ALTER TABLE vehicules
    ADD COLUMN verification_active TINYINT(1) NOT NULL DEFAULT 1 AFTER actif;

CREATE TABLE IF NOT EXISTS anomaly_occurrences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anomalie_id INT NOT NULL,
    verification_ligne_id INT NOT NULL,
    date_remontee DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_anomaly_occurrence_line (verification_ligne_id),
    INDEX idx_anomaly_occurrences_anomaly_date (anomalie_id, date_remontee),
    CONSTRAINT fk_anomaly_occurrences_anomaly
        FOREIGN KEY (anomalie_id) REFERENCES anomalies(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_anomaly_occurrences_line
        FOREIGN KEY (verification_ligne_id) REFERENCES verification_lignes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO anomaly_occurrences (anomalie_id, verification_ligne_id, date_remontee)
SELECT id, verification_ligne_id, date_creation
FROM anomalies;

CREATE TABLE IF NOT EXISTS notification_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NOT NULL,
    nom VARCHAR(120) NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_group_name (caserne_id, nom),
    CONSTRAINT fk_notification_groups_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_group_members (
    group_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, utilisateur_id),
    INDEX idx_notification_group_members_user (utilisateur_id),
    CONSTRAINT fk_notification_group_members_group
        FOREIGN KEY (group_id) REFERENCES notification_groups(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notification_group_members_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_access_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NOT NULL,
    module VARCHAR(30) NOT NULL,
    vehicule_id INT NULL,
    utilisateur_id INT NULL,
    nom_saisi VARCHAR(150) NULL,
    token_fingerprint CHAR(64) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    referer VARCHAR(500) NULL,
    session_fingerprint CHAR(64) NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    identity_updated_at DATETIME NULL,
    INDEX idx_qr_access_caserne_date (caserne_id, opened_at),
    INDEX idx_qr_access_vehicle_date (vehicule_id, opened_at),
    CONSTRAINT fk_qr_access_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_qr_access_vehicle
        FOREIGN KEY (vehicule_id) REFERENCES vehicules(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_qr_access_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
