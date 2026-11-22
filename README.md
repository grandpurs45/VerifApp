# VerifApp

Application web de verification materielle en caserne, orientee smartphone terrain et backoffice gestionnaire.

Version courante: `v1.10.0` (voir fichier `VERSION`).

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
    - valeur mesuree avec curseur tactile, echelle et pas configurables, seuils de conformite independants
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
- Suivi public par caserne (activation volontaire): planning des verifications, couverture mensuelle, graphique annuel et compteurs d anomalies; lien partageable, regenerable et desactivable, sans donnees individuelles. Voir le [guide administrateur](docs/ADMIN_GUIDE.md#15-partage-public-des-statistiques).

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
- `Verifications -> Configuration -> Vehicules & materiel -> Fiche vehicule`
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

## Couverture annuelle
- Depuis la vue mensuelle, ouvrir `Vue annuelle` pour visualiser janvier a decembre.
- Choisir l annee et le vehicule, puis eventuellement une autre annee disponible dans `Comparer avec`.
- Les points du graphique et les taux du tableau ouvrent le detail mensuel en conservant le vehicule.
- Le taux annuel est calcule sur les creneaux couverts / attendus, et non par moyenne simple des pourcentages des mois.
- Le mois en cours est limite aux jours ecoules; les mois futurs sont exclus. Les objectifs utilisent la configuration actuelle des vehicules, comme la vue mensuelle.
- Les donnees du tableau restent accessibles si le graphique ne se charge pas. Chart.js 4.5.1 est servi localement avec sa licence MIT.

## Vue mensuelle des verifications
URL:
- `/index.php?controller=verifications&action=monthly`

Comportement:
- `matin` = avant l heure de bascule configuree
- `soir` = a partir de l heure de bascule configuree
- valeur par defaut: `18h00`
- `Verifications enregistrees` compte les formulaires, pas les creneaux.
- En mode matin et soir, chaque poste attend 2 creneaux par jour; une garde 24 h couvre les deux tout en comptant pour 1 verification enregistree.
- `Taux de couverture` mesure les creneaux couverts / attendus, independamment du resultat conforme ou non conforme du materiel.

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
  - `./scripts/release.ps1 -Version 1.10.0`
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

### Mise a niveau vers v1.10.0
- Aucune nouvelle migration par rapport a v1.9.4; utiliser le script de mise a jour habituel pour appliquer les migrations precedentes si necessaire.
- Depuis Historique, ouvrir `Vue annuelle` pour consulter la couverture par mois et comparer deux annees disponibles.
- Le suivi public reste desactive par defaut. Depuis `Administration > Lien public de suivi`, generer volontairement le lien de la caserne active (droit `users.manage`).
- Toute personne disposant du lien peut consulter les statistiques agregees sans connexion; aucun nom d agent ni commentaire terrain n est expose. Utiliser HTTPS et regenerer ou desactiver le lien si sa diffusion n est plus souhaitee.
- Verifier apres deploiement: version `1.10.0`, vues mensuelle/annuelle, generation du lien, consultation en navigation privee et refus de l ancien lien apres regeneration/desactivation.
- Les assets du graphique et le CSS public sont inclus dans la release; aucun build npm n est requis sur le serveur.
- Tests: `php tests/verification_coverage_calculator.php`, `php tests/verification_yearly_view.php`, `php tests/statistics_share_security.php`, `php tests/public_anomaly_statistics.php`.

### Mise a niveau vers v1.9.4
- Aucune nouvelle migration par rapport a v1.9.3; la migration 045 reste requise si la mise a jour part d une version anterieure.
- La vue mensuelle distingue `Verifications enregistrees`, `Creneaux attendus / jour` et `Taux de couverture`. Le calcul reste inchange.
- En mode matin et soir, trois gardes de 24 h representent trois formulaires et six creneaux couverts sur six.
- Le calendrier et le detail journalier peuvent defiler horizontalement sur petit ecran, sans comprimer leurs valeurs.
- Apres deploiement, verifier la version `1.9.4` et recharger la vue mensuelle. Test: `php tests/monthly_coverage_labels.php`.

### Mise a niveau vers v1.9.3
- La migration `045_add_verification_submission_keys.sql` est obligatoire; le script de mise a jour habituel l applique automatiquement.
- Chaque formulaire terrain porte une cle unique. Le bouton est bloque pendant l envoi et une repetition du meme formulaire retrouve la verification existante sans nouvelle anomalie, occurrence ou notification.
- Un nouveau formulaire permet une nouvelle verification legitime. Des reponses differentes avec une cle deja enregistree sont refusees.
- Apres deploiement, verifier la version `1.9.3` et recharger les formulaires terrain ouverts avant la mise a jour.
- Aucun doublon historique n est supprime automatiquement. Les doublons suspects doivent etre verifies avant toute correction.
- Tests sans envoi reel: `php tests/verification_submission_idempotency.php` (SQLite en memoire) et `php tests/verification_replay_controller.php`.

### Mise a niveau vers v1.9.2
- Aucune nouvelle migration par rapport a v1.9.1.
- La couverture mensuelle reconnait les anciens postes apres changement de type ou reparation des postes, via les identifiants du materiel conserve.
- Les anciennes et nouvelles references sont regroupees sans doublonner les creneaux. Les gardes de 12 h / 24 h et les totaux de verifications restent pris en compte.
- Apres deploiement, recharger le tableau mensuel du vehicule concerne: aucun reenregistrement des verifications ni reparation manuelle des statistiques n est necessaire.
- Si le materiel d une verification est reparti entre plusieurs postes actuels, aucune equivalence ambigue n est inventee.
- Test de regression sans donnees reelles: `php tests/monthly_coverage_type_change.php` (SQLite en memoire).

### Mise a niveau vers v1.9.1
- Aucune nouvelle migration par rapport a v1.9.0.
- Lors du changement de type, les postes equivalents du type cible sont reutilises et les nouveaux libelles reprennent le bon suffixe (exemple: Conducteur VSAV vers Conducteur VSSUAP).
- Pour un vehicule deja converti avec des postes copies automatiquement portant l ancien suffixe, ouvrir `Modifier le type et l indicatif` et reenregistrer le meme type et le meme indicatif apres mise a jour. Aucun aller-retour vers l ancien type n est necessaire.
- La reparation concerne les rattachements du materiel de ce vehicule uniquement; les postes partages, les autres vehicules et les verifications passees ne sont pas renommes.
- Verifier la version `1.9.1`, les postes du formulaire mobile et la conservation du materiel. Test de regression: `php tests/vehicle_identity.php` (SQLite en memoire).

### Mise a niveau vers v1.9.0
- Aucune nouvelle migration par rapport a v1.8.1.
- Ouvrir `Verifications > Configuration > Types & postes` ou `Vehicules & materiel`. Sur desktop, ces entrees se trouvent dans le menu lateral; sur mobile, elles restent accessibles depuis les pages de configuration.
- `Modifier le type et l indicatif` est accessible depuis la liste des vehicules et `Configurer engin`. Le meme vehicule est conserve, sans duplication ni modification des reglages de verification.
- En cas de changement de type, les postes de meme nom sont reutilises dans le type cible, et les postes manquants sont crees. Les verifications passees conservent leurs postes d origine.
- Tester apres mise a jour: version `1.9.0`, navigation types/vehicules, changement d indicatif, formulaire mobile et historique. Les QR existants continuent de pointer vers le meme vehicule.
- Tests sans donnees reelles: `php tests/vehicle_identity.php` et `php tests/configuration_navigation.php`.

### Mise a niveau vers v1.8.1
- Aucune nouvelle migration par rapport a v1.8.0.
- Les mails de mise a jour d anomalie reprennent le modele detaille avec engin, materiel, auteur, statut, priorite, responsable et commentaire de traitement.
- L encodage des mails protege les balises HTML des coupures de longues lignes pendant le transport.
- Tests sans envoi reel: `php tests/notification_email_encoding.php` et `php tests/notification_email_update.php` (extension `pdo_sqlite` requise pour le second).
- Apres deploiement, verifier la version `1.8.1` et le rendu d un nouveau signalement puis d une mise a jour d anomalie.

### Mise a niveau vers v1.8.0
- Appliquer les migrations `043_add_slider_measure_controls.sql` et `044_add_exclusive_minimum_threshold.sql` avec le script habituel de mise a jour.
- Dans la fiche engin, choisir `Valeur mesuree avec curseur` pour les materiels concernes; aucune conversion automatique des mesures existantes.
- Exemple bouteille: unite `bars`, echelle `0` a `300`, pas `10`, conformite de `50` a `200`.
- L option `Non conforme si la valeur est inferieure ou egale au minimum` rend `50` non conforme; elle est decochee par defaut et disponible aussi pour les mesures classiques.
- Le terrain affiche la valeur du curseur sans champ editable. Une action explicite est obligatoire avant validation; les brouillons conservent les valeurs.
- Verifier apres deploiement: version `1.8.0`, configuration et duplication du materiel, curseur mobile, egalite au seuil, detail et export d une verification.
- Tests CLI sans acces aux donnees de l application: `php tests/slider_measure.php`, `php tests/slider_verification_submission.php`, `php tests/verification_lines.php` (extension `pdo_sqlite` requise pour les tests de repositories).

### Mise a niveau vers v1.7.0
- Aucune nouvelle migration par rapport a v1.6.0.
- La liste du stock pharmacie est en lecture; le crayon ouvre la modification d un article.
- Les indicateurs filtrent la liste; les actions secondaires sont accessibles dans le menu de chaque article.
- Une modification refusee conserve les valeurs saisies et reouvre le bon article.
- Activer ou desactiver un article ne modifie ni son stock ni ses autres reglages.
- Verification du changement de statut: `php tests/pharmacy_article_status.php` (SQLite en memoire, sans acces aux donnees de l application).

### Mise a niveau vers v1.6.0
- Aucune nouvelle migration par rapport a v1.5.1.
- `Pharmacie > Reception` regroupe la saisie des articles recus, les quantites et le stock prevu apres reception.
- Le recapitulatif distingue les unites. La reception ne modifie pas la commande de reference.
- Le formulaire d ajout d article est accessible via le bouton `Ajouter un article` de la page Stock.

### Mise a niveau vers v1.5.1
- Aucune nouvelle migration par rapport a v1.5.0.
- Les refus lies au droit `dashboard.view` sont distingues des erreurs de mot de passe dans la connexion et le journal d audit.
- Apres un changement de mot de passe initial, utiliser le nouveau mot de passe meme si le role doit encore etre corrige par un administrateur.
- Verification automatisee du parcours: `php tests/auth_first_login.php` (sans connexion a une base de donnees).

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
