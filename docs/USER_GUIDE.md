# Guide Utilisateur VerifApp (v1)

Ce guide couvre l usage quotidien terrain et backoffice non admin.

## 1. Profils
- Verificateur terrain:
  - lance une verification via QR
  - saisit les controles
  - enregistre la verification
- Gestionnaire:
  - consulte dashboard
  - suit anomalies/historique
  - gere modules selon ses droits (parc, pharmacie, etc.)

## 2. Verification terrain (QR)
Etapes:
1. Scanner QR officiel de la caserne (ou QR vehicule).
2. Choisir vehicule puis poste (si non preselectionne).
3. Renseigner tous les controles.
4. Enregistrer la verification.

Types de reponses possibles:
- Presence: `Present` / `Manquant`
- Choix: `Fonctionnel` / `Non fonctionnel`
- Valeur mesuree: valeur numerique avec seuil min/max

Comportements utiles:
- brouillon local auto (si active) sur le meme creneau.
- progression visible en tete.
- zone incomplete surlignee avant envoi.

## 3. Sortie pharmacie terrain (QR)
Etapes:
1. Scanner QR `Sortie pharmacie`.
2. Rechercher un article.
3. Ajouter la/les ligne(s) sortie.
4. Renseigner declarant (obligatoire).
5. Enregistrer.

Cas particuliers:
- `Autre (hors liste)`:
  - commentaire obligatoire (minimum 5 caracteres).
- articles avec sortie sur compte-rendu:
  - motif obligatoire.
  - numero intervention requis selon regle de l article.

## 4. Inventaire pharmacie terrain (QR)
Etapes:
1. Scanner QR `Inventaire mobile`.
2. Saisir une quantite pour chaque article demande.
3. Ajouter au besoin des lignes `materiel en trop`.
4. Soumettre l inventaire.

## 5. Dashboard gestionnaire
- indicateurs par module (anomalies, verifications, pharmacie).
- tuiles et blocs affiches selon permissions du compte.
- la caserne active est visible dans la barre laterale.

## 6. Anomalies
- creer automatiquement si controle NOK.
- assigner un responsable.
- changer priorite/statut.
- commenter le suivi.

## 7. Mon compte
- modifier nom/email.
- changer mot de passe.
- regler abonnements notifications (in-app/email) selon droits.

## 8. Bonnes pratiques terrain
- saisir juste apres la verification reelle.
- ne pas laisser de champ vide.
- commenter tout `Manquant`/`Non fonctionnel`.
- en cas de doute, consigner et signaler plutot que ignorer.

## 9. Aide rapide
- si acces refuse: verifier QR officiel et caserne active.
- si session expiree: se reconnecter avant de reprendre.
- si notification email absente: verifier dossier spam + parametres admin.
