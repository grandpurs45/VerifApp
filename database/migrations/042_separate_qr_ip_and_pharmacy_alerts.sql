ALTER TABLE qr_access_logs
    ADD COLUMN proxy_ip_address VARCHAR(45) NULL AFTER ip_address;

CREATE TABLE IF NOT EXISTS pharmacy_stock_alert_states (
    article_id INT PRIMARY KEY,
    caserne_id INT NOT NULL,
    alert_level VARCHAR(20) NOT NULL DEFAULT 'normal',
    last_notified_level VARCHAR(20) NULL,
    last_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    threshold_value DECIMAL(10,2) NULL,
    last_notified_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pharmacy_alert_caserne_level (caserne_id, alert_level),
    CONSTRAINT fk_pharmacy_alert_article
        FOREIGN KEY (article_id) REFERENCES pharmacie_articles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_pharmacy_alert_caserne
        FOREIGN KEY (caserne_id) REFERENCES casernes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
