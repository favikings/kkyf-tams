-- =====================================================================
-- KKYF Membership Portal v3 — Phase 1 Schema (No Seed Data)
-- Run this once on a fresh/empty database.
-- Engine: InnoDB | Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- tents — created manually by Super Admin after first login (no seed)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tents (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL UNIQUE,
    color_hex      VARCHAR(7)   NULL DEFAULT '#2E86C1',
    banner_path    VARCHAR(255) NULL,
    leader_name    VARCHAR(150) NULL,
    leader_phone   VARCHAR(20)  NULL,
    whatsapp_link  VARCHAR(512) NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- users — Super Admins (unlimited; created via scripts/create-super-admin.php
--         or by promotion — see promoted_by/promoted_at below)
--         Tent Admins (self-register, require approval)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(150) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    phone          VARCHAR(20)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('super_admin','tent_admin') NOT NULL,
    tent_id        INT UNSIGNED NULL,               -- NULL for super_admin
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    approved_by    INT UNSIGNED NULL,               -- who approved the tent_admin registration
    approved_at    TIMESTAMP NULL,
    promoted_by    INT UNSIGNED NULL,               -- set if a tent_admin was promoted to super_admin
    promoted_at    TIMESTAMP NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tent_id) REFERENCES tents(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (promoted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- NOTE: role='super_admin' is not unique/limited — the app supports multiple
-- Super Admin rows simultaneously. tent_id is set to NULL on promotion.

-- ---------------------------------------------------------------------
-- members
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tent_id        INT UNSIGNED NOT NULL,
    full_name      VARCHAR(200) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    photo_path     VARCHAR(255) NULL,
    birth_month    TINYINT UNSIGNED NULL,
    birth_day      TINYINT UNSIGNED NULL,
    occupation     ENUM('student','worker') NOT NULL DEFAULT 'worker',
    school_name    VARCHAR(200) NULL,
    is_first_timer TINYINT(1)  NOT NULL DEFAULT 1,
    first_seen_sunday DATE     NULL,
    join_date      DATE        NOT NULL,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by     INT UNSIGNED NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tent_id)    REFERENCES tents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_members_tent (tent_id),
    INDEX idx_members_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- attendance — one row per member per Sunday
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id      INT UNSIGNED NOT NULL,
    tent_id        INT UNSIGNED NOT NULL,
    sunday_date    DATE        NOT NULL,
    checked_in_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    marked_by      INT UNSIGNED NULL,
    is_retroactive TINYINT(1)  NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_sunday (member_id, sunday_date),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (tent_id)   REFERENCES tents(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attendance_sunday (sunday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- first_timer_followups
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS first_timer_followups (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id      INT UNSIGNED NOT NULL UNIQUE,
    tent_id        INT UNSIGNED NOT NULL,
    first_visit    DATE        NOT NULL,
    status         ENUM('pending','called','converted','not_returning') NOT NULL DEFAULT 'pending',
    assigned_to    INT UNSIGNED NULL,
    notes          TEXT NULL,
    updated_by     INT UNSIGNED NULL,
    updated_at     TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id)   REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (tent_id)     REFERENCES tents(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- member_notes — private admin notes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id   INT UNSIGNED NOT NULL,
    admin_id    INT UNSIGNED NOT NULL,
    note        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- NOTE: This file creates structure only — zero rows are inserted.
-- To log in for the first time, run: php scripts/create-super-admin.php
-- Then log in as Super Admin and create your tents from the UI.
-- =====================================================================
