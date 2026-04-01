CREATE TABLE IF NOT EXISTS casernes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    code VARCHAR(80) NOT NULL UNIQUE,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utilisateur_casernes (
    utilisateur_id INT NOT NULL,
    caserne_id INT NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utilisateur_id, caserne_id),
    CONSTRAINT fk_utilisateur_casernes_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_utilisateur_casernes_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE CASCADE,
    INDEX idx_utilisateur_casernes_default (utilisateur_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO casernes (nom, code, actif)
SELECT 'Caserne principale', 'caserne_principale', 1
WHERE NOT EXISTS (SELECT 1 FROM casernes LIMIT 1);

SET @default_caserne_id := (SELECT id FROM casernes ORDER BY id ASC LIMIT 1);

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'type_vehicules'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE type_vehicules ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE type_vehicules SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE type_vehicules MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'type_vehicules'
      AND INDEX_NAME = 'nom'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE type_vehicules DROP INDEX nom', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'type_vehicules'
      AND INDEX_NAME = 'uq_type_vehicules_caserne_nom'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE type_vehicules ADD CONSTRAINT uq_type_vehicules_caserne_nom UNIQUE (caserne_id, nom)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'type_vehicules'
      AND CONSTRAINT_NAME = 'fk_type_vehicules_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE type_vehicules ADD CONSTRAINT fk_type_vehicules_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vehicules'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE vehicules ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE vehicules SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE vehicules MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vehicules'
      AND CONSTRAINT_NAME = 'fk_vehicules_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE vehicules ADD CONSTRAINT fk_vehicules_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vehicules'
      AND INDEX_NAME = 'idx_vehicules_caserne'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE vehicules ADD INDEX idx_vehicules_caserne (caserne_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'postes'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE postes ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE postes SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE postes MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'postes'
      AND INDEX_NAME = 'code'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE postes DROP INDEX code', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'postes'
      AND INDEX_NAME = 'uq_postes_caserne_code'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE postes ADD CONSTRAINT uq_postes_caserne_code UNIQUE (caserne_id, code)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'postes'
      AND CONSTRAINT_NAME = 'fk_postes_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE postes ADD CONSTRAINT fk_postes_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'controles'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE controles ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_vehicle_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'controles'
      AND COLUMN_NAME = 'vehicule_id'
);
SET @sql := IF(@has_vehicle_col > 0,
    'UPDATE controles c INNER JOIN vehicules v ON v.id = c.vehicule_id SET c.caserne_id = v.caserne_id WHERE c.caserne_id IS NULL',
    'UPDATE controles c INNER JOIN postes p ON p.id = c.poste_id SET c.caserne_id = p.caserne_id WHERE c.caserne_id IS NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE controles SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE controles MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'controles'
      AND CONSTRAINT_NAME = 'fk_controles_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE controles ADD CONSTRAINT fk_controles_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'controles'
      AND INDEX_NAME = 'idx_controles_caserne'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE controles ADD INDEX idx_controles_caserne (caserne_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'zones'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE zones ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE zones z
INNER JOIN vehicules v ON v.id = z.vehicule_id
SET z.caserne_id = v.caserne_id
WHERE z.caserne_id IS NULL;
UPDATE zones SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE zones MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'zones'
      AND CONSTRAINT_NAME = 'fk_zones_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE zones ADD CONSTRAINT fk_zones_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'verifications'
      AND COLUMN_NAME = 'caserne_id'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE verifications ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE verifications ver
INNER JOIN vehicules v ON v.id = ver.vehicule_id
SET ver.caserne_id = v.caserne_id
WHERE ver.caserne_id IS NULL;
UPDATE verifications SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE verifications MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'verifications'
      AND CONSTRAINT_NAME = 'fk_verifications_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE verifications ADD CONSTRAINT fk_verifications_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'verifications'
      AND INDEX_NAME = 'idx_verifications_caserne_date'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE verifications ADD INDEX idx_verifications_caserne_date (caserne_id, date_heure)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_table := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_articles'
);
SET @sql := IF(@has_table > 0, 'SELECT 1', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    IF(
        @has_table = 0,
        1,
        (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pharmacie_articles'
              AND COLUMN_NAME = 'caserne_id'
        )
    )
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE pharmacie_articles ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE pharmacie_articles SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE pharmacie_articles MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_articles'
      AND INDEX_NAME = 'uq_pharmacie_articles_nom'
);
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE pharmacie_articles DROP INDEX uq_pharmacie_articles_nom', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_articles'
      AND INDEX_NAME = 'uq_pharmacie_articles_caserne_nom'
);
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE pharmacie_articles ADD CONSTRAINT uq_pharmacie_articles_caserne_nom UNIQUE (caserne_id, nom)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_articles'
      AND CONSTRAINT_NAME = 'fk_pharmacie_articles_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE pharmacie_articles ADD CONSTRAINT fk_pharmacie_articles_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (
    IF(
        (
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pharmacie_mouvements'
        ) = 0,
        1,
        (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pharmacie_mouvements'
              AND COLUMN_NAME = 'caserne_id'
        )
    )
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE pharmacie_mouvements ADD COLUMN caserne_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE pharmacie_mouvements pm
INNER JOIN pharmacie_articles pa ON pa.id = pm.article_id
SET pm.caserne_id = pa.caserne_id
WHERE pm.caserne_id IS NULL;
UPDATE pharmacie_mouvements SET caserne_id = @default_caserne_id WHERE caserne_id IS NULL;
SET @sql := 'ALTER TABLE pharmacie_mouvements MODIFY COLUMN caserne_id INT NOT NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_mouvements'
      AND CONSTRAINT_NAME = 'fk_pharmacie_mouvements_caserne'
);
SET @sql := IF(@fk_exists = 0, 'ALTER TABLE pharmacie_mouvements ADD CONSTRAINT fk_pharmacie_mouvements_caserne FOREIGN KEY (caserne_id) REFERENCES casernes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO utilisateur_casernes (utilisateur_id, caserne_id, is_default)
SELECT u.id, @default_caserne_id, 1
FROM utilisateurs u
LEFT JOIN utilisateur_casernes uc ON uc.utilisateur_id = u.id
WHERE uc.utilisateur_id IS NULL;

UPDATE utilisateur_casernes uc
INNER JOIN (
    SELECT utilisateur_id, MIN(caserne_id) AS caserne_id
    FROM utilisateur_casernes
    GROUP BY utilisateur_id
) first_link ON first_link.utilisateur_id = uc.utilisateur_id
SET uc.is_default = CASE WHEN uc.caserne_id = first_link.caserne_id THEN 1 ELSE 0 END;
