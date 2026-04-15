# VerifApp

Application web de verification materielle en caserne, orientee smartphone terrain et backoffice gestionnaire.

Version courante: `v0.18.1` (voir fichier `VERSION`).

## Sommaire
- Objectif
- Fonctionnalites principales
- Architecture technique
- Prerequis
- Installation rapide (Docker)
- Installation locale (XAMPP / PHP)
- Acces et profils
- Flux QR terrain
- Multi-caserne
- Configuration administration
- Vue mensuelle des verifications
- Scripts utiles
- Deploiement et release
- Depannage
- Documentation associee

## Objectif
VerifApp permet de:
- Realiser des verifications d engins rapidement depuis smartphone.
- Tracer qui a verifie, quand, et avec quel resultat.
- Ouvrir automatiquement des anomalies sur les points non conformes.
- Piloter le parc (types, vehicules, zones, materiel) via backoffice.

## Fonctionnalites principales
- Verification terrain mobile:
  - selection engin + poste
  - checklist adaptee a l engin
  - saisies supportees:
    - choix `fonctionnel / non fonctionnel`
    - check `present / absent`
    - valeur mesuree avec seuils min/max
- Anomalies:
  - creation automatique en cas de `nok`
  - assignation et suivi
- Historique:
  - filtres multi-criteres
  - detail verification
  - export PDF
- Vue mensuelle matin/soir:
  - lecture calendrier rapide
  - indicateurs de couverture et conformite
- Dashboard par module:
  - indicateurs regroupes par categorie (`Anomalies`, `Verifications`, `Pharmacie`)
  - taux de verification du mois jusqu a J-1 (jour en cours exclu)
- Backoffice parc materiel:
  - types et postes
  - vehicules, zones, sous-zones
  - materiel configure par engin
  - recherche instantanee du materiel + filtre par zone dans la fiche engin
- Module pharmacie invite (QR) pour sorties de stock.
- Module inventaire pharmacie (BO + terrain mobile via QR dedie).
- Sorties pharmacie:
  - acquittement des sorties
  - synthese depuis la derniere commande
  - marquage `commande passee`
- Roles et acces configurables.
- Multi-caserne dans une seule instance.

## Architecture technique
- Stack: PHP (MVC leger), MySQL/MariaDB, Apache.
- Routing: `index.php?controller=X&action=Y`.
- Dossiers:
  - `app/Controllers`
  - `app/Repositories`
  - `public/views`
  - `database/migrations`

## Prerequis
- PHP `8.2+`
- Extensions PHP: `pdo`, `pdo_mysql`
- MySQL ou MariaDB
- Serveur web (Apache recommande)

## Installation rapide (Docker)
1. Copier la configuration:
   - `cp .env.docker.example .env.docker`
2. Lancer les conteneurs:
   - `docker compose up -d --build`
3. Appliquer les migrations:
   - `docker compose exec web php scripts/migrate.php`
4. Ouvrir l application:
   - `http://localhost:8080`

Important:
- Laisser `APP_VERSION` vide pour utiliser automatiquement le fichier `VERSION`.

## Installation locale (XAMPP / PHP)
1. Configurer la base dans `.env`.
2. Appliquer les migrations:
   - `php scripts/migrate.php`
3. Ouvrir:
   - `http://localhost/VerifApp/public/index.php`

## Acces et profils
### Gestionnaire
- Connexion:
  - `/index.php?controller=manager_auth&action=login_form`
- Backoffice:
  - `/index.php?controller=manager&action=dashboard`

### Verificateur terrain
- Acces via lien/QR invite:
  - `/index.php?controller=field&action=access`

### Compte admin par defaut (installation vierge)
- Identifiant: `admin` ou `admin@verifapp.local`
- Mot de passe: `admin`
- Changement de mot de passe force a la premiere connexion.

## Flux QR terrain
### QR global caserne (verification)
Genere depuis:
- `Administration -> Parametres application`

### QR engin (verification ciblee)
Genere depuis:
- `Parc & materiel -> Fiche vehicule`
- Actions disponibles:
  - generer / regenerer
  - supprimer
  - copier lien
  - ouvrir lien
  - imprimer QR

### QR pharmacie
Genere depuis:
- `Administration -> Parametres application`

Note:
- Depuis `v0.11.0`, les tokens QR sont stockes en base dans `app_settings`.

## Multi-caserne
- Une base peut contenir plusieurs casernes.
- Un utilisateur peut appartenir a plusieurs casernes.
- Si l utilisateur a plusieurs casernes:
  - ecran de choix apres login.
- Tous les modules backoffice sont scopes sur la caserne active.

## Configuration administration
Menu:
- `/index.php?controller=manager_admin&action=menu`

Parametres application:
- `/index.php?controller=manager_admin&action=settings`

Reglages notables:
- expiration session gestionnaire
- generation QR invites
- seuil horaire matin/soir des verifications mensuelles par caserne

Pharmacie (backoffice):
- `stock`: `/index.php?controller=manager_pharmacy&action=index`
- `sorties`: `/index.php?controller=manager_pharmacy&action=outputs`

## Vue mensuelle des verifications
URL:
- `/index.php?controller=verifications&action=monthly`

Comportement:
- `matin` = avant l heure de bascule configuree
- `soir` = a partir de l heure de bascule configuree
- valeur par defaut: `18h00`

## Scripts utiles
- Migrations:
  - `php scripts/migrate.php`
- Reset admin dev:
  - `php scripts/reset-admin-dev.php`
- Release PowerShell:
  - `./scripts/release.ps1 -Version 0.17.0`
- Packaging release:
  - `./scripts/package-release.ps1`

## Deploiement et release
### Push release
- `git push origin main --tags`

### Checklist release (obligatoire)
- mettre a jour `VERSION`
- mettre a jour `CHANGELOG.md`
- mettre a jour `README.md`

### Mise a jour serveur
- `git pull origin main --tags`
- `docker compose up -d --build`
- `docker compose exec web php scripts/migrate.php`

### Verification post-deploiement
- page login gestionnaire
- dashboard
- vue mensuelle
- page parametres application
- acces QR verification + pharmacie

## Depannage
### Echec generation QR
1. Verifier migrations:
   - `docker compose exec web php scripts/migrate.php`
2. Verifier table `app_settings` (migration `018_create_app_settings.sql`).
3. Verifier droits SQL sur `app_settings` (`SELECT`, `INSERT`, `UPDATE`, `DELETE`).

### Erreurs de migration
- Si environnement deja initialise, verifier table `schema_migrations` avant de rejouer.
- Eviter de supprimer manuellement des tables referencees par des FK.

### Version affichee incorrecte
- Verifier contenu `VERSION`.
- Verifier absence de surcharge `APP_VERSION` dans `.env` / `.env.docker`.

## Documentation associee
- Changelog: `CHANGELOG.md`
- Politique de versionning: `VERSIONING.md`
- Guide de deploiement: `DEPLOYMENT.md`
