CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_code VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_code),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (code, nom, actif, is_system)
VALUES
    ('admin', 'Administrateur', 1, 1),
    ('responsable_materiel', 'Responsable materiel', 1, 0),
    ('verificateur', 'Verificateur', 1, 0)
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    actif = VALUES(actif),
    is_system = CASE WHEN roles.code = 'admin' THEN 1 ELSE roles.is_system END;

INSERT INTO role_permissions (role_id, permission_code)
SELECT r.id, p.permission_code
FROM roles r
JOIN (
    SELECT 'dashboard.view' AS permission_code
    UNION ALL SELECT 'verifications.history'
    UNION ALL SELECT 'anomalies.manage'
    UNION ALL SELECT 'assets.manage'
    UNION ALL SELECT 'pharmacy.manage'
    UNION ALL SELECT 'users.manage'
) p
WHERE r.code = 'admin'
ON DUPLICATE KEY UPDATE permission_code = VALUES(permission_code);

INSERT INTO role_permissions (role_id, permission_code)
SELECT r.id, p.permission_code
FROM roles r
JOIN (
    SELECT 'dashboard.view' AS permission_code
    UNION ALL SELECT 'verifications.history'
    UNION ALL SELECT 'anomalies.manage'
    UNION ALL SELECT 'assets.manage'
    UNION ALL SELECT 'pharmacy.manage'
) p
WHERE r.code = 'responsable_materiel'
ON DUPLICATE KEY UPDATE permission_code = VALUES(permission_code);
