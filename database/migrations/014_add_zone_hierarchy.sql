ALTER TABLE zones
    ADD COLUMN parent_id INT NULL AFTER vehicule_id,
    ADD INDEX idx_zones_parent_id (parent_id);

ALTER TABLE zones
    ADD CONSTRAINT fk_zones_parent
        FOREIGN KEY (parent_id)
        REFERENCES zones(id)
        ON DELETE RESTRICT;
