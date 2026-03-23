# Versioning Policy

Ce projet suit **Semantic Versioning**: `MAJOR.MINOR.PATCH`.

## Regles
- `MAJOR`: changement incompatible (routes, schema DB incompatible, comportement casse).
- `MINOR`: nouvelle fonctionnalite compatible (nouvel ecran, nouvelle route, nouveau module).
- `PATCH`: correction compatible (bugfix, UX mineure, perf, typo).

## Cycle de release
1. Travailler sur `main` (ou feature branch si necessaire).
2. Mettre a jour `CHANGELOG.md` dans `Unreleased`.
3. Au moment de livrer:
   - creer une section version datee dans `CHANGELOG.md` (ex: `## [0.3.0] - 2026-03-24`)
   - creer un tag git `vX.Y.Z`
4. Vider `Unreleased` pour le cycle suivant.

## Convention de commits (recommandee)
- `feat:` nouvelle fonctionnalite
- `fix:` correction
- `refactor:` refonte sans changement fonctionnel
- `docs:` documentation
- `chore:` maintenance

Exemples:
- `feat: add manager anomalies workflow`
- `fix: prevent crash when anomalies table is missing`

## Regles de migrations SQL
- Une migration appliquee en production ne doit pas etre modifiee.
- Si une correction est necessaire, creer une nouvelle migration corrective.
- Toujours noter les impacts schema dans `CHANGELOG.md`.
