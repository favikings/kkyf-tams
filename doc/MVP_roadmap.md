# KKYF Membership Portal v2
# MVP Roadmap for Cursor AI Agent

Version: 1.0  
Organization: Ken Katas Youth Foundation (KKYF)  
Technology: PHP 8+, MySQL 8+, JavaScript, PWA-ready frontend  

---

# Purpose

This roadmap instructs the AI development agent to build the KKYF Membership Portal v2 step-by-step.

The agent must NOT build all features at once.

The app must be built in controlled MVP phases, with each phase completed, tested, and committed before moving to the next.

---

# Source Documents

The AI agent must use these documents as the source of truth:

1. KKYF Membership Portal PRD v2.1
2. KKYF Membership Portal TDD v1.0
3. This MVP Roadmap

Priority order:

1. MVP Roadmap
2. TDD
3. PRD

If there is conflict, follow the MVP Roadmap first.

---

# Global Development Rules

The AI agent must:

- Build one phase at a time.
- Do not skip phases.
- Do not add extra features not listed in the current phase.
- Use clean PHP 8+ code.
- Use MySQL 8+.
- Use reusable services and controllers.
- Use role-based access control.
- Keep the frontend mobile-friendly.
- Keep the structure PWA-ready.
- Write database migrations before writing feature logic.
- Test each feature before proceeding.
- Commit after each completed phase.
- Ask before changing the roadmap.

---

# Recommended Folder Structure

```text
/kkyf-portal
  /app
    /Controllers
    /Models
    /Services
    /Middleware
    /Helpers
  /config
  /database
    /migrations
    /seeders
  /public
    /assets
    /css
    /js
    index.php
  /routes
  /storage
    /logs
    /uploads
  /tests
  .env.example
  README.md
```

---

# PHASE 0: Project Foundation

## Goal

Create the base project structure and prepare the app for development.

## Build

- Project folder structure
- Environment configuration
- Database connection
- Error logging
- Basic routing
- Basic layout
- .env.example
- README.md

## Database Tables

Create only:

- users
- activity_logs

## Acceptance Criteria

- App loads without error.
- Database connection works.
- Environment variables work.
- Errors are logged.
- Basic home route works.

## Do NOT Build Yet

- Members
- Tents
- Attendance
- SMS
- Offline sync
- Reports

---

# PHASE 1: Authentication and Roles

## Goal

Allow secure login and role-based access.

## Build

- Login
- Logout
- Password hashing
- Session handling
- Super Admin role
- Tent Admin role
- Auth middleware
- Role middleware

## User Roles

### Super Admin

Can access everything.

### Tent Admin

Can only access assigned tent data.

## Acceptance Criteria

- User can login.
- User can logout.
- Passwords are hashed.
- Unauthorized users are blocked.
- Tent Admin cannot access Super Admin pages.

---

# PHASE 2: Tent Management

## Goal

Allow Super Admin to create and manage tents.

## Build

- Create tent
- Edit tent
- View tent
- Deactivate tent
- Assign Tent Admin to tent

## Database Table

- tents

## Tent Fields

- name
- banner
- color
- leader_name
- leader_phone
- whatsapp_link
- status

## Acceptance Criteria

- Super Admin can create tents.
- Super Admin can assign Tent Admins.
- Tent Admin can only see assigned tent.
- Tents can be edited and deactivated.

---

# PHASE 3: Member Management

## Goal

Digitize all KKYF member records.

## Build

- Add member
- Edit member
- View member profile
- Search members
- Filter by tent
- Activate/deactivate member

## Database Table

- members

## Member Fields

- uuid
- full_name
- phone
- date_of_birth
- occupation
- school_name
- tent_id
- join_date
- profile_photo
- notes
- active_status

## Acceptance Criteria

- Super Admin can manage all members.
- Tent Admin can manage only assigned tent members.
- Members can be searched by name or phone.
- Member profile page displays complete member information.

---

# PHASE 4: Attendance MVP

## Goal

Replace manual Sunday attendance.

## Build

- Sunday attendance check-in
- Search member during check-in
- Prevent duplicate check-in for same Sunday
- Attendance history
- Basic Sunday attendance report

## Database Table

- attendance

## Attendance Fields

- member_id
- attendance_date
- service_type
- checked_by
- source
- created_at

## Business Rules

- A member cannot be checked in twice for the same Sunday.
- Tent Admin can check in only members in assigned tent.
- Super Admin can check in any member.
- Attendance date defaults to current Sunday.
- Retroactive attendance is not included in MVP.

## Acceptance Criteria

- Members can be checked in.
- Duplicate check-in is blocked.
- Attendance history displays correctly.
- Sunday report shows total attendance.

---

# PHASE 5: Basic Dashboard

## Goal

Give leadership immediate visibility.

## Build

### Super Admin Dashboard

Show:

- Total members
- Active members
- Total tents
- Attendance today
- This month attendance count

### Tent Admin Dashboard

Show:

- Tent members
- Tent attendance today
- Recent members
- Recent attendance

