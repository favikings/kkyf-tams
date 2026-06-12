CREATE TABLE IF NOT EXISTS attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    service_type VARCHAR(60) NOT NULL DEFAULT 'Sunday Service',
    checked_by BIGINT UNSIGNED NOT NULL,
    source ENUM('web', 'offline') NOT NULL DEFAULT 'web',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance_member_date (member_id, attendance_date),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_attendance_checked_by (checked_by),
    CONSTRAINT fk_attendance_member_id
        FOREIGN KEY (member_id) REFERENCES members(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_checked_by
        FOREIGN KEY (checked_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
