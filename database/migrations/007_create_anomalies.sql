CREATE TABLE anomalies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    verification_ligne_id INT NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'ouverte',
    priorite VARCHAR(20) NOT NULL DEFAULT 'moyenne',
    commentaire TEXT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_resolution DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_anomalies_verification_ligne
        FOREIGN KEY (verification_ligne_id)
        REFERENCES verification_lignes(id)
        ON DELETE CASCADE
);
