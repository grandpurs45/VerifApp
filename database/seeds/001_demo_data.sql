SET @caserne_id := (SELECT id FROM casernes ORDER BY id ASC LIMIT 1);

INSERT INTO type_vehicules (caserne_id, nom)
SELECT @caserne_id, 'VSAV'
WHERE NOT EXISTS (
    SELECT 1
    FROM type_vehicules
    WHERE caserne_id = @caserne_id
      AND nom = 'VSAV'
);

SET @type_vsav_id := (
    SELECT id
    FROM type_vehicules
    WHERE caserne_id = @caserne_id
      AND nom = 'VSAV'
    LIMIT 1
);

INSERT INTO vehicules (caserne_id, nom, type_vehicule_id, actif)
SELECT @caserne_id, 'VSAV 75', @type_vsav_id, TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM vehicules
    WHERE caserne_id = @caserne_id
      AND nom = 'VSAV 75'
);

SET @vehicule_vsav_id := (
    SELECT id
    FROM vehicules
    WHERE caserne_id = @caserne_id
      AND nom = 'VSAV 75'
    LIMIT 1
);

INSERT INTO postes (caserne_id, nom, code, type_vehicule_id)
SELECT @caserne_id, 'Chef d''agres VSAV', 'CA_VSAV', @type_vsav_id
WHERE NOT EXISTS (
    SELECT 1
    FROM postes
    WHERE caserne_id = @caserne_id
      AND code = 'CA_VSAV'
);

SET @poste_vsav_id := (
    SELECT id
    FROM postes
    WHERE caserne_id = @caserne_id
      AND code = 'CA_VSAV'
    LIMIT 1
);

INSERT INTO zones (caserne_id, vehicule_id, nom)
SELECT @caserne_id, @vehicule_vsav_id, 'Cabine avant'
WHERE NOT EXISTS (
    SELECT 1
    FROM zones
    WHERE caserne_id = @caserne_id
      AND vehicule_id = @vehicule_vsav_id
      AND nom = 'Cabine avant'
);

INSERT INTO zones (caserne_id, vehicule_id, nom)
SELECT @caserne_id, @vehicule_vsav_id, 'Cellule'
WHERE NOT EXISTS (
    SELECT 1
    FROM zones
    WHERE caserne_id = @caserne_id
      AND vehicule_id = @vehicule_vsav_id
      AND nom = 'Cellule'
);

SET @zone_cabine_id := (
    SELECT id
    FROM zones
    WHERE caserne_id = @caserne_id
      AND vehicule_id = @vehicule_vsav_id
      AND nom = 'Cabine avant'
    LIMIT 1
);

SET @zone_cellule_id := (
    SELECT id
    FROM zones
    WHERE caserne_id = @caserne_id
      AND vehicule_id = @vehicule_vsav_id
      AND nom = 'Cellule'
    LIMIT 1
);

INSERT INTO controles (caserne_id, libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif)
SELECT @caserne_id, 'Radio', @poste_vsav_id, @vehicule_vsav_id, @zone_cabine_id, 'Cabine avant', 1, TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM controles
    WHERE caserne_id = @caserne_id
      AND poste_id = @poste_vsav_id
      AND vehicule_id = @vehicule_vsav_id
      AND libelle = 'Radio'
);

INSERT INTO controles (caserne_id, libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif)
SELECT @caserne_id, 'Lampe', @poste_vsav_id, @vehicule_vsav_id, @zone_cabine_id, 'Cabine avant', 2, TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM controles
    WHERE caserne_id = @caserne_id
      AND poste_id = @poste_vsav_id
      AND vehicule_id = @vehicule_vsav_id
      AND libelle = 'Lampe'
);

INSERT INTO controles (caserne_id, libelle, poste_id, vehicule_id, zone_id, zone, ordre, actif)
SELECT @caserne_id, 'Couverture', @poste_vsav_id, @vehicule_vsav_id, @zone_cellule_id, 'Cellule', 3, TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM controles
    WHERE caserne_id = @caserne_id
      AND poste_id = @poste_vsav_id
      AND vehicule_id = @vehicule_vsav_id
      AND libelle = 'Couverture'
);
