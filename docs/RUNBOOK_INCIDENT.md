# Runbook Incident VerifApp (v1)

Guide court d intervention en cas d incident production.

## 1. Triage initial (5 minutes)
- verifier disponibilite:
  - `GET /health.php`
- verifier symptome:
  - login KO
  - erreurs metier (sorties/verifs)
  - notifications KO
  - lenteur globale
- capturer:
  - heure
  - caserne impactee
  - utilisateur impacte
  - code incident (si present)

## 2. Healthcheck attendu
`/health.php` doit retourner:
- `status: ok`
- `db: ok`
- `version` renseignee
- `timezone` renseignee
- bloc `smtp` coherent avec la config

Si `db: down`:
- verifier credentials DB (`.env`)
- verifier service DB actif
- verifier connectivite reseau entre app et DB

## 3. Incident 504 / Gateway Timeout
Objectif: distinguer proxy, conteneur web, Apache/PHP et base.

Verifier depuis le serveur, avant tout redemarrage si possible:
```bash
docker compose ps
docker inspect --format '{{.Name}} health={{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}} oom={{.State.OOMKilled}} exit={{.State.ExitCode}}' verifapp-web
docker stats --no-stream verifapp-web verifapp-db
docker logs --since=2h verifapp-web
docker logs --since=2h verifapp-db
curl -sv --max-time 10 http://127.0.0.1:8080/health.php
curl -sv --max-time 10 https://ton-domaine/health.php
```

Lecture rapide:
- `127.0.0.1:8080/health.php` OK mais domaine KO: regarder Traefik, DNS, TLS, reseau `proxy`.
- `127.0.0.1:8080/health.php` timeout: regarder Apache/PHP dans `verifapp-web`.
- `/health.php` repond `db: down`: regarder MariaDB et la connectivite DB.
- conteneur `running` mais health `unhealthy`: redemarrer `web`, puis conserver logs et heure exacte.

Monitoring:
- Uptime Kuma et Upptime doivent cibler la meme URL, idealement `https://ton-domaine/health.php`.
- verifier que Upptime n accepte pas seulement une page HTML ou une redirection comme succes.
- configurer Upptime pour exiger HTTP `200` et, si possible, la presence de `"status":"ok"` dans le corps.

## 4. Incident login / permissions
Actions:
- ouvrir `Administration > Audit securite`
- filtrer par identifiant/IP/date
- verifier:
  - `invalid_credentials`
  - `locked`
  - `inactive_user`
  - `caserne_selected/login_ok`
- corriger compte:
  - activation
  - reset mot de passe
  - role/affectation caserne

## 5. Incident pharmacie
### Sorties KO
- verifier permissions `pharmacy.manage`.
- verifier article actif.
- verifier contraintes motif (si sortie CR obligatoire).

### Inventaires KO
- verifier acces QR inventaire (token valide).
- verifier saisie quantite obligatoire.
- verifier enregistrement en base.

### Notification sortie non recue
- verifier parametres notifications (canaux/evenements).
- verifier preferences utilisateur.
- lancer `email test` depuis parametres.
- verifier blocage provider SMTP.

## 6. Incident verification terrain
- verifier token QR terrain valide (caserne).
- verifier session/brouillon (TTL).
- verifier controles zones/postes associes au vehicule.
- verifier creation anomalie en cas NOK.

## 7. Procedure rollback standard
1. geler les actions risquant d ecraser des donnees.
2. restaurer code version stable.
3. restaurer backup:
   ```bash
   php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force
   ```
4. redemarrer services web/php.
5. verifier `health.php`.
6. valider login + 1 flux metier.

Voir detail: `docs/ROLLBACK.md`.

## 8. Communication incident
- informer:
  - perimetre impacte (caserne/modules)
  - workaround temporaire
  - ETA de retour
- cloturer:
  - cause racine
  - action corrective
  - prevention recurrence

## 9. Checklist post-incident
- backup immediat apres stabilisation.
- export audit securite si incident auth.
- mise a jour changelog interne incident.
- ticket d amelioration si faille process/outillage.
