CREATE TABLE IF NOT EXISTS badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO badges (name, description) VALUES
    ('First Step', 'Attended 1 consecutive Sunday'),
    ('On Fire', 'Attended 4 consecutive Sundays'),
    ('Faithful', 'Attended 12 consecutive Sundays'),
    ('Unstoppable', 'Attended 24 consecutive Sundays'),
    ('3-Month Member', 'Completed 3 months as a member'),
    ('6-Month Member', 'Completed 6 months as a member'),
    ('1-Year Member', 'Completed 1 year as a member')
ON DUPLICATE KEY UPDATE
    description = VALUES(description);
