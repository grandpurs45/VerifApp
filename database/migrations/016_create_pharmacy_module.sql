CREATE TABLE IF NOT EXISTS pharmacie_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    unite VARCHAR(30) NOT NULL DEFAULT 'u',
    stock_actuel DECIMAL(10,2) NOT NULL DEFAULT 0,
    seuil_alerte DECIMAL(10,2) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pharmacie_articles_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pharmacie_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    type ENUM('sortie', 'entree', 'ajustement') NOT NULL DEFAULT 'sortie',
    quantite DECIMAL(10,2) NOT NULL,
    commentaire VARCHAR(500) DEFAULT NULL,
    declarant VARCHAR(120) DEFAULT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pharmacie_mouvements_article
        FOREIGN KEY (article_id) REFERENCES pharmacie_articles(id) ON DELETE RESTRICT,
    INDEX idx_pharmacie_mouvements_article_date (article_id, cree_le),
    INDEX idx_pharmacie_mouvements_type_date (type, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
