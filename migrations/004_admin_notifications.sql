-- =====================================================================
-- KKYF Membership Portal v3 — Migration 004
-- Adds persistent Super Admin notifications and Web Push subscriptions,
-- and repairs/enforces the canonical normalized users.email uniqueness.
-- Structure only; no seed rows. Safe to run more than once.
--
-- If this migration reports duplicate normalized emails, review them with:
--   SELECT LOWER(TRIM(email)) AS normalized_email, GROUP_CONCAT(id ORDER BY id) AS user_ids
--   FROM users GROUP BY LOWER(TRIM(email)) HAVING COUNT(*) > 1;
-- Resolve those rows deliberately, then run this migration again.
-- =====================================================================

CREATE TABLE IF NOT EXISTS notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    type            VARCHAR(50) NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         VARCHAR(255) NOT NULL,
    action_url      VARCHAR(255) NULL,
    related_user_id INT UNSIGNED NULL,
    read_at         DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_unread (user_id, read_at, created_at),
    INDEX idx_notifications_related_user (related_user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    endpoint_hash    CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    endpoint         TEXT NOT NULL,
    public_key       VARCHAR(255) NOT NULL,
    auth_token       VARCHAR(255) NOT NULL,
    content_encoding VARCHAR(20) NOT NULL DEFAULT 'aes128gcm',
    user_agent       VARCHAR(255) NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_subscriptions_endpoint_hash (endpoint_hash),
    INDEX idx_push_subscriptions_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @normalized_email_duplicates = (
    SELECT COUNT(*)
    FROM (
        SELECT LOWER(TRIM(email)) AS normalized_email
        FROM users
        GROUP BY LOWER(TRIM(email))
        HAVING COUNT(*) > 1
    ) duplicate_groups
);

-- Portable MySQL 8 / MariaDB guard: the second insert is a no-op when clean,
-- but deliberately raises a duplicate-key error with a descriptive table name
-- when normalized duplicates exist. No CREATE ROUTINE privilege is required.
CREATE TEMPORARY TABLE migration_004_duplicate_normalized_emails_resolve_first (
    guard_id TINYINT UNSIGNED NOT NULL,
    UNIQUE KEY resolve_duplicate_normalized_emails_before_migration_004 (guard_id)
);
INSERT INTO migration_004_duplicate_normalized_emails_resolve_first (guard_id) VALUES (1);
INSERT INTO migration_004_duplicate_normalized_emails_resolve_first (guard_id)
SELECT 1 WHERE @normalized_email_duplicates > 0;
DROP TEMPORARY TABLE migration_004_duplicate_normalized_emails_resolve_first;

UPDATE users
SET email = LOWER(TRIM(email))
WHERE email <> LOWER(TRIM(email));

SET @users_email_unique_exists = (
    SELECT COUNT(*)
    FROM (
        SELECT INDEX_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'email'
          AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME
        HAVING COUNT(*) = 1
    ) unique_email_indexes
);

SET @users_email_unique_sql = IF(
    @users_email_unique_exists = 0,
    'ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)',
    'SET @users_email_unique_noop = 1'
);

PREPARE users_email_unique_statement FROM @users_email_unique_sql;
EXECUTE users_email_unique_statement;
DEALLOCATE PREPARE users_email_unique_statement;
