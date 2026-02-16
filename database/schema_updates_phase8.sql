-- Phase 8: Schema Updates for Forgot Password & Email Login

-- 1. Add Email to Admin_User
-- We use IGNORE or a procedure to avoid error if exists, but for simple scripts, we trust the flow or handle in PHP
-- However, straight SQL is requested.
-- ALTER IGNORE is deprecated. We will try standard ALTER. If it fails, we handle in PHP runner.

ALTER TABLE Admin_User ADD COLUMN Email VARCHAR(255) UNIQUE AFTER Username;

-- 2. Create Password_Resets Table
CREATE TABLE IF NOT EXISTS Password_Resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (token),
    INDEX (email)
) ENGINE=InnoDB;
