ALTER TABLE controles
    ADD COLUMN vehicule_id INT NULL AFTER poste_id,
    ADD COLUMN zone_id INT NULL AFTER vehicule_id;

INSERT IGNORE INTO zones (vehicule_id, nom)
SELECT DISTINCT
    v.id AS vehicule_id,
    c.zone AS nom
FROM controles c
INNER JOIN postes p ON p.id = c.poste_id
INNER JOIN vehicules v ON v.type_vehicule_id = p.type_vehicule_id
WHERE c.zone IS NOT NULL
  AND c.zone <> '';

-- Duplication par vehicule pour les controles existants (mode legacy).
INSERT INTO controles (libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif, created_at)
SELECT
    c.libelle,
    c.poste_id,
    v.id AS vehicule_id,
    z.id AS zone_id,
    c.zone,
    c.ordre,
    c.actif,
    c.created_at
FROM controles c
INNER JOIN postes p ON p.id = c.poste_id
INNER JOIN vehicules v ON v.type_vehicule_id = p.type_vehicule_id
INNER JOIN zones z
    ON z.vehicule_id = v.id
   AND z.nom = c.zone
WHERE c.vehicule_id IS NULL;

-- Assure les zones manquantes deduites des verifications historiques.
INSERT IGNORE INTO zones (vehicule_id, nom)
SELECT DISTINCT
    v.vehicule_id,
    c_old.zone
FROM verification_lignes vl
INNER JOIN verifications v ON v.id = vl.verification_id
INNER JOIN controles c_old ON c_old.id = vl.controle_id
WHERE c_old.vehicule_id IS NULL
  AND c_old.zone IS NOT NULL
  AND c_old.zone <> '';

-- Cree les controles vehicule-specifiques manquants pour toutes les verifications existantes.
INSERT INTO controles (libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif, created_at)
SELECT DISTINCT
    c_old.libelle,
    c_old.poste_id,
    v.vehicule_id,
    z.id AS zone_id,
    c_old.zone,
    c_old.ordre,
    c_old.actif,
    c_old.created_at
FROM verification_lignes vl
INNER JOIN verifications v ON v.id = vl.verification_id
INNER JOIN controles c_old ON c_old.id = vl.controle_id
INNER JOIN zones z
    ON z.vehicule_id = v.vehicule_id
   AND z.nom = c_old.zone
LEFT JOIN controles c_new
    ON c_new.vehicule_id = v.vehicule_id
   AND c_new.poste_id = c_old.poste_id
   AND c_new.libelle = c_old.libelle
   AND c_new.zone = c_old.zone
   AND c_new.ordre = c_old.ordre
WHERE c_old.vehicule_id IS NULL
  AND c_new.id IS NULL;

-- Remappe les lignes historiques vers les nouveaux controles.
UPDATE verification_lignes vl
INNER JOIN verifications v ON v.id = vl.verification_id
INNER JOIN controles c_old ON c_old.id = vl.controle_id
INNER JOIN (
    SELECT
        MIN(id) AS id,
        vehicule_id,
        poste_id,
        libelle,
        zone,
        ordre
    FROM controles
    WHERE vehicule_id IS NOT NULL
    GROUP BY vehicule_id, poste_id, libelle, zone, ordre
) c_new
    ON c_new.vehicule_id = v.vehicule_id
   AND c_new.poste_id = c_old.poste_id
   AND c_new.libelle = c_old.libelle
   AND c_new.zone = c_old.zone
   AND c_new.ordre = c_old.ordre
SET vl.controle_id = c_new.id
WHERE c_old.vehicule_id IS NULL;

-- Supprime uniquement les anciens controles legacy non references.
DELETE c
FROM controles c
LEFT JOIN verification_lignes vl ON vl.controle_id = c.id
WHERE c.vehicule_id IS NULL
  AND vl.id IS NULL;

ALTER TABLE controles
    MODIFY COLUMN vehicule_id INT NOT NULL,
    MODIFY COLUMN zone_id INT NOT NULL;

ALTER TABLE controles
    ADD CONSTRAINT fk_controles_vehicule
        FOREIGN KEY (vehicule_id)
        REFERENCES vehicules(id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_controles_zone
        FOREIGN KEY (zone_id)
        REFERENCES zones(id)
        ON DELETE RESTRICT;
