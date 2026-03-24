INSERT INTO utilisateurs (nom, email, mot_de_passe, role, actif)
VALUES (
    'Verificateur Demo',
    'verificateur@verifapp.local',
    '$2y$10$sbS6Drs309ESbNxoTtjnd.XN3g75dJ3Esg7GKaLP2nyCY7P0PFTZe',
    'verificateur',
    TRUE
)
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    mot_de_passe = VALUES(mot_de_passe),
    role = VALUES(role),
    actif = VALUES(actif);
