SET @has_prenom := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND COLUMN_NAME = 'prenom'
);

SET @sql := IF(
    @has_prenom = 0,
    'ALTER TABLE utilisateurs ADD COLUMN prenom VARCHAR(100) NULL AFTER nom',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_login := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND COLUMN_NAME = 'login'
);

SET @sql := IF(
    @has_login = 0,
    'ALTER TABLE utilisateurs ADD COLUMN login VARCHAR(100) NULL AFTER prenom',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE utilisateurs
SET login = LOWER(REPLACE(TRIM(nom), ' ', '.'))
WHERE login IS NULL OR TRIM(login) = '';

UPDATE utilisateurs
SET login = CONCAT('user_', id)
WHERE login IS NULL OR TRIM(login) = '';

UPDATE utilisateurs u
INNER JOIN (
    SELECT login
    FROM utilisateurs
    WHERE login IS NOT NULL AND TRIM(login) <> ''
    GROUP BY login
    HAVING COUNT(*) > 1
) duplicates ON duplicates.login = u.login
SET u.login = CONCAT(u.login, '.', u.id);

SET @has_idx_login := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'utilisateurs'
      AND INDEX_NAME = 'uq_utilisateurs_login'
);

SET @sql := IF(
    @has_idx_login = 0,
    'ALTER TABLE utilisateurs ADD UNIQUE INDEX uq_utilisateurs_login (login)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
