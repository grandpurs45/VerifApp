# Guide Admin VerifApp (v1.9)

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
- Verifications > Configuration > Types & postes: types d engins et postes de verification.
- Verifications > Configuration > Vehicules & materiel: indicatifs, zones/sous-zones, materiel, frequence, QR et destinataires des anomalies.
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

### Modifier l identite d un vehicule

Depuis `Verifications > Configuration > Vehicules & materiel > Fiche vehicule`, ouvrir `Modifier le type et l indicatif`, choisir le type et saisir l indicatif, puis enregistrer.
Le meme bouton est accessible depuis les actions de la liste des vehicules et depuis `Configurer engin`; il ouvre directement le formulaire de la fiche.
Le vehicule conserve son identifiant, ses zones, ses materiels, ses QR codes, ses destinataires et son historique. Les reglages de verification ne changent pas.
Si le type change, les materiels sont rattaches aux postes equivalents du type cible (par exemple Conducteur VSAV vers Conducteur VSSUAP). Les postes manquants sont crees dans ce type avec le bon suffixe, sans modifier les anciens postes ni les verifications passees.
Pour un vehicule deja converti dont les postes copies automatiquement portent encore l ancien suffixe, reenregistrer le meme type et le meme indicatif depuis sa fiche apres installation du correctif. Le rattachement du materiel est repare pour ce vehicule uniquement; les postes historiques ne sont pas renommes.
La couverture mensuelle rattache les anciens postes aux postes actuels a partir du materiel conserve dans les lignes de verification. Les creneaux sont fusionnes sans doublon, avec prise en compte des gardes de 24 h. Si le materiel d une ancienne verification est reparti entre plusieurs postes actuels, aucune equivalence n est inventee.
Le nom doit rester unique pour ce type dans la caserne. En cas de refus, les champs saisis sont conserves.

### Mesures avec curseur

Dans `Verifications > Configuration > Vehicules & materiel > Fiche vehicule`, choisir `Valeur mesuree avec curseur` pour le materiel concerne.
Renseigner l unite et au moins un seuil de conformite, puis les bornes et le pas du curseur.
Exemple pour une bouteille: unite `bars`, echelle `0` a `300`, pas `10`, conformite de `50` a `200`.
Le pas est un entier positif qui doit diviser exactement l amplitude de l echelle.
Une valeur de `30` est alors enregistrable mais genere une non-conformite.
Pour rendre aussi `50` non conforme, cocher `Non conforme si la valeur est inferieure ou egale au minimum`.
Sans cette option, une valeur exactement egale au minimum reste conforme. L option est disponible egalement pour les mesures classiques et necessite la migration `044_add_exclusive_minimum_threshold.sql`.

Sur le terrain, la valeur reste vide jusqu a une action explicite sur le curseur tactile. La valeur selectionnee est affichee au-dessus du curseur, sans champ modifiable; le pas configure est respecte automatiquement.
Les mesures classiques existantes ne sont pas converties automatiquement.
La migration `043_add_slider_measure_controls.sql` doit etre appliquee avant de configurer ce type.

### Protection contre les envois multiples

Chaque formulaire terrain porte une cle unique de soumission. Pendant l envoi, le bouton est desactive. Si le meme formulaire est retransmis, la verification deja enregistree est reutilisee sans nouvelle anomalie, occurrence ni notification.
Une nouvelle ouverture du formulaire permet une nouvelle verification legitime. Les reponses modifiees avec une cle deja enregistree sont refusees pour eviter un enregistrement ambigu.
La migration `045_add_verification_submission_keys.sql` est requise. Les formulaires ouverts avant la mise a jour doivent etre recharges.
Les doublons historiques ne sont pas supprimes automatiquement: leur traitement necessite une verification au cas par cas.

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
- Dans la vue mensuelle, `Verifications enregistrees` compte les formulaires et `Creneaux attendus / jour` l objectif de couverture. Trois postes verifies en garde 24 h donnent 3 formulaires et 6 creneaux couverts sur 6.
- Le `Taux de couverture` est distinct de la conformite du materiel: une verification non conforme couvre tout de meme son creneau.
- Reordonner les zones d un meme niveau avec la poignee de glisser-deposer.

### Graphique annuel de couverture

Depuis `Historique > Vue mensuelle`, ouvrir `Vue annuelle`, choisir l annee et le vehicule, puis appliquer le filtre.
`Comparer avec` superpose une autre annee disposant de verifications dans le perimetre selectionne; le champ reste desactive lorsqu une seule annee est disponible.
Les mois futurs n ont pas de point. Le mois courant est calcule sur les jours ecoules; les mois passes utilisent le mois complet. Le taux annuel est pondere par les creneaux attendus et reprend les objectifs de la configuration actuelle, comme la vue mensuelle.
Les taux du tableau et les points du graphique ouvrent les details du mois concerne. L annee comparee a sa propre courbe et ses propres liens.

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

