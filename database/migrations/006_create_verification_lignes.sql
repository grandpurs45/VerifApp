CREATE TABLE verification_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    verification_id INT NOT NULL,
    controle_id INT NOT NULL,
    resultat VARCHAR(10) NOT NULL,
    commentaire TEXT NULL,
    photo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_verification_lignes_verification
        FOREIGN KEY (verification_id)
        REFERENCES verifications(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_verification_lignes_controle
        FOREIGN KEY (controle_id)
        REFERENCES controles(id)
        ON DELETE RESTRICT
);