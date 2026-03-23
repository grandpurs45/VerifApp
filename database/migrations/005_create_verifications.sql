CREATE TABLE verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicule_id INT NOT NULL,
    poste_id INT NOT NULL,
    agent VARCHAR(100) NOT NULL,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut_global VARCHAR(20) NOT NULL DEFAULT 'conforme',
    commentaire_global TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_verifications_vehicule
        FOREIGN KEY (vehicule_id)
        REFERENCES vehicules(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_verifications_poste
        FOREIGN KEY (poste_id)
        REFERENCES postes(id)
        ON DELETE RESTRICT
);