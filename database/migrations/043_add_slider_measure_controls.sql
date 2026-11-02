ALTER TABLE controles
    ADD COLUMN curseur_min INT NULL AFTER seuil_max,
    ADD COLUMN curseur_max INT NULL AFTER curseur_min,
    ADD COLUMN curseur_pas INT NULL AFTER curseur_max;
