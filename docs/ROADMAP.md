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

## Prochain cycle

### v1.6
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
