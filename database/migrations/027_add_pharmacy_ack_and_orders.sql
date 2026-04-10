ALTER TABLE pharmacie_mouvements
    ADD COLUMN IF NOT EXISTS acquitte_le DATETIME NULL AFTER cree_le,
    ADD COLUMN IF NOT EXISTS acquitte_par VARCHAR(120) NULL AFTER acquitte_le;

CREATE TABLE IF NOT EXISTS pharmacie_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NOT NULL,
    commande_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(500) NULL,
    cree_par VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pharmacie_commandes_caserne_date (caserne_id, commande_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
