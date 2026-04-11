# Changelog

Tous les changements notables de ce projet sont documentes dans ce fichier.

Le format suit Keep a Changelog et Semantic Versioning.

## [Unreleased]

### Added
- Rien pour le moment.

## [0.16.7] - 2026-04-11

### Added
- Pharmacie inventaire:
  - QR dedie `Inventaire mobile` dans `Administration -> Parametres application`
  - generation/regeneration token dedie inventaire
  - impression A4 de l affiche QR inventaire
  - message imprime QR inventaire configurable par caserne
- Inventaire (BO + terrain):
  - support des lignes `Materiel en trop (hors liste)`
  - migrations:
    - `028_create_pharmacy_inventory_module.sql`
    - `029_allow_inventory_extra_lines.sql`

### Changed
- Inventaire terrain (UX mobile):
  - passage en inventaire complet: quantite obligatoire pour tous les articles actifs
  - progression sticky en tete (`x / total`)
  - filtre rapide `Tous / A saisir`
  - cartes allegees pour lecture/saisie plus rapide
  - commentaires d ecart replis par defaut
- Inventaire BO:
  - ajout du bloc `Materiel en trop (hors liste)` pour les ecarts non references

### Fixed
- Acces inventaire mobile:
  - compatibilite token dedie inventaire + fallback token pharmacie

## [0.16.6] - 2026-04-10

### Added
- Dashboard gestionnaire (module verifications):
  - nouvel indicateur `Taux de verification du mois (jusqu a hier)`
  - calcul de couverture des creneaux `matin/soir` du 1er du mois a J-1 (jour en cours exclu)
  - affichage detaille `creneaux couverts / attendus`

### Changed
- Dashboard:
  - regroupement visuel des KPIs par module (`Anomalies`, `Verifications`, `Pharmacie`) pour une lecture plus claire

### Fixed
- Couleur des cartes d alerte:
  - passage en vert automatique quand la valeur est a `0` (anomalies ouvertes, non conformes, alertes stock)
  - maintien en rouge uniquement lorsqu une alerte est presente

## [0.16.5] - 2026-04-10

### Added
- Sorties pharmacie (backoffice):
  - acquittement des sorties (`Acquitter` / `Prise en compte`)
  - marquage `Commande passee` (horodatage + auteur + note optionnelle)
  - synthese des sorties depuis la derniere commande pour preparation reappro
  - migration `027_add_pharmacy_ack_and_orders.sql`

### Changed
- Filtres sorties pharmacie:
  - ajout du filtre `Acquittement` (`A acquitter`, `Deja acquittees`, `Toutes`)
  - filtre par defaut positionne sur `A acquitter`

### Fixed
- Suivi pharmacie:
  - meilleure lisibilite de l etat de prise en compte dans la liste des sorties

## [0.16.4] - 2026-04-10

### Added
- Sortie pharmacie terrain:
  - option `Autre (hors liste)` deplacee en fin de liste de selection
  - support DB des sorties hors liste via migration `026_allow_free_label_outputs.sql`

### Changed
- Sortie pharmacie terrain:
  - pour `Autre`, la quantite n est plus saisie (forcee a `1`)
  - commentaire rendu obligatoire avec minimum 5 caracteres

### Fixed
- Gestion d erreur:
  - message explicite si la migration `026` n est pas appliquee (au lieu d un message generique)

## [0.16.3] - 2026-04-10

### Added
- Parametres application:
  - bouton `Imprimer A4` sur les QR `Verification terrain` et `Sortie pharmacie`
  - generation d une affiche d impression A4 portrait (QR grand format + lien)

### Changed
- BO Pharmacie (stock):
  - ajout d un acces rapide vers le formulaire terrain de sortie materiel (lien tokenise + caserne)
  - ouverture en nouvel onglet pour usage direct terrain

### Fixed
- Navigation BO Pharmacie:
  - clarification du rappel de lien pour eviter la confusion avec l historique des sorties

## [0.16.2] - 2026-04-10

### Added
- Backoffice:
  - vrai bouton `Retour` visible dans la banniere (remplace le lien discret)
  - sous-menu `Pharmacie` dans la navigation gauche (`Stock`, `Sorties`)
