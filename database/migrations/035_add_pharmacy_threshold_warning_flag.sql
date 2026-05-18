SET @has_col := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pharmacie_articles'
      AND COLUMN_NAME = 'surveiller_seuil'
);

SET @sql := IF(
    @has_col = 0,
    'ALTER TABLE pharmacie_articles ADD COLUMN surveiller_seuil TINYINT(1) NOT NULL DEFAULT 0 AFTER seuil_alerte',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
