CREATE TABLE IF NOT EXISTS member_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member_badge (member_id, badge_id),
    INDEX idx_member_badges_member (member_id),
    INDEX idx_member_badges_badge (badge_id),
    CONSTRAINT fk_member_badges_member_id
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_member_badges_badge_id
        FOREIGN KEY (badge_id) REFERENCES badges(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
