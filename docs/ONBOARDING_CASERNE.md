# Onboarding Nouvelle Caserne (v1)

Procedure recommandee pour integrer une nouvelle caserne dans VerifApp sans interrompre l exploitation.

## 1. Prerequis
- Avoir un compte `Administrateur` (plateforme).
- Avoir un backup recent:
  - `php scripts/backup.php --out=backups --name=before_new_caserne`
- Verifier la sante applicative:
  - `GET /health.php` attendu `status=ok` et `db=ok`.

## 2. Creation de la caserne
1. Ouvrir `Administration -> Utilisateurs` (ou module caserne selon version).
2. Creer la caserne (nom court + libelle officiel).
3. Verifier qu elle apparait dans le selecteur de caserne.

Bonnes pratiques nommage:
- Nom court sans caracteres exotiques (ex: `ORMES-SARAN`).
- Nom affiche clair et stable.

## 3. Comptes et roles locaux
1. Creer le compte admin local de la caserne (role local `Administrateur_caserne`).
2. Affecter les autres comptes (responsable materiel, responsable pharmacie, verificateur).
3. Verifier qu un compte a au moins 1 caserne affectee.
4. Valider la separation des droits:
  - admin caserne ne voit pas les donnees des autres casernes.
  - admin plateforme conserve la vue globale.

## 4. Parametres de la caserne
Dans `Administration -> Parametres application`, verifier pour la caserne active:
- heure de bascule matin/soir des verifications mensuelles.
- TTL session gestionnaire (si parametre caserne active).
- notifications activees selon besoin (in-app / email).
- QR tokens caserne (verification, pharmacie, inventaire) generes.

## 5. Initialisation metier
Pour la nouvelle caserne:
1. Configurer types d engins et postes.
2. Creer les vehicules.
3. Construire les zones/sous-zones par vehicule.
4. Ajouter le materiel par vehicule.
5. Configurer pharmacie:
  - liste articles,
  - seuils,
  - regles sortie CR.

Option:
- dupliquer un vehicule modele pour accelerer le parametrage.

## 6. QR et affichage terrain
1. Generer les QR caserne:
  - verification,
  - sortie pharmacie,
  - inventaire.
2. Generer les QR engin necessaires.
3. Imprimer les affiches A4 et poser en caserne.
4. Tester avec smartphone reel:
  - ouverture QR,
  - saisie,
  - enregistrement.

## 7. Checklist de validation (Go live)
- Login admin caserne: OK.
- Dashboard caserne: OK.
- 1 verification terrain enregistree: OK.
- Anomalie auto sur NOK: OK.
- 1 sortie pharmacie: OK.
- 1 inventaire terrain: OK.
- Notifications in-app: OK.
- Notifications email (si activees): OK.
- Audit securite visible pour la caserne: OK.

## 8. Support post-demarrage (J+1 / J+7)
- J+1:
  - verifier logs d erreur,
  - verifier connexions/lockout anormaux.
- J+7:
  - verifier adoption terrain,
  - ajuster parametrage UX si besoin (densite, brouillon, etc.).

## 9. Rollback onboarding
En cas de probleme critique:
1. Bloquer temporairement les nouvelles saisies sur la caserne.
2. Restaurer le dernier backup valide:
   - `php scripts/restore.php --from=backups/verifapp_backup_xxx.zip --force`
3. Rejouer uniquement les changements necessaires.

Voir aussi:
- `docs/ROLLBACK.md`
- `docs/RUNBOOK_INCIDENT.md`
