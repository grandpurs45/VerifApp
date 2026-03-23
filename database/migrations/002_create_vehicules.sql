CREATE TABLE vehicules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    type_vehicule_id INT NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_type_vehicule
        FOREIGN KEY (type_vehicule_id)
        REFERENCES type_vehicules(id)
        ON DELETE RESTRICT
);