CREATE TABLE IF NOT EXISTS first_timers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    tent_id BIGINT UNSIGNED NOT NULL,
    first_visit_date DATE NOT NULL,
    status ENUM('Pending', 'Called', 'Converted', 'Not Returning') NOT NULL DEFAULT 'Pending',
    followup_notes TEXT NULL,
    converted_member_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_first_timers_full_name (full_name),
    INDEX idx_first_timers_phone (phone),
    INDEX idx_first_timers_tent_id (tent_id),
    INDEX idx_first_timers_status (status),
    INDEX idx_first_timers_first_visit_date (first_visit_date),
    CONSTRAINT fk_first_timers_tent_id
        FOREIGN KEY (tent_id) REFERENCES tents(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_first_timers_converted_member_id
        FOREIGN KEY (converted_member_id) REFERENCES members(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_first_timers_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
