INSERT INTO type_vehicules (nom)
VALUES ('VSAV');

INSERT INTO vehicules (nom, type_vehicule_id, actif)
VALUES ('VSAV 75', 1, TRUE);

INSERT INTO postes (nom, code, type_vehicule_id)
VALUES ('Chef d''agrès VSAV', 'CA_VSAV', 1);

INSERT INTO controles (libelle, poste_id, zone, ordre, actif)
VALUES
    ('Radio', 1, 'Cabine avant', 1, TRUE),
    ('Lampe', 1, 'Cabine avant', 2, TRUE),
    ('Couverture', 1, 'Cellule', 3, TRUE);