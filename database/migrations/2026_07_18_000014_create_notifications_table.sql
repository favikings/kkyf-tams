CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    type VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    entity_type VARCHAR(80) NULL,
    entity_id VARCHAR(80) NULL,
    dedupe_key VARCHAR(190) NULL,
    metadata JSON NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_created (user_id, created_at),
    INDEX idx_notifications_user_read (user_id, read_at),
    UNIQUE KEY uniq_notifications_dedupe (dedupe_key),
    CONSTRAINT fk_notifications_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_notifications_actor_user_id
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
