-- Ensure admin accounts are linked to all active casernes.
INSERT IGNORE INTO utilisateur_casernes (utilisateur_id, caserne_id, is_default)
SELECT u.id, c.id, 0
FROM utilisateurs u
INNER JOIN casernes c ON c.actif = 1
WHERE u.role = 'admin';

-- Ensure each admin has exactly one default caserne.
UPDATE utilisateur_casernes uc
INNER JOIN (
    SELECT uc2.utilisateur_id, MIN(uc2.caserne_id) AS first_caserne_id
    FROM utilisateur_casernes uc2
    INNER JOIN utilisateurs u2 ON u2.id = uc2.utilisateur_id
    WHERE u2.role = 'admin'
    GROUP BY uc2.utilisateur_id
) first_link ON first_link.utilisateur_id = uc.utilisateur_id
INNER JOIN utilisateurs u ON u.id = uc.utilisateur_id
SET uc.is_default = CASE WHEN uc.caserne_id = first_link.first_caserne_id THEN 1 ELSE 0 END
WHERE u.role = 'admin';
