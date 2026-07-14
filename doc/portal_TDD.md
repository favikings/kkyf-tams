# KKYF Membership Portal v2
# Technical Design Document (TDD)

Version: 1.0
Organization: Ken Katas Youth Foundation (KKYF)
Platform: Progressive Web App (PWA)
Technology: PHP 8+, MySQL 8+, JavaScript, IndexedDB

---

# 1. System Architecture

Frontend (PWA)
→ REST API
→ PHP Service Layer
→ MySQL Database

Offline Layer:
- IndexedDB
- Background Sync
- Sync Queue
- Conflict Resolution Engine

---

# 2. User Roles

## Super Admin
- Full access
- SMS management
- Migration management
- Reports
- Audit logs

## Tent Admin
- Manage assigned tent
- Attendance management
- First timer follow-up
- Tent analytics

---

# 3. Core Database Schema

## users
- id (PK)
- uuid
- full_name
- email
- phone
- password_hash
- role
- tent_id
- status
- created_at
- updated_at

## tents
- id (PK)
- uuid
- name
- banner
- color
- leader_name
- leader_phone
- whatsapp_link
- created_at

## members
- id (PK)
- uuid
- full_name
- phone
- dob
- occupation
- school_name
- tent_id
- join_date
- profile_photo
- notes
- active_status
- created_at

## attendance
- id (PK)
- member_id
- attendance_date
- service_type
- checked_by
- source
- created_at

## first_timers
- id (PK)
- member_id
- first_visit_date
- status
- followup_notes
- updated_at

## absentee_alerts
- id (PK)
- member_id
- missed_count
- alert_level
- resolved

## badges
- id (PK)
- name
- description

## member_badges
- id (PK)
- member_id
- badge_id
- awarded_at

## sms_logs
- id (PK)
- recipient
- message
- status
- provider_response
- created_at

## activity_logs
- id (PK)
- user_id
- action
- entity_type
- entity_id
- created_at

---

# 4. Migration Mapping

| Existing Table | New Table |
|---------------|-----------|
| Members | members |
| Attendance_Log | attendance |
| Admin_User | users |
| Tents | tents |
| Sessions | sessions |

Migration Rules:
- Preserve UUIDs
- Preserve historical attendance
- Preserve admin accounts
- Preserve tent assignments
- Log all migration actions

---

# 5. REST API Specification

## Authentication

POST /api/auth/login

POST /api/auth/logout

POST /api/auth/forgot-password

POST /api/auth/reset-password

## Members

GET /api/members

POST /api/members

PUT /api/members/{id}

DELETE /api/members/{id}

## Attendance

POST /api/attendance/checkin

GET /api/attendance/history

POST /api/attendance/retroactive

## Reports

GET /api/reports/weekly

GET /api/reports/monthly

GET /api/reports/yearly

---

# 6. Attendance Streak Algorithm

Definition:
Consecutive attended Sundays.

Algorithm:

1. Sort attendance by date.
2. Compare consecutive Sundays.
3. If next Sunday attended:
   streak += 1
4. If Sunday missed:
   streak = 0
5. Save streak value.

Badges:

- First Step = 1 Sunday
- On Fire = 4 Sundays
- Faithful = 12 Sundays
- Unstoppable = 24 Sundays

---

# 7. Absentee Calculation Algorithm

Rules:

2 missed Sundays:
- Early Warning

3 missed Sundays:
- Follow-Up Required

4+ missed Sundays:
- Critical

Process:

Every Sunday night:

1. Identify active members.
2. Count consecutive missed Sundays.
3. Generate alerts.
4. Notify Tent Admin.

---

# 8. First-Timer Conversion Logic

Workflow:

Pending
→ Called
→ Converted

OR

Pending
→ Called
→ Not Returning

Rules:

- First visit automatically creates record.
- Tent Admin updates status.
- Converted members become regular members.

---

# 9. Import Wizard Specification

Supported:

- CSV
- XLSX

Import Types:

- Members
- Attendance
- Tent Admins
- Full Migration

Features:

- Column Mapping
- Preview
- Validation
- Error Report
- Duplicate Detection
- Saved Templates

---

# 10. PWA Offline Sync Architecture

Offline Storage:

- IndexedDB

Queue:

offline_actions

Fields:

- action_type
- payload
- timestamp
- sync_status

Sync Process:

1. User performs action offline.
2. Action stored locally.
3. Internet returns.
4. Background Sync executes.
5. API receives payload.
6. Local item marked synced.

Conflict Resolution:

- Server wins for edits.
- Duplicate attendance blocked.
- UUID used for reconciliation.

---

# 11. Security

Authentication:
- JWT
- Secure Password Hashing

Authorization:
- Role Based Access Control

Logging:
- Audit trail for all critical actions

Protection:
- CSRF Protection
- Rate Limiting
- Input Validation

---

# 12. Performance Requirements

- Support 500+ check-ins per service
- API response < 2 seconds
- Offline attendance available
- Mobile-first design

---

# 13. Deployment Strategy

Phase 1:
- Backup production database

Phase 2:
- Deploy staging environment

Phase 3:
- Execute migration

Phase 4:
- Validate records

Phase 5:
- Production release

Phase 6:
- Monitoring and optimization
