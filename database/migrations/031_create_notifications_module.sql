CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NOT NULL,
    event_code VARCHAR(80) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'info',
    titre VARCHAR(190) NOT NULL,
    message VARCHAR(500) NOT NULL,
    lien VARCHAR(255) NULL,
    acteur_utilisateur_id INT NULL,
    acteur_nom VARCHAR(150) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_caserne_date (caserne_id, created_at),
    INDEX idx_notifications_event (event_code),
    CONSTRAINT fk_notifications_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notifications_acteur
        FOREIGN KEY (acteur_utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    lu_le DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_recipients_user_read (utilisateur_id, lu, created_at),
    INDEX idx_notif_recipients_notif (notification_id),
    UNIQUE KEY uq_notif_recipient (notification_id, utilisateur_id),
    CONSTRAINT fk_notif_recipients_notification
        FOREIGN KEY (notification_id) REFERENCES notifications(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notif_recipients_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    event_code VARCHAR(80) NOT NULL,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notif_subscription (utilisateur_id, event_code),
    CONSTRAINT fk_notif_subscriptions_user
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
