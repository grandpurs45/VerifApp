# Roadmap VerifApp

Cette roadmap donne une direction produit. Les versions futures et leur contenu peuvent evoluer selon les retours terrain.

## Livre

### v1.4.0
- groupes de notifications independants des roles
- routage des anomalies par groupe, role ou utilisateur
- emails d anomalies detailles
- regroupement des anomalies actives et historique des occurrences
- activation des verifications par engin
- ordre des zones par glisser-deposer
- formulaire de creation utilisateur plus robuste
- journal des ouvertures QR

### v1.4.1
- rappels d anomalies connues selon un seuil configurable
- frequence de verification quotidienne ou matin et soir par vehicule
- couverture mensuelle par poste et par creneau
- prise en compte des gardes de 24 h sur les deux creneaux

### v1.5.0
- journaux connexions et QR separes
- filtres et export CSV dedies au journal QR
- IP client distinguee de l IP reverse proxy
- notifications pharmacie aux seuils warning et critique avec anti-doublon
- emails de seuil pharmacie detailles

### v1.5.1
- repli sur le role du compte lorsque le role de caserne est vide
- message et journal explicites pour les refus d acces gestionnaire
- verification de l enregistrement du mot de passe et conservation de l IP client
- tests de regression du changement de mot de passe initial

### v1.6.0
- formulaire d ajout d article pharmacie en fenetre modale
- page et menu dedies a la reception de commandes pharmacie
- stock prevu apres reception et recapitulatif par unite
- interface de reception adaptee au mobile
- reception simplifiee sans note ni modification de commande de reference

### v1.7.0
- stock pharmacie en lecture avec modification a la demande
- filtres depuis les indicateurs et actions secondaires regroupees
- interface adaptee au mobile et en-tete de tableau fixe sur desktop
- conservation de la saisie apres un refus d enregistrement
- changement de statut isole du stock, avec test de regression

### v1.8.0
- mesures terrain par curseur tactile avec valeur en lecture seule
- echelle et pas par materiel, independants des seuils de conformite
- option de non-conformite a egalite avec le seuil minimum
- conservation de la configuration lors des duplications et reprise des brouillons
- correction du tri des zones dans le detail et l export des verifications
- tests de regression des mesures, de leur enregistrement et du detail historique

### v1.8.1
- mails de suivi d anomalie detailles, homogenes avec les signalements
- encodage MIME protegeant les balises HTML pendant le transport
- tests de restitution du contenu et de respect de la caserne active

## Prochain cycle

### v1.9
- historique de livraison des notifications email avec statut et erreur
- recherche et filtres avances dans les anomalies
- actions groupees sur les anomalies
- tests automatises des parcours critiques

## A etudier

### Verifications
- modeles d engins et duplication de configuration
- controles periodiques autres que quotidiens
- mode hors ligne terrain avec synchronisation controlee

### Notifications
- digest quotidien des anomalies actives
- escalade selon priorite et anciennete
- canaux additionnels via integrations externes

### Pharmacie
- commandes fournisseurs et receptions partielles
- alertes de peremption
- exports consolides multi-caserne

### Plateforme
- API documentee pour integrations
- authentification SSO
- observabilite metier et statistiques de performance
- finalisation du module carburant

## Criteres de priorisation
- securite et integrite des donnees
- impact operationnel terrain
- frequence du besoin
- complexite de maintenance
- compatibilite avec les installations existantes
