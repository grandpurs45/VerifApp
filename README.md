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