- Pharmacie:
  - migration `025_add_pharmacy_output_reason_requirement.sql`
  - nouveau parametre article `motif_sortie_obligatoire`
  - filtre rapide `Voir erreurs uniquement` sur la liste des articles

### Changed
- Sortie pharmacie terrain (QR):
  - selection article optimisee smartphone: recherche live + ajout direct en ligne
  - champ `Motif` masque quand non requis pour l article
  - champ `Motif` obligatoire quand l article l exige
  - numero d intervention obligatoire si motif = `utilise`
- Stock pharmacie:
  - sorties autorisees meme si stock insuffisant (stock negatif possible)

### Fixed
- Navigation:
  - parcours plus lisible sur les pages avec retour (bouton explicite)

## [0.16.1] - 2026-04-10

### Added
- Pharmacie:
  - champ de recherche instantanee sur `Articles existants` (filtre local par nom, sans rechargement)
  - compteur dynamique des lignes visibles apres filtrage

### Changed
- Configuration engin:
  - remplacement des encarts d aide repetitifs du mode `Presence (check)` par un bouton info `i`
  - aide contextualisee via popover cliquable (desktop + mobile), plus compacte
  - interaction popover amelioree (fermeture au clic exterieur, un seul ouvert)

## [0.16.0] - 2026-04-07

### Added
- UX mobile terrain V2 (par caserne):
  - nouveaux parametres `manager_admin/settings`:
    - densite mobile (`normal` / `compact`)
    - barre de progression fixe
    - brouillon automatique
    - duree de conservation du brouillon (heures)
    - aide champs manquants (scroll + surlignage)
- Brouillon terrain intelligent:
  - sauvegarde locale auto (`localStorage`) et reprise dans le meme creneau (matin/soir)
  - isolation par contexte: caserne + vehicule + poste + verificateur (compte ou nom saisi)
  - affichage du temps restant avant expiration du brouillon
- Zones:
  - migration `024_allow_same_zone_name_in_different_branches.sql` pour autoriser les memes noms de zone sur des branches differentes de l arbre.

### Changed
- Terrain:
  - mode `Presence` simplifie en reponse forcee `Present / Manquant`
  - quantite attendue re-affichee dans le libelle (ex: `2 Biseptine`)
  - commentaire global deplace en fin de formulaire
  - passe visuelle mobile (cartes allegees, progression plus lisible, etats repondu/manquant plus clairs)
- Configuration engin:
  - `Presence (check)` accepte de nouveau une quantite attendue (entier > 0)
  - aide explicite sur la hierarchie de zones (`Cellule > Sac PS > Sacoche rouge`)

### Fixed
- Zones:
  - correction du blocage sur les sous-zones homonymes (ancienne contrainte unique trop stricte)
  - message d erreur plus explicite en cas de doublon au meme niveau
- Validation terrain:
  - suppression du faux positif de non-reponse en mode presence
  - detection des controles incomplets plus fiable avant enregistrement

## [0.15.1] - 2026-04-05

### Changed
- Compte / securite:
  - changement du mot de passe depuis `Mon compte` via modal (plus ergonomique)
  - flux connecte: l ecran dedie `change_password_form` redirige vers `Mon compte`
  - gestion des erreurs/succes de changement de mot de passe integree a `Mon compte`
- Navigation caserne:
  - selecteur de caserne masque quand une seule caserne est accessible
  - affichage sidebar simplifie en mode mono-caserne

### Fixed
- Modal mot de passe:
  - les messages d erreur sont maintenant visibles directement dans la modal
- Isolation caserne:
  - un admin caserne ne voit plus les comptes `admin` plateforme
  - controle d acces renforce sur la fiche utilisateur `manager_users/show`

## [0.15.0] - 2026-04-05

### Added
- Utilisateurs:
  - nouvelle page dediee `Fiche utilisateur` (`manager_users/show`) pour l edition complete d un compte
  - actions groupees sur la liste des comptes:
    - activation en masse
    - desactivation en masse
    - changement de mot de passe en masse via modal
  - recherche rapide `nom / email` sur la liste comptes
  - confirmation forte de suppression definitive (modal + saisie `SUPPRIMER`)
