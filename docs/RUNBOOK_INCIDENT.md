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

## 3. Incident login / permissions
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

## 4. Incident pharmacie
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

## 5. Incident verification terrain
- verifier token QR terrain valide (caserne).
- verifier session/brouillon (TTL).
- verifier controles zones/postes associes au vehicule.
- verifier creation anomalie en cas NOK.

## 6. Procedure rollback standard
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

## 7. Communication incident
- informer:
  - perimetre impacte (caserne/modules)
  - workaround temporaire
  - ETA de retour
- cloturer:
  - cause racine
  - action corrective
  - prevention recurrence

## 8. Checklist post-incident
- backup immediat apres stabilisation.
- export audit securite si incident auth.
- mise a jour changelog interne incident.
- ticket d amelioration si faille process/outillage.
