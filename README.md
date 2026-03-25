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

## Docker local/serveur
1. Copier `.env.docker.example` vers `.env.docker`
   (laisser `APP_VERSION` non defini pour version automatique via le fichier `VERSION`)
2. Lancer:
`docker compose up -d --build`
3. Appliquer les migrations:
`docker compose exec web php scripts/migrate.php`
