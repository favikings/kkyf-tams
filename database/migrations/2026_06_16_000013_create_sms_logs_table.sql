CREATE TABLE IF NOT EXISTS sms_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    scope ENUM('member', 'tent', 'bulk') NOT NULL,
    member_id BIGINT UNSIGNED NULL,
    tent_id BIGINT UNSIGNED NULL,
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    recipients_snapshot TEXT NULL,
    message TEXT NOT NULL,
    provider VARCHAR(60) NOT NULL DEFAULT 'log_only',
    provider_message_id VARCHAR(190) NULL,
    status ENUM('pending', 'sent', 'failed', 'simulated') NOT NULL DEFAULT 'pending',
    response_payload LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    INDEX idx_sms_logs_user_id (user_id),
    INDEX idx_sms_logs_scope (scope),
    INDEX idx_sms_logs_member_id (member_id),
    INDEX idx_sms_logs_tent_id (tent_id),
    INDEX idx_sms_logs_status (status),
    CONSTRAINT fk_sms_logs_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_sms_logs_member_id
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sms_logs_tent_id
        FOREIGN KEY (tent_id) REFERENCES tents(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
