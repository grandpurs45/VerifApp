# VerifApp

Application web de verification operationnelle des moyens.

## Objectif
Permettre aux agents de realiser des verifications sur smartphone
et aux gestionnaires de consulter les anomalies et l'historique.

## Statut
Projet en cours de developpement.

## Documentation projet
- Changelog: `CHANGELOG.md`
- Politique de versionning: `VERSIONING.md`
- Guide de deploiement: `DEPLOYMENT.md`

## Release rapide
Commande PowerShell:
`./scripts/release.ps1 -Version 0.1.1`

## Package production
Commande PowerShell:
`./scripts/package-release.ps1`

## Migration production
Commande serveur:
`php scripts/migrate.php`

## Compte admin par defaut
- Sur installation vierge, un compte gestionnaire est cree automatiquement:
  - identifiant: `admin` (ou `admin@verifapp.local`)
  - mot de passe: `admin`
- Le changement de mot de passe est obligatoire a la premiere connexion.

## Gestion des roles et acces
- Interface: `/index.php?controller=manager_roles&action=index`
- Les permissions manager sont pilotables par role (migration `017`).
- Le role `admin` est systeme et non supprimable.

## Docker local/serveur
1. Copier `.env.docker.example` vers `.env.docker`
   (laisser `APP_VERSION` non defini pour version automatique via le fichier `VERSION`)
2. Lancer:
`docker compose up -d --build`
3. Appliquer les migrations:
`docker compose exec web php scripts/migrate.php`

## Depannage QR
Depuis `0.11.0`, les tokens QR sont stockes en base (`app_settings`) et ne necessitent plus d'ecriture de `.env`.

Si la regeneration echoue en administration:
1. Verifier que les migrations sont a jour:
`docker compose exec web php scripts/migrate.php`
2. Verifier que la table `app_settings` existe (migration `018_create_app_settings.sql`).
3. Verifier les droits SQL de l'utilisateur applicatif (SELECT/INSERT/UPDATE sur `app_settings`).

## Module pharmacie (QR)
- Token configurable depuis l'administration (`Parametres application`), stocke en base.
- Fallback possible via `PHARMACY_QR_TOKEN` dans `.env` (ou `.env.docker`) si `app_settings` indisponible.
- Lien QR terrain:
`/index.php?controller=pharmacy&action=access&token=VOTRE_TOKEN`
- Backoffice gestionnaire:
`/index.php?controller=manager_pharmacy&action=index`