- Multi-caserne / roles:
  - role local par caserne stocke dans `utilisateur_casernes.role_code`
  - support du role effectif par caserne en session manager et acces terrain
  - gestion de caserne prioritaire utilisateur (selection par defaut a la connexion)
- Base de donnees:
  - migration `023_add_role_to_user_caserne_membership.sql`

### Changed
- Gestion des comptes:
  - separation explicite entre:
    - role plateforme
    - role local par caserne
  - compte `admin` plateforme verrouille en edition (role systeme)
  - suppression utilisateur rendue definitive (au lieu de simple desactivation)
- Navigation utilisateurs:
  - la liste reste compacte; la fiche d edition n est plus affichee sous le tableau
  - ouverture fiche via clic ligne ou bouton `Ouvrir fiche`
- Securite perimetre caserne:
  - un administrateur de caserne ne voit et ne gere que les comptes de sa caserne active
  - les comptes admin plateforme sont masques pour les admins caserne
  - creation/edition limitees a la caserne active pour les admins caserne

### Fixed
- Suppression compte:
  - gestion robuste des references historiques (`verifications.utilisateur_id`, `anomalies.assigne_a`)
    pour eviter les erreurs de contrainte selon les schemas
- Auth multi-caserne:
  - coherences de role/session renforcees entre connexion, selection caserne et controles d acces

## [0.14.1] - 2026-04-03

### Added
- Pharmacie:
  - nouvelle migration `021_group_pharmacy_outputs.sql` pour ajouter `sortie_ref` sur les mouvements
  - historique des sorties regroupe par session de sortie (plusieurs articles en un enregistrement)
  - nouvelle page historique dediee avec filtres (`manager_pharmacy/outputs`)

### Changed
- Pharmacie:
  - tableau des articles aligne par colonnes fixes (`Nom`, `Unite`, `Quantite`, `Seuil`, `Etat`, `Action`)
  - libelles clarifies (`Stock` -> `Quantite`) et saisie admin en nombres entiers
  - bloc "Dernieres sorties" limite aux 10 dernieres sorties groupees + lien "Voir tout + filtres"
  - declarant obligatoire sur le formulaire terrain de sortie stock
  - menu sidebar: suppression du doublon "Mon compte" (garde uniquement le bouton bas)
  - activation du menu "Pharmacie" sur toutes les routes `manager_pharmacy/*`

### Fixed
- Pharmacie:
  - logique des alertes stock corrigee:
    - ignore les articles inactifs
    - ignore les seuils nuls/non renseignes
    - alerte uniquement si `quantite < seuil`
  - validation serveur entiers uniquement (admin + terrain) pour eviter les incoherences `0.5`
  - normalisation de saisie (`15,00` / `15.00`) acceptee si la valeur correspond a un entier

## [0.14.0] - 2026-04-03

### Added
- Verifications:
  - nouvelle vue mensuelle matin/soir (`verifications/monthly`) avec:
    - calendrier mensuel lisible
    - indicateurs globaux (volume, couverture, conformite)
    - detail journalier matin/soir
- Parametres application:
  - reglage par caserne de l heure de bascule matin/soir
    (`verification_evening_hour_caserne_{id}`)
  - valeur par defaut: `18h00`

### Changed
- Regle temporelle verifications:
  - `matin` = avant l heure configuree
  - `soir` = a partir de l heure configuree
- Flux QR vehicule terrain:
  - ecran postes simplifie (sans code poste, vehicule plus visible)
  - suppression de l etape numerotee et du retour vehicules en contexte QR engin
  - wording harmonise en `verification` (au lieu de `checklist`)
- Parc & materiel:
  - gestion du materiel recentree au niveau de l engin (zones + materiel sur la page engin)
  - suppression de la section globale `Materiel (controles)` de la page liste vehicules
  - libelles d action clarifies (`Configurer engin`, `Configurer zones & materiel`)

### Fixed
- Actions materiel:
  - redirections post-action (ajout/modification/suppression) maintenues sur la page de l engin cible
    pour eviter les allers-retours vers la page globale.

## [0.13.0] - 2026-04-02

