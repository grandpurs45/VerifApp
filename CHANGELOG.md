# Changelog

Tous les changements notables de ce projet sont documentes dans ce fichier.

Le format suit Keep a Changelog et Semantic Versioning.

## [Unreleased]

### Added
- Rien pour le moment.

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
