# VerifApp

Application web de verification materielle en caserne, orientee smartphone terrain et backoffice gestionnaire.

Version courante: `v1.5.0` (voir fichier `VERSION`).

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
- Backup / Restore
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
  - regroupement des remontees identiques en occurrences
  - vue compacte, assignation, priorite, statut et historique terrain
  - email detaille avec engin, declarant et controles non conformes
  - rappels d anomalies connues selon un seuil d occurrences configurable
- Historique:
  - filtres multi-criteres
  - detail verification
  - export PDF
- Vue mensuelle matin/soir:
  - lecture calendrier rapide
  - indicateurs de couverture et conformite
  - frequence configurable par vehicule: quotidienne ou matin + soir
  - une garde de 24 h couvre les deux creneaux
- Dashboard par module:
  - indicateurs regroupes par categorie (`Anomalies`, `Verifications`, `Pharmacie`)
  - taux de verification du mois jusqu a J-1 (jour en cours exclu)
- Backoffice parc materiel:
  - types et postes
  - vehicules, zones, sous-zones
  - materiel configure par engin
  - recherche instantanee du materiel + filtre par zone dans la fiche engin
  - activation des verifications engin par engin
  - ordre des zones par glisser-deposer
- Module pharmacie invite (QR) pour sorties de stock.
- Module inventaire pharmacie (BO + terrain mobile via QR dedie).
- Sorties pharmacie:
  - acquittement des sorties
  - synthese depuis la derniere commande
  - marquage `commande passee`
  - notification au passage au seuil warning ou critique, sans doublon tant que le niveau ne change pas
- Roles et acces configurables.
- Groupes de notifications independants des roles.
- Audit des ouvertures QR verification, pharmacie et inventaire.
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
2. Remplacer `DB_PASSWORD` par un secret long et aleatoire.
3. Lancer les conteneurs:
   - `docker compose --env-file .env.docker up -d --build`
4. Appliquer les migrations:
   - `docker compose --env-file .env.docker exec web php scripts/migrate.php`
5. Ouvrir l application:
   - `http://localhost:8080`

Important:
- Laisser `APP_VERSION` vide pour utiliser automatiquement le fichier `VERSION`.
- En reverse proxy HTTPS (Traefik, Nginx Proxy, etc.), renseigner `APP_URL` en `https://...` et activer `APP_FORCE_HTTPS=1`.
- Les ports web et phpMyAdmin sont lies a `127.0.0.1` et ne sont pas exposes publiquement.
- phpMyAdmin est desactive par defaut. Pour un acces ponctuel: `docker compose --env-file .env.docker --profile tools up -d phpmyadmin`.
- Depuis un poste distant, ouvrir un tunnel SSH: `ssh -L 8081:127.0.0.1:8081 utilisateur@serveur`, puis utiliser `http://127.0.0.1:8081`.

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
- notifications (cloche + email) par evenement, role, groupe et utilisateur

Pharmacie (backoffice):
- `stock`: `/index.php?controller=manager_pharmacy&action=index`
- `sorties`: `/index.php?controller=manager_pharmacy&action=outputs`

Notifications email (optionnel):
- activer `NOTIFICATIONS_EMAIL_ENABLED=1`
- definir `NOTIFICATIONS_EMAIL_FROM` et `NOTIFICATIONS_EMAIL_FROM_NAME`
- choisir le transport:
  - `NOTIFICATIONS_EMAIL_TRANSPORT=mail` (mail() PHP)
  - `NOTIFICATIONS_EMAIL_TRANSPORT=smtp` (SMTP direct depuis VerifApp)
- si `smtp`, configurer:
  - `NOTIFICATIONS_EMAIL_SMTP_HOST`
  - `NOTIFICATIONS_EMAIL_SMTP_PORT` (ex: `587`)
  - `NOTIFICATIONS_EMAIL_SMTP_SECURITY` (`none`, `tls`, `ssl`)
  - `NOTIFICATIONS_EMAIL_SMTP_AUTH` (`1` ou `0`)
  - `NOTIFICATIONS_EMAIL_SMTP_USER`
  - `NOTIFICATIONS_EMAIL_SMTP_PASS`
  - `NOTIFICATIONS_EMAIL_SMTP_TIMEOUT` (3 a 60 sec)
- configurer ensuite les preferences utilisateur dans `Mon compte` (cloche/email); sans preference enregistree, un utilisateur explicitement cible accepte les deux canaux actifs
- creer si besoin des groupes independants des roles dans `Administration -> Parametres notifications`
- utiliser le bouton `Envoyer email de test` dans `Administration -> Parametres application`