### Added
- Parc vehicules:
  - fiche vehicule dediee (resume + QR engin)
  - ecran zones dedie par vehicule
  - duplication vehicule avec 3 niveaux:
    - vehicule seul
    - vehicule + zones
    - vehicule + zones + materiel
- QR terrain:
  - QR engin individuel (token par vehicule/caserne) avec actions:
    - generer / regenerer
    - supprimer
    - copier lien
    - ouvrir lien
    - imprimer QR (format A6)
- UI gestion parc:
  - tableau vehicules avec selection et actions de masse guidees
  - badges d etat QR engin (`Genere` / `Non genere`)

### Changed
- Vehicules:
  - creation/edition basees sur `type + indicatif` (construction nom automatique)
  - ouverture de la fiche vehicule en cliquant sur le nom
- Codes postes:
  - normalisation/validation stricte (majuscules, sans espaces, max 10 caracteres) front + back

### Fixed
- Suppression vehicule:
  - ajout du mode `Supprimer tout` (vehicule + zones + materiel + historique) en transaction
  - correction de suppression des hierarchies de zones (suppression feuilles -> parents)
  - garde-fou: `Supprimer tout` interdit si vehicule actif
- UX suppression:
  - split button (`Supprimer` direct + menu `Supprimer tout`)
  - confirmation danger dediee pour `Supprimer tout` (sans popup navigateur en doublon)

## [0.12.0] - 2026-04-01

### Added
- Multi-caserne:
  - migration `019_add_caserne_multitenant.sql` (tables `casernes`, `utilisateur_casernes`)
  - selection de caserne obligatoire a la connexion gestionnaire pour les comptes lies a plusieurs casernes
  - switch de caserne active depuis le shell backoffice
- Authentification:
  - nouvel ecran `manager_select_caserne.php`
  - controle d acces verificateur sur la caserne du lien QR
- Utilisateurs:
  - affectation multi-caserne depuis le CRUD utilisateurs (selection multiple)

### Changed
- Isolation des donnees par caserne:
  - scope `caserne_id` applique aux modules parc/materiel, verifications, anomalies et pharmacie
  - liens invites (terrain + pharmacie) incluent la caserne active
- Seed demo:
  - `001_demo_data.sql` compatible schema multi-caserne

## [0.11.1] - 2026-03-30

### Added
- Rien pour le moment.

### Fixed
- Checklist terrain:
  - correction du calcul de progression pour les controles `Check` (plus de comptage automatique au chargement)

## [0.11.0] - 2026-03-30

### Added
- Parametres applicatifs en base de donnees:
  - migration `018_create_app_settings.sql`
  - stockage des tokens QR (`field_qr_token`, `pharmacy_qr_token`) dans `app_settings`

### Changed
- Lecture des parametres sensibles:
  - priorite a `app_settings`, fallback sur `.env` si la table est absente/non migree
- Docker:
  - retour du montage `.env.docker` en lecture seule (plus besoin d ecriture runtime)
- Documentation:
  - base de connaissance QR mise a jour pour le stockage BDD et le depannage SQL

### Fixed
- Regeneration QR administration:
  - suppression de la dependance aux droits d ecriture du fichier `.env`
  - messages d erreur alignes sur les causes BDD (`settings_store_unavailable`, `settings_store_failed`)

## [0.10.3] - 2026-03-29

### Added
- Base de connaissance de depannage QR:
  - section README dediee aux erreurs de permissions `.env`
  - section DEPLOYMENT dediee au diagnostic et correctifs

### Changed
- Docker:
  - montage `.env.docker` en ecriture dans `docker-compose.yml` pour permettre la regeneration des tokens QR
- Parametres application:
  - message d aide detaille dans l interface en cas d erreur d ecriture `.env`

## [0.10.2] - 2026-03-29

### Added
- Rien pour le moment.

### Changed
- Roles et acces:
  - permissions du role `Administrateur` systeme affichees en grise et non modifiables
  - verrou backend pour refuser toute tentative de mise a jour des permissions du role systeme
- Docker/documentation:
  - montage `.env.docker` en ecriture dans `docker-compose.yml` pour permettre la regeneration QR
  - ajout d une base de connaissance de depannage (README + DEPLOYMENT)

