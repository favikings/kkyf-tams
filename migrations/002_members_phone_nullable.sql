-- =====================================================================
-- KKYF Membership Portal v3 — Migration 002
-- Makes members.phone optional: not every member has a phone number on
-- file. Run once, after 001_schema.sql, on any existing database.
-- =====================================================================

ALTER TABLE members MODIFY COLUMN phone VARCHAR(20) NULL;
