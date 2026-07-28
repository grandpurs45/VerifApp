ALTER TABLE verifications
    ADD COLUMN garde_duree_heures TINYINT UNSIGNED NOT NULL DEFAULT 12 AFTER agent;
