CREATE TABLE controles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL,
    poste_id INT NOT NULL,
    zone VARCHAR(100) NOT NULL,
    ordre INT NOT NULL DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_controles_poste
        FOREIGN KEY (poste_id)
        REFERENCES postes(id)
        ON DELETE CASCADE
);