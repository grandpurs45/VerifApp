# Guide Admin VerifApp (v1.4)

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
  - parametres et groupes de notifications
  - roles et acces
  - utilisateurs
  - journal des connexions et journal des ouvertures QR separes

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
  - groupes de destinataires independants des roles
  - ciblage general ou specifique par engin
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

En cas d erreur a la creation, les champs non sensibles sont conserves et les erreurs sont affichees sous les champs concernes.

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
- audit:
  - ouvrir `Administration > Audit securite`
  - verifier le compteur du journal QR et la caserne de chaque trace
  - les tokens ne sont jamais stockes en clair, uniquement leur empreinte

## 7. Notifications et anomalies
- Un role controle les permissions applicatives.
- Un groupe de notifications controle uniquement les destinataires des alertes.
- Un utilisateur peut appartenir a plusieurs groupes de notifications.
- Le ciblage d un engin surcharge la regle generale pour les anomalies de cet engin.
- Une anomalie active identique n est pas dupliquee: une occurrence terrain est ajoutee.
- Apres resolution, une nouvelle non-conformite peut creer une nouvelle anomalie.
- Les emails d anomalies indiquent l engin, le poste, le declarant et les controles NOK.
- Regler le seuil de rappel dans `Administration > Parametres notifications`.
- Le premier signalement envoie un mail immediat. Une anomalie connue envoie ensuite un rappel lorsque son compteur atteint un multiple du seuil.

## 8. Preparation des engins
- Un nouvel engin est exclu des verifications par defaut.
- Saisir ses postes, zones et controles avant de l activer.
- Activer `Inclure cet engin dans les verifications` depuis sa fiche.
- Choisir `1 fois par jour` ou `Matin et soir` dans la frequence attendue.
- Un engin exclu ne figure pas sur le formulaire mobile et ne compte pas dans les objectifs journaliers.
- En mode `Matin et soir`, chaque poste compte pour deux couvertures quotidiennes. Une saisie en garde 24 h couvre les deux.
- Reordonner les zones d un meme niveau avec la poignee de glisser-deposer.

## 9. Securite operationnelle
- garder `Mode debug` desactive en prod.
- forcer des mots de passe robustes.
- surveiller `Audit securite` regulierement.
- exporter les logs connexions pour revue periodique.
- limiter les comptes admin plateforme.

## 10. Backup / restore admin
Depuis la racine projet:

```bash
php scripts/backup.php --out=backups --name=manual
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force
```

Avec restauration `.env`:

```bash
php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force --restore-env
```

## 11. Checklist admin hebdo
- verifier anomalies ouvertes.
- verifier alertes stock pharmacie.
- verifier sorties non acquittees.
- verifier audit connexions (echecs/verrouillages).
- verifier le journal des ouvertures QR.
- verifier presence d au moins 1 backup recent.

## 12. Alertes de stock pharmacie
- Configurer un seuil strictement positif sur chaque article concerne.
- Activer `Surveiller le seuil` pour recevoir aussi le warning lorsque le stock est egal au seuil.
- Le niveau critique est declenche lorsque le stock passe sous le seuil, meme si le warning n est pas active.
- Configurer les destinataires de `Pharmacie: seuil warning atteint` et `Pharmacie: stock critique` dans les parametres notifications.
- Une alerte identique n est pas repetee tant que l article reste au meme niveau.
