# KKYF Membership Portal v2
## Product Requirements Document (PRD)

### Organization
KEN KATAS YOUTH FOUNDATION (KKYF)

### Platform
Progressive Web App (PWA)

### Technology Stack
PHP + MySQL

### Version
v2.1 (Migration Ready)

---

# Executive Summary

This document defines the requirements for upgrading the existing KKYF Membership Portal into Version 2 while preserving all live production data.

The system currently contains live records for:

- Members
- Attendance
- Tents
- Administrators
- Audit Logs
- Sessions

Version 2 shall extend the existing platform without destroying or replacing historical data.

---

# 1. Project Goals

- Replace manual attendance processes.
- Centralize membership management.
- Improve analytics and reporting.
- Introduce first-timer follow-up workflows.
- Introduce absentee tracking.
- Introduce attendance streaks and badges.
- Support SMS communication.
- Support offline attendance capture.
- Preserve all existing production data.

---

# 2. Existing Production Database

Current production tables:

- Tents
- Members
- Attendance_Log
- Admin_User
- Sessions
- Audit_Log
- Password_Resets

These tables form the migration source for Version 2.

---

# 3. User Roles

## Super Admin

Permissions:

- Full system access
- Manage all tents
- Manage all members
- View reports
- Send SMS
- Manage imports and migrations
- Manage attendance windows
- View audit logs

## Tent Admin

Permissions:

- Manage assigned tent
- Manage attendance
- Manage first-timers
- Manage follow-up records
- View tent analytics

Restrictions:

- Cannot access other tents
- Cannot send bulk SMS
- Cannot manage migrations

---

# 4. Member Management

## Member Fields

- UUID
- Full Name
- Phone Number
- Date of Birth
- Occupation
- School Name
- Tent
- Join Date
- Profile Photo
- Member Notes
- Active Status

## Occupation Values

- Student
- Worker
- Alumni

## Active Status Values

- Active
- Inactive

---

# 5. Attendance Management

Features:

- Sunday check-in
- Retroactive attendance
- Offline attendance capture
- Attendance history
- Attendance exports

---

# 6. First-Timer Follow-Up

Statuses:

- Pending
- Called
- Converted
- Not Returning

---

# 7. Absentee Tracker

Rules:

- Early Warning = 2 missed Sundays
- Follow-Up Required = 3 missed Sundays
- Critical = 4+ missed Sundays

---

# 8. Birthdays & Anniversaries

Features:

- Birthday reminders
- Anniversary tracking
- Milestone notifications

---

# 9. Attendance Streaks & Badges

Attendance streaks are automatically calculated from attendance history.

Badges include:

- First Step
- On Fire
- Faithful
- Unstoppable
- 3-Month Member
- 6-Month Member
- 1-Year Member

---

# 10. Tent Profiles

Each tent shall have:

- Banner
- Color
- Leader Name
- Leader Phone
- WhatsApp Group Link

---

# 11. Dashboards

Super Admin Dashboard

- Total Members
- Attendance Metrics
- Growth Metrics
- First Timers
- Critical Absentees
- Upcoming Birthdays

Tent Dashboard

- Tent Attendance
- First Timers
- Absentee Alerts

---

# 12. Reports

Available Reports:

- Weekly
- Monthly
- Yearly
- Sunday Summary

Export Formats:

- PDF
- Excel

---

# 13. Communications

AfricasTalking SMS Integration

Features:

- Bulk SMS
- Tent SMS
- Birthday Reminders

---

# 14. Activity Logs

Track:

- Member actions
- Attendance actions
- Admin actions
- Import actions
- Migration actions

---

# 15. Data Import & Migration Wizard

Supported Formats:

- CSV
- XLSX

Import Types:

- Members
- Attendance
- Tent Admins
- Full Migration

Features:

- Field Mapping
- Validation
- Preview
- Error Reports
- Saved Mappings

Import Modes:

- Add New Only
- Update Existing
- Add and Update

---

# 16. Progressive Web App

Features:

- Installable
- Offline Mode
- IndexedDB
- Background Sync

---

# 17. Database Entities

Core Tables:

- users
- tents
- members
- attendance
- first_timers
- absentee_alerts
- badges
- member_badges
- streaks
- anniversaries
- sms_logs
- activity_logs
- check_in_windows
- member_notes

Migration Tables:

- import_jobs
- import_logs
- import_mappings
- migration_logs

---

# 18. Non-Functional Requirements

- Support 500+ check-ins
- Role-based security
- Offline support
- Scalable architecture
- Duplicate detection

---

# 19. Technical Architecture

Frontend:

- HTML5
- CSS3
- JavaScript
- PWA

Backend:

- PHP 8+
- REST API

Database:

- MySQL 8+

Integrations:

- AfricasTalking
- WhatsApp Click-to-Chat

---

# 20. Existing Database Migration

Migration Source Tables:

- Members
- Attendance_Log
- Admin_User
- Tents
- Sessions

Migration Rules:

- Preserve UUIDs
- Preserve attendance history
- Preserve tent assignments
- Preserve admin accounts
- Preserve first-timer records

Derived Data:

- Attendance Streaks
- Badges
- Absentee Alerts
- Anniversary Milestones

Migration Requirements:

- Full backup before migration
- Repeatable staging migrations
- Migration audit logs
- Rollback support

---

# 21. Rollout Strategy

Phase 1:

- Database Backup
- Staging Deployment

Phase 2:

- Schema Migration
- Data Validation

Phase 3:

- Feature Testing
- User Acceptance Testing

Phase 4:

- Production Deployment

Phase 5:

- Monitoring and Optimization