### Fixed
- Parametres application (QR):
  - gestion defensive des permissions fichier `.env` pour eviter les warnings PHP
  - retour utilisateur propre `env_write_failed` en cas de droits insuffisants
  - message d aide detaille directement dans l interface administration

## [0.10.1] - 2026-03-29

### Added
- Rien pour le moment.

### Changed
- Parametres application (administration):
  - boutons `Generer/Regenerer lien + QR` pour verification terrain et pharmacie
  - regeneration = nouveau token, nouveau lien, nouveau QR
  - confirmation explicite avant regeneration (anciens liens/QR invalides)

## [0.10.0] - 2026-03-29

### Added
- Parametres application (administration):
  - generation des liens invites (terrain + pharmacie)
  - generation des QR codes associes directement depuis l administration

## [0.9.0] - 2026-03-29

### Changed
- Configuration des controles (manager):
  - nouveaux libelles metier du type de reponse: `Valeur`, `Check`, `Choix`
  - `Check` utilise le flux presence/absence
  - `Choix` utilise le flux fonctionnel/non fonctionnel
- Checklist terrain:
  - affichage adapte selon le type choisi:
    - `Check` => grosse case a cocher `Objet present`
    - `Choix` => `Fonctionnel / Non fonctionnel`
    - `Valeur` => saisie numerique avec seuils
- Backoffice:
  - detail verification integre au shell commun (menu lateral + bandeau)
  - nouvel espace `Mon compte` (profil + changement mot de passe)
  - menu `Administration` recentre sur le parametrage applicatif (roles/utilisateurs uniquement)
  - `Mon compte`: edition du profil (nom/email) et suppression du lien "retour dashboard"
  - changement de mot de passe accessible depuis `Mon compte` (plus reserve a la premiere connexion)
  - expiration de session gestionnaire configurable via `MANAGER_SESSION_TTL_MINUTES`
  - edition profil en mode `Editer` (lecture par defaut, champs activables a la demande)
  - ajout d un menu `Parametres application` dans `Administration`

### Fixed
- `Mon compte`:
  - correction du crash `Call to undefined method ManagerController::redirect()`

## [0.8.5] - 2026-03-28

### Added
- Rien pour le moment.

## [0.8.4] - 2026-03-28

### Added
- Rien pour le moment.

### Fixed
- Checklist terrain (controles mesure):
  - blocage des valeurs hors plage configuree (`min/max`) cote formulaire
  - validation serveur renforcée pour refuser l'enregistrement hors plage
- Vue anomalies:
  - filtre statut par defaut positionne sur `Actives (A traiter + En cours)`

## [0.8.3] - 2026-03-28

### Added
- Rien pour le moment.

### Changed
- Checklist terrain (controles statut/quantite):
  - suppression du choix `NA`
  - passage a 2 choix explicites: `Present` / `Absent`
  - commentaire obligatoire si `Absent`
- Controles quantite/mesure:
  - saisie et configuration passees en unites entieres (pas de decimales)

### Fixed
- Validation serveur de saisie alignee sur l'UI:
  - seules les valeurs `ok` et `nok` sont acceptees pour les controles statut/quantite
- Validation serveur des valeurs numeriques:
  - rejet des decimales pour les controles quantite/mesure

## [0.8.2] - 2026-03-28

### Added
- Rien pour le moment.

### Fixed
- Checklist terrain (controles quantite):
  - fusion de la quantite attendue avec le libelle article sur une seule ligne lisible mobile
  - suppression du bloc dedie "quantite attendue" pour eviter l'information en double

## [0.8.1] - 2026-03-28

### Added
- Rien pour le moment.

### Fixed
- Affichage de version:
  - priorite au fichier `VERSION` (release courante)
  - `APP_VERSION` utilise uniquement en fallback
  - suppression de la valeur figee `APP_VERSION=0.2.0` dans `.env.docker`

## [0.8.0] - 2026-03-28

### Added
- Backoffice shell unifie:
  - navigation laterale desktop
  - navigation mobile persistante
  - entete standardise avec actions communes
- Modularisation des vues gestionnaire via partials partages:
  - `public/views/partials/backoffice_shell_top.php`
  - `public/views/partials/backoffice_shell_bottom.php`

