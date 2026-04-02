-- Realign caserne_id from parent entities to avoid cross-caserne orphan records.

UPDATE vehicules v
INNER JOIN type_vehicules tv ON tv.id = v.type_vehicule_id
SET v.caserne_id = tv.caserne_id
WHERE v.caserne_id <> tv.caserne_id;

UPDATE postes p
INNER JOIN type_vehicules tv ON tv.id = p.type_vehicule_id
SET p.caserne_id = tv.caserne_id
WHERE p.caserne_id <> tv.caserne_id;

UPDATE zones z
INNER JOIN vehicules v ON v.id = z.vehicule_id
SET z.caserne_id = v.caserne_id
WHERE z.caserne_id <> v.caserne_id;

UPDATE controles c
INNER JOIN vehicules v ON v.id = c.vehicule_id
SET c.caserne_id = v.caserne_id
WHERE c.caserne_id <> v.caserne_id;

UPDATE verifications ver
INNER JOIN vehicules v ON v.id = ver.vehicule_id
SET ver.caserne_id = v.caserne_id
WHERE ver.caserne_id <> v.caserne_id;

UPDATE pharmacie_articles pa
SET pa.caserne_id = (
    SELECT c.id
    FROM casernes c
    ORDER BY c.id ASC
    LIMIT 1
)
WHERE pa.caserne_id IS NULL;

UPDATE pharmacie_mouvements pm
INNER JOIN pharmacie_articles pa ON pa.id = pm.article_id
SET pm.caserne_id = pa.caserne_id
WHERE pm.caserne_id <> pa.caserne_id;

-- Ensure each user has at least one default caserne link.
UPDATE utilisateur_casernes uc
INNER JOIN (
    SELECT utilisateur_id, MIN(caserne_id) AS caserne_id
    FROM utilisateur_casernes
    GROUP BY utilisateur_id
) first_link ON first_link.utilisateur_id = uc.utilisateur_id
SET uc.is_default = CASE WHEN uc.caserne_id = first_link.caserne_id THEN 1 ELSE 0 END;

-- Create scoped QR tokens per caserne when missing (keep legacy global tokens as fallback).
SET @has_app_settings := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'app_settings'
);

SET @sql := IF(@has_app_settings = 0, 'SELECT 1', '
INSERT INTO app_settings (setting_key, setting_value)
SELECT
    CONCAT(''field_qr_token_caserne_'', c.id) AS setting_key,
    LOWER(SHA2(CONCAT(UUID(), ''_field_'', c.id, ''_'', NOW()), 256)) AS setting_value
FROM casernes c
LEFT JOIN app_settings s ON s.setting_key = CONCAT(''field_qr_token_caserne_'', c.id)
WHERE s.setting_key IS NULL
  AND c.actif = 1
');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@has_app_settings = 0, 'SELECT 1', '
INSERT INTO app_settings (setting_key, setting_value)
SELECT
    CONCAT(''pharmacy_qr_token_caserne_'', c.id) AS setting_key,
    LOWER(SHA2(CONCAT(UUID(), ''_pharmacy_'', c.id, ''_'', NOW()), 256)) AS setting_value
FROM casernes c
LEFT JOIN app_settings s ON s.setting_key = CONCAT(''pharmacy_qr_token_caserne_'', c.id)
WHERE s.setting_key IS NULL
  AND c.actif = 1
');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
