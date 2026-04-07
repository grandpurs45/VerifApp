SET @has_fk_idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'zones'
      AND index_name = 'idx_zones_vehicule_id'
);

SET @add_fk_idx_sql := IF(
    @has_fk_idx = 0,
    'ALTER TABLE zones ADD INDEX idx_zones_vehicule_id (vehicule_id)',
    'SELECT 1'
);
PREPARE add_fk_idx_stmt FROM @add_fk_idx_sql;
EXECUTE add_fk_idx_stmt;
DEALLOCATE PREPARE add_fk_idx_stmt;

SET @has_old_idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'zones'
      AND index_name = 'uq_zones_vehicule_nom'
);

SET @drop_sql := IF(
    @has_old_idx > 0,
    'ALTER TABLE zones DROP INDEX uq_zones_vehicule_nom',
    'SELECT 1'
);
PREPARE drop_stmt FROM @drop_sql;
EXECUTE drop_stmt;
DEALLOCATE PREPARE drop_stmt;

SET @has_new_idx := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'zones'
      AND index_name = 'uq_zones_vehicule_parent_nom'
);

SET @add_sql := IF(
    @has_new_idx = 0,
    'ALTER TABLE zones ADD CONSTRAINT uq_zones_vehicule_parent_nom UNIQUE (vehicule_id, parent_id, nom)',
    'SELECT 1'
);
PREPARE add_stmt FROM @add_sql;
EXECUTE add_stmt;
DEALLOCATE PREPARE add_stmt;
