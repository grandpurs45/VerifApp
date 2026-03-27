ALTER TABLE controles
    ADD COLUMN type_saisie VARCHAR(20) NOT NULL DEFAULT 'statut' AFTER libelle,
    ADD COLUMN valeur_attendue DECIMAL(10,2) NULL AFTER type_saisie,
    ADD COLUMN unite VARCHAR(20) NULL AFTER valeur_attendue,
    ADD COLUMN seuil_min DECIMAL(10,2) NULL AFTER unite,
    ADD COLUMN seuil_max DECIMAL(10,2) NULL AFTER seuil_min;

ALTER TABLE verification_lignes
    ADD COLUMN valeur_saisie DECIMAL(10,2) NULL AFTER resultat;
