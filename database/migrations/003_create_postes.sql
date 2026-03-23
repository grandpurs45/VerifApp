CREATE TABLE postes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    type_vehicule_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_postes_type_vehicule
        FOREIGN KEY (type_vehicule_id)
        REFERENCES type_vehicules(id)
        ON DELETE RESTRICT
);