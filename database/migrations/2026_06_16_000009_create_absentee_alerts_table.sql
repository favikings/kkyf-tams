CREATE TABLE IF NOT EXISTS absentee_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    missed_count INT UNSIGNED NOT NULL DEFAULT 0,
    alert_level ENUM('Early Warning', 'Follow-Up Required', 'Critical') NOT NULL,
    resolved TINYINT(1) NOT NULL DEFAULT 0,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_absentee_member (member_id),
    INDEX idx_absentee_alert_level (alert_level),
    INDEX idx_absentee_resolved (resolved),
    CONSTRAINT fk_absentee_member_id
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
