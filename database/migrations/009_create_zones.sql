CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicule_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_zones_vehicule
        FOREIGN KEY (vehicule_id)
        REFERENCES vehicules(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_zones_vehicule_nom
        UNIQUE (vehicule_id, nom)
);
