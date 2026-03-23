# Changelog

Tous les changements notables de ce projet sont documentes dans ce fichier.

Le format suit Keep a Changelog et Semantic Versioning.

## [Unreleased]

### Added
- Rien pour le moment.

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
