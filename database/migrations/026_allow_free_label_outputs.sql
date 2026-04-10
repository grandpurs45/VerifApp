ALTER TABLE pharmacie_mouvements
    MODIFY COLUMN article_id INT NULL;

ALTER TABLE pharmacie_mouvements
    ADD COLUMN IF NOT EXISTS article_libre_nom VARCHAR(150) NULL AFTER article_id;
