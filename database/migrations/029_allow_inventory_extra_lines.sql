ALTER TABLE pharmacie_inventaire_lignes
    MODIFY article_id INT NULL;

ALTER TABLE pharmacie_inventaire_lignes
    ADD COLUMN article_libre_nom VARCHAR(150) NULL AFTER article_id;