## 13. Reception de commande pharmacie
- Ouvrir `Pharmacie > Reception` ou `Receptionner une commande` depuis la page Stock.
- Rechercher les articles recus, les ajouter et renseigner les quantites.
- La liste affiche le stock actuel et le stock prevu apres reception. Le recapitulatif distingue les unites et le bouton de validation est actif uniquement pour des quantites entieres positives.
- Valider la reception. Le formulaire ne propose pas de note ni de changement de commande de reference.
- Les quantites recues sont ajoutees aux stocks de la caserne active; les alertes de seuil sont reevaluees.
- La page Sorties conserve l historique et la synthese de commande, sans le formulaire de reception.

## 14. Lecture et modification du stock pharmacie
- Le tableau affiche l article, son stock disponible, le seuil et son statut. Les reglages se trouvent dans la fenetre ouverte par le crayon.
- Les indicateurs Articles actifs, Sous le seuil et Au seuil filtrent la liste; un second clic retire le filtre. La recherche se combine avec le filtre selectionne.
- Le menu Autres actions permet de desactiver ou reactiver un article et de le supprimer avec confirmation. La suppression avec historique reste reservee aux articles inactifs.
- Une modification refusee reouvre le formulaire de l article concerne avec les valeurs saisies.

## 15. Partage public des statistiques

### Generer et partager
- Selectionner la caserne active dans le backoffice.
- Ouvrir `Administration > Lien public de suivi` ou `Partager le suivi de la caserne` depuis les vues mensuelle et annuelle des verifications. Le droit `users.manage` est requis pour gerer ce lien.
- Cliquer sur `Generer le lien`, puis `Copier le lien`. Aucun acces public n est active automatiquement lors de la mise a jour.
- Le lien ne demande pas de connexion. Il donne un acces en lecture seule aux statistiques de cette caserne, avec filtres mois, vehicule, annee et comparaison annuelle.
- `Regenerer le lien` invalide immediatement l ancien et fournit une nouvelle adresse a redistribuer. `Desactiver` retire l acces public jusqu a une nouvelle generation.

### Indicateurs disponibles
- Planning journalier identique a la vue mensuelle et taux de couverture du mois.
- Journees a 100 %, creneaux couverts/attendus, formulaires enregistres et verifications comportant au moins une non-conformite.
- Graphique annuel des taux mensuels, comparaison optionnelle et tableau detaille. Les mois futurs sont exclus, le mois courant porte sur les jours ecoules. Les objectifs reposent sur la configuration actuelle des vehicules, comme dans le backoffice.
- Anomalies: signalements terrain (chaque controle NOK, y compris une nouvelle occurrence), nouvelles anomalies ouvertes pendant le mois, anomalies actuellement resolues/cloturees dont la date de resolution est dans le mois.
- Etat actuel, toutes dates confondues: anomalies a traiter, en cours et actives de priorite haute/critique. Ce stock n est pas un historique mensuel des changements de statut.
- Le filtre vehicule s applique aux verifications et aux anomalies. Les vehicules exclus des verifications ne sont pas proposes; sans filtre, les compteurs d anomalies concernent la caserne entiere.

### Confidentialite et exploitation
- Toute personne possedant le lien peut le consulter et le transmettre. Utiliser HTTPS et regenerer ou desactiver le lien en cas de diffusion non souhaitee.
- Seuls les agregats et les libelles de caserne/vehicules sont publics: aucun nom d agent, commentaire terrain, responsable assigne ou fiche individuelle n est expose.
- La page demande aux moteurs de ne pas l indexer et interdit sa mise en cache; cela ne remplace pas la confidentialite du lien. Les assets sont locaux pour ne pas transmettre le lien a un fournisseur tiers.
- Le jeton figure dans l URL: ne pas publier les journaux de requetes du serveur ou du reverse proxy. Une regeneration ne retire pas les captures deja realisees par les destinataires.
- Aucune migration supplementaire: les liens sont conserves dans la table `app_settings` existante, un par caserne.
- Pour les contributeurs uniquement, apres modification des classes des vues publiques, reconstruire le CSS versionne avec `powershell -File scripts/build-public-statistics-css.ps1` (Node/npm requis pour ce build uniquement). Aucun build npm n est necessaire en production.
