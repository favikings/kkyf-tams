CREATE TABLE IF NOT EXISTS migration_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_table VARCHAR(100) NOT NULL,
    source_id VARCHAR(100) NULL,
    target_table VARCHAR(100) NULL,
    target_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'success', 'skipped', 'error') NOT NULL,
    message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_migration_logs_source (source_table, source_id),
    INDEX idx_migration_logs_target (target_table, target_id),
    INDEX idx_migration_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
