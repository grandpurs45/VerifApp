SET @has_col := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateur_casernes'
      AND COLUMN_NAME = 'role_code'
);
SET @sql := IF(@has_col = 0, 'ALTER TABLE utilisateur_casernes ADD COLUMN role_code VARCHAR(100) NULL AFTER caserne_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE utilisateur_casernes uc
INNER JOIN utilisateurs u ON u.id = uc.utilisateur_id
SET uc.role_code = u.role
WHERE uc.role_code IS NULL OR uc.role_code = '';

SET @sql := 'ALTER TABLE utilisateur_casernes MODIFY COLUMN role_code VARCHAR(100) NOT NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateur_casernes'
      AND INDEX_NAME = 'idx_utilisateur_casernes_role_code'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX idx_utilisateur_casernes_role_code ON utilisateur_casernes (role_code)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
