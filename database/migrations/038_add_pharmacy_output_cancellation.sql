ALTER TABLE pharmacie_mouvements
    ADD COLUMN IF NOT EXISTS annule_le DATETIME NULL AFTER acquitte_par,
    ADD COLUMN IF NOT EXISTS annule_par VARCHAR(120) NULL AFTER annule_le,
    ADD COLUMN IF NOT EXISTS annulation_motif VARCHAR(255) NULL AFTER annule_par;

CREATE INDEX IF NOT EXISTS idx_pharmacie_mouvements_annule_le
    ON pharmacie_mouvements (annule_le);
