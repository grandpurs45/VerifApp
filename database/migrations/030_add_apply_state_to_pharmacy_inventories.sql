ALTER TABLE pharmacie_inventaires
    ADD COLUMN IF NOT EXISTS applique_le DATETIME NULL AFTER cree_le;

ALTER TABLE pharmacie_inventaires
    ADD COLUMN IF NOT EXISTS applique_par VARCHAR(150) NULL AFTER applique_le;
