CREATE TABLE IF NOT EXISTS pharmacie_inventaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NOT NULL,
    cree_par VARCHAR(150) NULL,
    note VARCHAR(255) NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pharm_inv_caserne_date (caserne_id, cree_le),
    CONSTRAINT fk_pharm_inv_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pharmacie_inventaire_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    article_id INT NOT NULL,
    stock_theorique DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_compte DECIMAL(10,2) NOT NULL DEFAULT 0,
    ecart DECIMAL(10,2) NOT NULL DEFAULT 0,
    commentaire VARCHAR(255) NULL,
    INDEX idx_pharm_inv_lignes_inv (inventaire_id),
    INDEX idx_pharm_inv_lignes_article (article_id),
    CONSTRAINT fk_pharm_inv_lignes_inv
        FOREIGN KEY (inventaire_id) REFERENCES pharmacie_inventaires(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_pharm_inv_lignes_article
        FOREIGN KEY (article_id) REFERENCES pharmacie_articles(id)
        ON DELETE RESTRICT
);