## Acceptance Criteria

- Super Admin sees global numbers.
- Tent Admin sees only assigned tent numbers.
- Dashboard data is accurate.

---

# PHASE 6: Migration MVP

## Goal

Move existing production data safely.

## Existing Tables

- Members
- Attendance_Log
- Admin_User
- Tents
- Sessions

## Build

- Database backup instruction
- Migration script structure
- Table-by-table migration
- Migration logs
- Validation summary

## New Tables Involved

- users
- tents
- members
- attendance
- migration_logs

## Migration Rules

- Preserve UUIDs where available.
- Preserve attendance history.
- Preserve tent assignments.
- Preserve admin accounts.
- Do not delete old tables.
- Migration must be repeatable in staging.

## Acceptance Criteria

- Old Members migrate into new members table.
- Old Attendance_Log migrates into attendance table.
- Old Admin_User migrates into users table.
- Old Tents migrate into tents table.
- Migration logs show success and errors.

---

# PHASE 7: First-Timer MVP

## Goal

Track and follow up first-time visitors.

## Build

- Add first-timer
- First-timer list
- Status update
- Follow-up notes
- Convert to regular member

## Database Table

- first_timers

## Statuses

- Pending
- Called
- Converted
- Not Returning

## Acceptance Criteria

- First-timer can be added.
- Tent Admin can update follow-up status.
- Converted first-timer becomes regular member.
- Follow-up notes are saved.

---

# PHASE 8: Absentee Tracker MVP

## Goal

Identify members who are missing consecutive Sundays.

## Build

- Absentee calculation command
- Absentee alert list
- Tent Admin absentee dashboard

## Database Table

- absentee_alerts

## Rules

- 2 missed Sundays = Early Warning
- 3 missed Sundays = Follow-Up Required
- 4+ missed Sundays = Critical

## Acceptance Criteria

- System identifies missed Sundays.
- Alerts are generated correctly.
- Tent Admin sees only assigned tent absentees.
- Resolved alerts can be marked resolved.

---

# PHASE 9: Attendance Streaks and Badges

## Goal

Reward consistent attendance.

## Build

- Attendance streak calculation
- Badge assignment
- Display badges on member profile

## Database Tables

- streaks
- badges
- member_badges

## Badges

- First Step
- On Fire
- Faithful
- Unstoppable
- 3-Month Member
- 6-Month Member
- 1-Year Member

## Acceptance Criteria

- Streaks calculate from attendance history.
- Badges are assigned automatically.
- Member profile displays earned badges.

---

# PHASE 10: SMS Communication

## Goal

Enable communication with members.

## Build

- AfricasTalking setup
- SMS logs
- Send SMS to one member
- Send SMS to one tent
- Super Admin bulk SMS

## Database Table

- sms_logs

## Acceptance Criteria

- SMS can be sent to a member.
- SMS can be sent to a tent.
- Bulk SMS is limited to Super Admin.
- SMS result is logged.

---

# PHASE 11: PWA Offline Attendance

## Goal

Allow attendance capture without internet.

## Build

- Service worker
- IndexedDB
- Offline attendance queue
- Background sync
- Conflict handling

## Rules

- Offline check-ins are stored locally.
- When internet returns, records sync to server.
- Duplicate attendance is blocked by server.
- Server is source of truth.

## Acceptance Criteria

- Attendance can be captured offline.
- Records sync when online.
- Duplicates are not created.
- Sync errors are visible to admin.

---

# PHASE 12: Reports and Exports

## Goal

Provide leadership reporting.

## Build

- Weekly report
- Monthly report
- Yearly report
- Sunday summary
- PDF export
- Excel export

## Acceptance Criteria

- Reports filter by date.
- Reports filter by tent.
- Super Admin can view all reports.
- Tent Admin can view assigned tent reports only.
- Exports download successfully.

---

# Features NOT in MVP

Do not build these until MVP is stable:

- Donations
- Sponsorships
- Camp registration
- Event ticketing
- WhatsApp automation
- Mobile app
- QR code attendance
- AI analytics

---

# Cursor AI Agent Starting Prompt

Use this exact prompt inside Cursor:

```text
You are building the KKYF Membership Portal v2.

Use the PRD, TDD, and MVP Roadmap as your source of truth.

Do not build the full app at once.

Start with PHASE 0 only.

Rules:
- Build one phase at a time.
- Do not add features from future phases.
- Use PHP 8+, MySQL 8+, JavaScript, and a PWA-ready frontend structure.
- Create clean folder structure.
- Create database migration files before feature logic.
- Use role-based access control.
- Keep Tent Admin restricted to assigned tent data.
- Write clean, documented, maintainable code.
- After completing each phase, summarize what was built, what files changed, and what tests were done.
- Wait for my approval before moving to the next phase.

Begin with PHASE 0: Project Foundation.
```

---

# Development Instruction

After each phase, the AI agent must provide:

```text
Phase completed:
Files created:
Database changes:
How to test:
Known issues:
Recommended next step:
```

The next phase should only begin after review and approval.
