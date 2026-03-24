ALTER TABLE verifications
    ADD COLUMN utilisateur_id INT NULL AFTER poste_id;

UPDATE verifications v
INNER JOIN utilisateurs u ON u.nom = v.agent
SET v.utilisateur_id = u.id
WHERE v.utilisateur_id IS NULL;

ALTER TABLE verifications
    ADD CONSTRAINT fk_verifications_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id)
        ON DELETE SET NULL;
