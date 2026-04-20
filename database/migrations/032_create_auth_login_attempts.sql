CREATE TABLE IF NOT EXISTS auth_login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL,
    UNIQUE KEY uq_auth_login_attempts_identifier_ip (identifier, ip_address),
    KEY idx_auth_login_attempts_locked_until (locked_until),
    KEY idx_auth_login_attempts_last_attempt_at (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
