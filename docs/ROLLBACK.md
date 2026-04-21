# Rollback VerifApp

Guide operationnel pour restaurer rapidement une instance VerifApp apres incident de deploiement.

## 1. Principe
- Toujours faire un backup avant upgrade.
- Un rollback = restaurer code + restaurer base + verifier.

## 2. Backup avant upgrade
Depuis la racine du projet:

```bash
php scripts/backup.php --out=backups --name=pre_upgrade
```

Resultat:
- `backups/verifapp_backup_YYYYmmdd_HHMMSS_pre_upgrade.zip` (ou dossier si ZipArchive indisponible)

Contenu:
- `db.sql`
- `app_settings.json`
- `manifest.json`
- `env.snapshot` (si `.env` present et backup sans `--no-env`)

## 3. Rollback code
Option Git:
- revenir au tag precedent (`git checkout <tag>`), ou
- redeployer l archive precedemment validee.

## 4. Rollback data
Restauration DB depuis backup:

```bash
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force
```

Important:
- `--force` est obligatoire (operation destructive).
- le script remplace le contenu de la base cible.

Restauration `.env` (optionnelle):

```bash
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force --restore-env
```

## 5. Post-restore
1. Redemarrer services (Apache/PHP-FPM ou Docker).
2. Verifier `health.php`:
   - `status=ok`
   - `db=ok`
3. Tester login gestionnaire.
4. Tester 1 action metier critique (ex: sortie pharmacie ou verification terrain).

## 6. Cas Docker (exemple)
```bash
docker compose up -d --build
docker compose exec web php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force
docker compose restart web
```

## 7. Points d attention
- Restaurer un backup dans la mauvaise base ecrase les donnees.
- Si la version code restauree est plus ancienne, verifier compatibilite migrations.
- Ne jamais modifier une migration deja appliquee; ajouter une migration corrective.
