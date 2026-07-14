CREATE TABLE IF NOT EXISTS members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    date_of_birth CHAR(5) NULL,
    occupation ENUM('Student', 'Worker', 'Alumni') NOT NULL DEFAULT 'Student',
    school_name VARCHAR(150) NULL,
    tent_id BIGINT UNSIGNED NOT NULL,
    join_date DATE NULL,
    profile_photo VARCHAR(255) NULL,
    notes TEXT NULL,
    active_status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_members_phone (phone),
    INDEX idx_members_full_name (full_name),
    INDEX idx_members_tent_id (tent_id),
    INDEX idx_members_active_status (active_status),
    CONSTRAINT fk_members_tent_id
        FOREIGN KEY (tent_id) REFERENCES tents(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
