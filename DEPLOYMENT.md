# Deployment Production

Ce guide vise un deploiement simple de VerifApp sur serveur PHP + MySQL/MariaDB.

## Prerequis
- PHP 8.1+
- Extension PDO MySQL activee
- MySQL/MariaDB
- Acces SSH/SFTP au serveur

## 1) Generer le package release
Depuis le poste de dev:

```powershell
.\scripts\package-release.ps1
```

Sortie:
- `dist/verifapp-vX.Y.Z.zip`
- `dist/verifapp-vX.Y.Z.zip.sha256`

## 2) Upload serveur
- Transferer le zip dans le dossier de l'application.
- Extraire l'archive.
- Configurer le vhost pour pointer sur `public/`.

## 3) Configurer l'environnement
- Copier `.env.example` en `.env`.
- Renseigner `APP_ENV`, `APP_URL`, `DB_*`.
- Optionnel: `FIELD_QR_TOKEN`.
- Optionnel: `PHARMACY_QR_TOKEN` pour proteger l'acces QR du module pharmacie.
- Ne pas definir `APP_VERSION` pour laisser l'application lire automatiquement la version depuis le fichier `VERSION`.

## 4) Appliquer les migrations
Sur le serveur:

```bash
php scripts/migrate.php
```

Le script applique automatiquement les migrations non executees et garde l'historique dans `schema_migrations`.

## 5) (Optionnel) Charger les seeds de demo
Uniquement en environnement de test/demo.

## 6) Verifier le deploiement
- Ouvrir `https://ton-domaine/health.php`
- Reponse attendue:
  - `status: ok`
  - `db: ok`

## 6bis) Premiere connexion gestionnaire
- Compte par defaut cree par migration:
  - identifiant: `admin` (ou `admin@verifapp.local`)
  - mot de passe: `admin`
- A la premiere connexion, l'application impose le changement de mot de passe.

## 7) Rollback rapide
- Replacer l'archive de la version precedente.
- Restaurer snapshot DB si une migration incompatible a ete appliquee.

## Notes
- Ne pas versionner `.env`.
- Toujours tester les migrations en preprod avant prod.
- Les migrations appliquees en production ne doivent pas etre modifiees; ajouter une migration corrective.
