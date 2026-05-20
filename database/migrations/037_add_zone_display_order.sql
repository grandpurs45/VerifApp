ALTER TABLE zones
    ADD COLUMN ordre INT NOT NULL DEFAULT 0 AFTER nom;

CREATE INDEX idx_zones_vehicle_parent_order
    ON zones (vehicule_id, parent_id, ordre, nom);
