CREATE TABLE IF NOT EXISTS auth_login_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caserne_id INT NULL,
    user_id INT NULL,
    identifier VARCHAR(190) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    user_agent VARCHAR(500) NOT NULL DEFAULT '',
    event_type ENUM('success','failure') NOT NULL,
    reason VARCHAR(80) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auth_login_events_created_at (created_at),
    INDEX idx_auth_login_events_caserne_date (caserne_id, created_at),
    INDEX idx_auth_login_events_user_date (user_id, created_at),
    INDEX idx_auth_login_events_type_date (event_type, created_at),
    CONSTRAINT fk_auth_login_events_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_auth_login_events_user
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
        ON DELETE SET NULL
);
