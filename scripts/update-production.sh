#!/usr/bin/env bash

set -Eeuo pipefail
IFS=$'\n\t'

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${REPO_ROOT}/.env.docker"
TARGET_REF="origin/main"
ASSUME_YES=0
SKIP_BACKUP=0
PREVIOUS_COMMIT=""
BACKUP_DESTINATION=""
COLOR_ENABLED=0

if [[ (-t 1 || -t 2) && -z "${NO_COLOR:-}" ]]; then
    COLOR_ENABLED=1
fi

COLOR_RESET=$'\033[0m'
COLOR_BLUE=$'\033[36m'
COLOR_GREEN=$'\033[32m'
COLOR_YELLOW=$'\033[33m'
COLOR_RED=$'\033[31m'

usage() {
    cat <<'EOF'
Usage:
  bash scripts/update-production.sh [options]

Options:
  --ref REF          Reference Git a deployer (defaut: origin/main).
                     Exemple: --ref v1.2.1
  --env-file PATH    Fichier d environnement Compose (defaut: .env.docker).
  --yes              Ne demande pas de confirmation.
  --no-backup        Ignore la sauvegarde pre-deploiement.
  --help             Affiche cette aide.

Exemples:
  bash scripts/update-production.sh
  bash scripts/update-production.sh --yes
  bash scripts/update-production.sh --ref v1.2.1 --yes
EOF
}

write_log() {
    local level=$1
    local color=$2
    shift 2

    if [[ "${COLOR_ENABLED}" -eq 1 ]]; then
        printf '%b[%s] %-7s%b %s\n' \
            "${color}" \
            "$(date '+%Y-%m-%d %H:%M:%S')" \
            "${level}" \
            "${COLOR_RESET}" \
            "$*"
    else
        printf '[%s] %-7s %s\n' \
            "$(date '+%Y-%m-%d %H:%M:%S')" \
            "${level}" \
            "$*"
    fi
}

log() {
    write_log "INFO" "${COLOR_BLUE}" "$@"
}

success() {
    write_log "OK" "${COLOR_GREEN}" "$@"
}

warn() {
    write_log "ATTENTION" "${COLOR_YELLOW}" "$@"
}

error_log() {
    write_log "ERREUR" "${COLOR_RED}" "$@"
}

fail() {
    error_log "$*"
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Commande introuvable: $1"
}

on_error() {
    local exit_code=$?
    local line_number=${1:-?}

    printf '\n' >&2
    error_log "Echec de la mise a jour a la ligne ${line_number}." >&2
    if [[ -n "${PREVIOUS_COMMIT}" ]]; then
        error_log "Commit avant mise a jour: ${PREVIOUS_COMMIT}" >&2
    fi
    if [[ -n "${BACKUP_DESTINATION}" ]]; then
        error_log "Sauvegarde disponible dans: ${BACKUP_DESTINATION}" >&2
    fi
    error_log "Consulte les logs avant toute tentative de rollback automatique." >&2
    exit "${exit_code}"
}

trap 'on_error ${LINENO}' ERR

