ALTER TABLE utilisateurs
    ADD COLUMN must_change_password BOOLEAN NOT NULL DEFAULT FALSE AFTER actif;

INSERT INTO utilisateurs (nom, email, mot_de_passe, role, actif, must_change_password)
SELECT
    'admin',
    'admin@verifapp.local',
    '$2y$10$zudVIUh7cmk5MafvXE.C6.fnWKq4ya8qOUWkPubdXW0UCtl52ICum',
    'admin',
    TRUE,
    TRUE
WHERE NOT EXISTS (
    SELECT 1
    FROM utilisateurs
    WHERE email = 'admin@verifapp.local'
);
