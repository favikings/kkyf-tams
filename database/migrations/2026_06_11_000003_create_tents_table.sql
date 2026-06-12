CREATE TABLE IF NOT EXISTS tents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL UNIQUE,
    banner VARCHAR(255) NULL,
    color VARCHAR(20) NULL,
    leader_name VARCHAR(150) NULL,
    leader_phone VARCHAR(30) NULL,
    whatsapp_link VARCHAR(255) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tents_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
