# Guide Admin VerifApp (v1)

Ce guide cible les administrateurs plateforme et caserne.

## 1. Roles admin
- `Administrateur` (plateforme):
  - gere toutes les casernes
  - gere roles/permissions globaux
  - gere utilisateurs globaux
  - gere parametres globaux (timezone, politique mot de passe, debug, email SMTP)
- `Administrateur_caserne`:
  - administre uniquement la caserne active
  - ne voit pas les comptes admin plateforme
  - ne peut pas modifier les parametres globaux reserves plateforme

## 2. Menus et modules
- Dashboard: indicateurs par module selon permissions.
- Anomalies: suivi, assignation, priorites.
- Historique: verifications detaillees, exports.
- Parc & materiel:
  - types et postes
  - vehicules, zones/sous-zones, materiel
  - QR vehicule
- Pharmacie:
  - stock articles
  - sorties
  - inventaires
  - statistiques
- Administration:
  - parametres application
  - roles et acces
  - utilisateurs

## 3. Parametres application
Page: `Administration > Parametres application`

Principaux reglages:
- Fuseau horaire global.
- Session gestionnaire (TTL, scope caserne).
- Politique mot de passe (longueur + complexite).
- Mode debug global (`active`/`desactive`).
- Notifications:
  - in-app
  - email (mail()/SMTP)
- Regles UX terrain (densite, brouillons, scroll champs manquants).
- Reglages QR (generation/regeneration + messages impression).

## 4. Gestion utilisateurs
Page: `Administration > Utilisateurs`

Bonnes pratiques:
- creer un compte nominatif par personne.
- affecter au moins 1 caserne par compte.
- verifier le role local par caserne.
- eviter l usage quotidien du compte admin plateforme.

Actions disponibles:
- creation utilisateur
- edition fiche utilisateur
- reset mot de passe
- activation/desactivation
- suppression (avec confirmation forte)
- actions bulk (activation/desactivation/reset MDP)

## 5. Gestion roles et permissions
Page: `Administration > Roles et acces`

Principes:
- un role = ensemble de permissions applicatives.
- le role admin systeme est verrouille.
- tester les changements avec un compte de test avant prod.

Permissions critiques:
- `users.manage` (administration comptes et roles)
- `assets.manage` (parc, zones, materiel)
- `pharmacy.manage` (pharmacie complete)
- `anomalies.manage`, `verifications.history`, `dashboard.view`

## 6. Exploitation QR
- QR caserne:
  - verification terrain
  - sortie pharmacie
  - inventaire mobile
- QR vehicule:
  - acces direct verification d un engin
- regeneration:
  - invalide les anciens QR/lien
  - reimprimer les affiches apres regeneration

## 7. Securite operationnelle
- garder `Mode debug` desactive en prod.
- forcer des mots de passe robustes.
- surveiller `Audit securite` regulierement.
- exporter les logs connexions pour revue periodique.
- limiter les comptes admin plateforme.

## 8. Backup / restore admin
Depuis la racine projet:

```bash
php scripts/backup.php --out=backups --name=manual
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force
```

Avec restauration `.env`:

```bash
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force --restore-env
```

## 9. Checklist admin hebdo
- verifier anomalies ouvertes.
- verifier alertes stock pharmacie.
- verifier sorties non acquittees.
- verifier audit connexions (echecs/verrouillages).
- verifier presence d au moins 1 backup recent.
