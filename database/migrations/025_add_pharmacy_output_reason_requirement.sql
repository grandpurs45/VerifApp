ALTER TABLE pharmacie_articles
    ADD COLUMN IF NOT EXISTS motif_sortie_obligatoire TINYINT(1) NOT NULL DEFAULT 0 AFTER actif;