while [[ $# -gt 0 ]]; do
    case "$1" in
        --ref)
            [[ $# -ge 2 ]] || fail "Valeur manquante apres --ref."
            TARGET_REF="$2"
            shift 2
            ;;
        --env-file)
            [[ $# -ge 2 ]] || fail "Valeur manquante apres --env-file."
            ENV_FILE="$2"
            shift 2
            ;;
        --yes)
            ASSUME_YES=1
            shift
            ;;
        --no-backup)
            SKIP_BACKUP=1
            shift
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            fail "Option inconnue: $1"
            ;;
    esac
done

require_command git
require_command docker

[[ "${TARGET_REF}" =~ ^[A-Za-z0-9][A-Za-z0-9._/-]*$ ]] \
    || fail "Reference Git invalide: ${TARGET_REF}"

docker compose version >/dev/null 2>&1 || fail "Le plugin 'docker compose' est indisponible."

cd "${REPO_ROOT}"

if [[ "${ENV_FILE}" != /* ]]; then
    ENV_FILE="${REPO_ROOT}/${ENV_FILE}"
fi

if command -v flock >/dev/null 2>&1; then
    exec 9>"${REPO_ROOT}/.git/verifapp-update.lock"
    flock -n 9 || fail "Une autre mise a jour VerifApp est deja en cours."
else
    warn "flock absent, verrou anti-concurrence non disponible."
fi

[[ -f "${ENV_FILE}" ]] || fail "Fichier d environnement introuvable: ${ENV_FILE}"

if [[ -n "$(git status --porcelain)" ]]; then
    git status --short
    fail "Le depot contient des modifications locales. Mise a jour annulee."
fi

for required_key in DB_NAME DB_USER DB_PASSWORD; do
    if ! grep -Eq "^[[:space:]]*${required_key}=.+$" "${ENV_FILE}"; then
        fail "${required_key} est absent ou vide dans ${ENV_FILE}."
    fi
done

COMPOSE=(docker compose --env-file "${ENV_FILE}")
"${COMPOSE[@]}" config --quiet

PREVIOUS_COMMIT="$(git rev-parse HEAD)"
log "Recuperation des references Git..."
git fetch origin --tags --prune

TARGET_COMMIT="$(git rev-parse --verify "${TARGET_REF}^{commit}")" \
    || fail "Reference Git introuvable: ${TARGET_REF}"

log "Version actuelle : $(git describe --tags --always "${PREVIOUS_COMMIT}")"
log "Version cible    : $(git describe --tags --always "${TARGET_COMMIT}")"

if [[ "${ASSUME_YES}" -ne 1 ]]; then
    if [[ ! -t 0 ]]; then
        fail "Execution non interactive: ajoute --yes pour autoriser la mise a jour."
    fi
    read -r -p "Continuer la mise a jour ? [y/N] " confirmation
    [[ "${confirmation}" =~ ^[yY]$ ]] || fail "Mise a jour annulee."
fi

if [[ "${SKIP_BACKUP}" -ne 1 ]]; then
    WEB_CONTAINER_ID="$("${COMPOSE[@]}" ps -q web)"
    [[ -n "${WEB_CONTAINER_ID}" ]] || fail "Le conteneur web est absent: sauvegarde impossible."

    TIMESTAMP="$(date '+%Y%m%d_%H%M%S')"
    CONTAINER_BACKUP_DIR="/tmp/verifapp-update-${TIMESTAMP}"
    BACKUP_DESTINATION="${REPO_ROOT}/backups/pre_update_${TIMESTAMP}"

    mkdir -p "${BACKUP_DESTINATION}"
    chmod 700 "${BACKUP_DESTINATION}"

    log "Creation de la sauvegarde pre-deploiement..."
    "${COMPOSE[@]}" exec -T web \
        php scripts/backup.php \
        --out="${CONTAINER_BACKUP_DIR}" \
        --name=pre_update

    docker cp "${WEB_CONTAINER_ID}:${CONTAINER_BACKUP_DIR}/." "${BACKUP_DESTINATION}/"
    if [[ -z "$(find "${BACKUP_DESTINATION}" -mindepth 1 -maxdepth 1 -print -quit)" ]]; then
        fail "La sauvegarde copiee depuis le conteneur est vide."
    fi
    success "Sauvegarde persistante: ${BACKUP_DESTINATION}"
else
    warn "Sauvegarde ignoree (--no-backup)."
fi

if [[ "${TARGET_REF}" == "origin/main" ]]; then
    CURRENT_BRANCH="$(git branch --show-current)"
    if [[ -z "${CURRENT_BRANCH}" ]]; then
        log "Retour sur la branche main depuis une version taguee..."
        git switch main
    elif [[ "${CURRENT_BRANCH}" != "main" ]]; then
        fail "Le deploiement origin/main exige la branche main ou un HEAD detache."
    fi
    log "Mise a jour fast-forward de la branche main..."
    git merge --ff-only "${TARGET_COMMIT}"
else
    log "Deploiement de la reference immuable ${TARGET_REF}..."
    git switch --detach "${TARGET_COMMIT}"
fi

for required_key in DB_NAME DB_USER DB_PASSWORD; do
    if ! grep -Eq "^[[:space:]]*${required_key}=.+$" "${ENV_FILE}"; then
        fail "${required_key} est absent ou vide dans ${ENV_FILE}."
    fi
done

"${COMPOSE[@]}" config --quiet

log "Reconstruction et redemarrage des conteneurs..."
"${COMPOSE[@]}" up -d --build

log "Application des migrations..."
"${COMPOSE[@]}" exec -T web php scripts/migrate.php

log "Attente du healthcheck..."
HEALTH_OK=0
for ((attempt = 1; attempt <= 24; attempt++)); do
    if "${COMPOSE[@]}" exec -T web php /var/www/html/docker/healthcheck-web.php >/dev/null 2>&1; then
        HEALTH_OK=1
        break
    fi
    sleep 5
done

if [[ "${HEALTH_OK}" -ne 1 ]]; then
    "${COMPOSE[@]}" ps
    "${COMPOSE[@]}" logs --tail=100 web
    fail "Le healthcheck n est pas revenu au vert apres 120 secondes."
fi

success "Healthcheck operationnel."
"${COMPOSE[@]}" ps

success "Mise a jour terminee avec succes."
success "Version deployee: $(git describe --tags --always HEAD)"
if [[ -n "${BACKUP_DESTINATION}" ]]; then
    success "Sauvegarde: ${BACKUP_DESTINATION}"
fi
