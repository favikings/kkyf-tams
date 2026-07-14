-- Clone legacy production tables into the isolated names expected by the v2 migration scripts.
-- Run this only after restoring or connecting to a database that already contains:
-- Tents, Admin_User, Members, Attendance_Log
--
-- This script is additive. It does not rename, drop, or alter the original legacy tables.

CREATE TABLE IF NOT EXISTS legacy_tents LIKE Tents;
INSERT IGNORE INTO legacy_tents SELECT * FROM Tents;

CREATE TABLE IF NOT EXISTS legacy_admin_user LIKE Admin_User;
INSERT IGNORE INTO legacy_admin_user SELECT * FROM Admin_User;

CREATE TABLE IF NOT EXISTS legacy_members LIKE Members;
INSERT IGNORE INTO legacy_members SELECT * FROM Members;

CREATE TABLE IF NOT EXISTS legacy_attendance_log LIKE Attendance_Log;
INSERT IGNORE INTO legacy_attendance_log SELECT * FROM Attendance_Log;
