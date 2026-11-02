ALTER TABLE controles
    ADD COLUMN seuil_min_exclusif TINYINT(1) NOT NULL DEFAULT 0 AFTER seuil_max;
