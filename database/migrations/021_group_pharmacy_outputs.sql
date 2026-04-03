ALTER TABLE pharmacie_mouvements
    ADD COLUMN IF NOT EXISTS sortie_ref VARCHAR(64) NULL AFTER type;

CREATE INDEX idx_pharmacie_mouvements_sortie_ref
    ON pharmacie_mouvements (sortie_ref);
