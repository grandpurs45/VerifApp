ALTER TABLE vehicules
    ADD COLUMN verification_frequency VARCHAR(20) NOT NULL DEFAULT 'daily' AFTER verification_active;