### Changed
- Ecrans backoffice migres vers le shell commun (PC + smartphone):
  - dashboard
  - administration
  - roles et acces
  - utilisateurs
  - anomalies
  - historique
  - pharmacie
  - configuration types
  - configuration vehicules

### Fixed
- Visibilite du module Administration alignee sur la permission `users.manage`:
  - bouton lateral masque pour les profils non autorises
  - acces direct a `/manager_admin/menu` protege avec la meme permission

## [0.7.1] - 2026-03-28

### Fixed
- Dashboard gestionnaire:
  - correction des liens invites (QR) pour utiliser l'hote public courant de la requete
  - fallback sur `APP_URL` si le contexte HTTP n'est pas disponible

## [0.7.0] - 2026-03-28

### Added
- Module roles et acces:
  - creation de roles (admin systeme non supprimable)
  - association des roles aux fonctionnalites manager
  - ecran dedie de gestion des permissions
- Module utilisateurs (CRUD):
  - creation de comptes
  - modification profil (nom, email, role, statut)
  - desactivation securisee (pas de suppression physique)
  - reset mot de passe avec changement obligatoire a la prochaine connexion
- Menu administration dedie avec navigation centralisee.

### Database
- Migration `017_create_roles_permissions.sql`.

## [0.6.0] - 2026-03-27

### Added
- Assignation des anomalies:
  - responsable assigne par anomalie (`assigne_a`)
  - action rapide "M'assigner cette anomalie"
  - filtres par assignation (y compris non assignees)
- Module pharmacie separe:
  - gestionnaire: CRUD articles stock (stock actuel, unite, seuil alerte)
  - terrain QR: declaration rapide des sorties de stock
  - declaration multi-articles en un seul formulaire
  - historique des mouvements de sortie
- Liens "acces invites" dans le dashboard gestionnaire:
  - verification terrain
  - sortie pharmacie
  - copie rapide des URLs QR

### Changed
- Vue anomalies gestionnaire simplifiee et plus lisible (cartes, badges, filtres).
- Statuts anomalies harmonises (`cloturee` consolidee vers `resolue`).
- Verification terrain:
  - pour les controles `quantite`, saisie simplifiee en `OK/NOK/NA`
  - la quantite attendue reste informative (pas de valeur obligatoire a saisir)
- Acces QR verification: retour en mode sans authentification obligatoire.
- UX pharmacie:
  - message de confirmation explicite apres enregistrement
  - formulaire masque apres succes avec action "nouvelle sortie"
  - backoffice pharmacie recompose pour une lecture plus claire.

### Database
- Migration `015_add_assignee_to_anomalies.sql`.
- Migration `016_create_pharmacy_module.sql`.

## [0.5.0] - 2026-03-27

### Added
- Controles quantitatifs et de mesure:
  - type de saisie `statut`, `quantite`, `mesure`
  - valeur attendue, unite, seuil minimum/maximum configurables
  - calcul automatique `OK/NOK` pour les saisies numeriques
- Sous-zones hierarchiques dans les engins (`zone parent > sous-zone`).

### Changed
- Administration materiel:
  - parametrage des champs quantitatifs/mesures dans le CRUD controles
  - affichage/contextualisation des champs selon le type de saisie
  - selecteur de zone avec chemin complet hierarchique
- Verification terrain:
  - saisie numerique pour les controles `quantite/mesure`
  - regroupement par chemin de zone hierarchique
- Detail/export des verifications:
  - affichage des valeurs relevees pour les controles non `statut`
  - affichage des chemins de zones hierarchiques.

### Database
- Migration `013_add_quantitative_controls.sql`.
- Migration `014_add_zone_hierarchy.sql`.

## [0.4.0] - 2026-03-25

### Changed
- Ergonomie administration refondue (gestionnaire):
  - navigation plus claire entre `Types & postes` et `Vehicules & zones`
  - listes avec recherche et filtres instantanes (types, postes, vehicules, zones, materiel)
  - section materiel en mode edition tableau plus lisible (entetes, actions alignees)
- Feedback UX en administration:
  - toasts succes/erreur auto-dismiss
  - etats de chargement sur boutons (`Ajout`, `Maj`, `Suppression`)
  - prevention du double-submit
  - confirmations de suppression homogenes

