-- =====================================================================
-- KKYF Membership Portal v3 — Migration 003
-- Adds secure password-recovery state: session-version invalidation,
-- single-use reset-token hashes, and an anti-abuse request ledger.
-- Structure only; no seed rows. Safe to run more than once.
-- =====================================================================

SET @auth_version_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'auth_version'
);

SET @auth_version_sql = IF(
    @auth_version_exists = 0,
    'ALTER TABLE users ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash',
    'SET @auth_version_noop = 1'
);

PREPARE auth_version_statement FROM @auth_version_sql;
EXECUTE auth_version_statement;
DEALLOCATE PREPARE auth_version_statement;

CREATE TABLE IF NOT EXISTS password_resets (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    token_hash   CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    request_ip   VARCHAR(45) NOT NULL,
    expires_at   DATETIME NOT NULL,
    used_at      DATETIME NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_resets_token_hash (token_hash),
    INDEX idx_password_resets_user_state (user_id, used_at, expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_attempts (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_hash   CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    request_ip   VARCHAR(45) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_attempts_email_time (email_hash, created_at),
    INDEX idx_password_reset_attempts_ip_time (request_ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