## Preparation et activation des engins
- Un engin nouvellement cree est exclu des verifications par defaut, afin de permettre la saisie de ses zones et de son materiel.
- L activation se fait depuis sa fiche avec `Inclure cet engin dans les verifications`.
- Un engin exclu n apparait pas sur le formulaire terrain et ne compte pas dans les postes attendus de la vue mensuelle.
- L ordre des zones d un meme niveau peut etre modifie avec la poignee de glisser-deposer.

## Audit des QR Codes
- `Administration -> Audit securite` separe le journal des connexions du journal des ouvertures QR.
- Le journal QR dispose de filtres et d un export CSV dedies.
- La trace contient l heure, le module, l engin eventuel, l identite lorsqu elle est connue, l IP client, l IP du reverse proxy et le navigateur.
- Seule une empreinte du token est conservee; le token QR n est jamais stocke en clair dans le journal.

## Alertes de stock pharmacie
- `warning`: le stock est exactement au seuil et l option `Surveiller le seuil` est activee sur l article.
- `critique`: le stock est strictement inferieur au seuil configure.
- Une seule notification est envoyee par article et par niveau. Une nouvelle alerte est possible apres retour a la normale ou changement de niveau.
- Le ciblage et les canaux se configurent dans `Administration > Parametres notifications` avec les evenements pharmacie correspondants.

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
- Backup complet (data + conf):
  - `php scripts/backup.php --out=backups --name=manual`
- Restore complet:
  - `php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force`
- Restore + `.env`:
  - `php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force --restore-env`
- Reset admin dev:
  - `php scripts/reset-admin-dev.php`
- Release PowerShell:
  - `./scripts/release.ps1 -Version 1.5.0`
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
- Automatisation recommandee:
  - `bash scripts/update-production.sh`
  - execution non interactive: `bash scripts/update-production.sh --yes`
  - version precise: `bash scripts/update-production.sh --ref vX.Y.Z --yes`
  - sans couleurs: `NO_COLOR=1 bash scripts/update-production.sh`
- Le script verifie Git et Compose, cree une sauvegarde persistante, reconstruit les conteneurs, applique les migrations et attend le healthcheck.
- Mise a jour manuelle:
  - `git pull origin main --tags`
  - `docker compose --env-file .env.docker up -d --build`
  - `docker compose --env-file .env.docker exec web php scripts/migrate.php`

### Verification post-deploiement
- page login gestionnaire
- dashboard
- vue mensuelle
- page parametres application
- acces QR verification + pharmacie
- creation d une anomalie NOK et reception de l email detaille
- audit QR avec la bonne caserne

### Mise a niveau vers v1.4.0
- La migration `040_prepare_v14_features.sql` est obligatoire.
- Elle ajoute:
  - l activation des verifications par engin
  - les occurrences d anomalies
  - les groupes de notifications
  - le journal des ouvertures QR
- Les engins existants restent inclus dans les verifications.
- Les nouveaux engins sont crees hors verification jusqu a leur activation depuis leur fiche.

### Mise a niveau vers v1.4.1
- La migration `041_add_verification_frequency.sql` ajoute la frequence de couverture par vehicule.
- Tous les vehicules existants restent en mode `1 fois par jour` apres migration.
- Depuis la fiche de chaque vehicule concerne, choisir `Matin et soir` pour exiger deux couvertures par poste.
- Dans `Administration > Parametres notifications`, regler le seuil de rappel des anomalies connues.

### Mise a niveau vers v1.5.0
- Appliquer `042_separate_qr_ip_and_pharmacy_alerts.sql` avec `php scripts/migrate.php`.
- Cette migration separe l IP client de celle du proxy dans les nouvelles traces QR et initialise le suivi anti-doublon des alertes de stock.
- Configurer les destinataires des evenements `Pharmacie: seuil warning atteint` et `Pharmacie: stock critique` dans `Administration > Parametres notifications`.
- Les anciennes traces QR conservent leur IP historique; seules les nouvelles ouvertures distinguent IP client et IP proxy.

## Depannage
### Echec generation QR
1. Verifier migrations:
   - `docker compose --env-file .env.docker exec web php scripts/migrate.php`
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
- Guide rollback: `docs/ROLLBACK.md`
- Guide admin: `docs/ADMIN_GUIDE.md`
- Guide utilisateur: `docs/USER_GUIDE.md`
- Runbook incident: `docs/RUNBOOK_INCIDENT.md`
- Onboarding nouvelle caserne: `docs/ONBOARDING_CASERNE.md`
- Roadmap: `docs/ROADMAP.md`