## [0.3.2] - 2026-03-25

### Changed
- Configuration Docker/documentation: suppression de `APP_VERSION` dans l'exemple d'environnement pour activer le versionning automatique via le fichier `VERSION`.
- Documentation de deploiement mise a jour pour recommander de ne pas fixer `APP_VERSION`.

## [0.3.1] - 2026-03-25

### Fixed
- Correction du filtrage dynamique en configuration materiel gestionnaire:
  - reconstruction visible des listes `postes` et `zones` selon le vehicule selectionne
  - comportement fiable sur navigateurs ou `hidden/disabled` sur `<option>` etait peu lisible

## [0.3.0] - 2026-03-25

### Added
- Compte administrateur initial sur installation vierge (`admin` / `admin`) avec changement de mot de passe obligatoire a la premiere connexion.
- Ecran dedie de changement de mot de passe gestionnaire.
- Separation de la configuration gestionnaire en 2 pages:
  - `Types d engins` (types + postes)
  - `Vehicules` (vehicules + zones + materiel)

### Changed
- Ergonomie de la grille materiel (controles): actions Modifier/Supprimer alignees sur chaque ligne.
- Filtrage dynamique dans la configuration materiel:
  - postes filtres selon le type du vehicule selectionne
  - zones filtrees selon le vehicule selectionne
- Messages d erreur de suppression plus explicites en cas de dependances metier (FK).

## [0.2.0] - 2026-03-24

### Added
- Authentification verificateur (optionnelle) avec session terrain.
- Nouvelle vue de connexion terrain (`field_login`) et logout verificateur.
- Migration `011_add_utilisateur_to_verifications.sql` pour tracer l'utilisateur de verification.
- Seed `003_verificateur_user.sql`.
- Affichage automatique de la version applicative via `VERSION` / `APP_VERSION`.
- Script de release `scripts/release.ps1` pour automatiser `VERSION + CHANGELOG`.

### Changed
- Parcours terrain mobile-first refondu (`home`, `postes`, `controles`, `verification_saved`).
- Checklist tactile: boutons OK/NOK/NA plus lisibles, progression live, CTA sticky.
- Saisie commentaire conditionnelle en NOK (UI).
- Auth terrain assouplie: acces QR sans login obligatoire (login verificateur reste disponible).
- Seeds utilisateurs rendus idempotents (`ON DUPLICATE KEY UPDATE`) pour reset credentials.

### Fixed
- Correction UX: etat visuel de selection OK/NOK/NA.
- Stabilisation de la migration hierarchique des controles avec donnees historiques.

## [0.1.0] - 2026-03-24

### Added
- Separation fonctionnelle en 2 espaces:
  - `terrain` (acces QR, verification mobile)
  - `gestionnaire` (auth, dashboard, pilotage)
- Workflow complet de verification:
  - formulaire POST
  - creation `verifications` + `verification_lignes`
  - creation d'anomalie automatique sur `NOK`
- Espace gestionnaire:
  - historique filtre des verifications
  - detail verification
  - export imprimable/PDF
  - suivi des anomalies (filtres + mise a jour statut/priorite)
- CRUD gestionnaire:
  - vehicules
  - postes
  - controles
  - zones (hierarchie par vehicule)
- Base de donnees:
  - migration `007_create_anomalies.sql`
  - migration `008_create_utilisateurs.sql`
  - migration `009_create_zones.sql`
  - migration `010_link_controles_to_vehicle_zone.sql`
  - seed `002_manager_user.sql`

### Changed
- Routing principal normalise vers `index.php?controller=X&action=Y` avec compatibilite legacy.
- Validation metier renforcee pour eviter les incoherences `vehicule/poste`.
- Hierarchie metier formalisee:
  - type d'engin -> engins
  - engin -> zones + postes compatibles
  - controle lie a `vehicule + poste + zone`

### Fixed
- Correction d'un conflit Git dans `README.md`.
- Correction du crash si la table `anomalies` est absente (mode degrade).
- Correction migration `010` pour remapper les historiques (`verification_lignes`) avant suppression des anciens controles.
