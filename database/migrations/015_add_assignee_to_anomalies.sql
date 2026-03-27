ALTER TABLE anomalies
    ADD COLUMN assigne_a INT NULL AFTER priorite,
    ADD CONSTRAINT fk_anomalies_assigne_a
        FOREIGN KEY (assigne_a)
        REFERENCES utilisateurs(id)
        ON DELETE SET NULL;
