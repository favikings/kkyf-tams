# KKYF Membership Portal
### Product Requirements Document — v3.0 "Clean Rebuild" Edition

| Field | Detail |
|---|---|
| Organization | KKYF (Kingsway Kingdom Youth Fellowship) |
| Document Type | PRD v3.0 — Clean Rebuild |
| Supersedes | v1.0 and v2.0 (technical approach only — product goals carried forward) |
| Platform | Progressive Web App (PWA), mobile-first |
| Stack | Pure PHP 8+ · MySQL 8 · Vanilla JS + Alpine.js · Tailwind CSS (CDN) |
| Build Method | Fresh branch, entirely new codebase, zero reused files from old app |
| Database Policy | Clean schema, zero seed data (except one-time Super Admin creation script) |
| Status | Ready for phased development |

---

## Companion Documents

This PRD defines *what* to build. Three companion documents define *exactly how*, so the build agent never has to guess:

| Document | Covers |
|---|---|
| `DESIGN_SYSTEM.md` | All color tokens (light + dark), typography, spacing, radii, elevation |
| `COMPONENTS.md` | Every reusable UI component with exact markup |
| `TECH_SPEC.md` | File conventions, DB access pattern, auth contract, API shape, dark-mode implementation, validation rules |

Read all four documents before starting Step 1 of `KKYF_Build_Prompts.md`.

## 0. Why a Clean Rebuild

The previous implementation grew complex and difficult to extend. This rebuild keeps the same product goals — attendance tracking, first-timer detection, follow-up, birthdays, reporting — but starts from an empty repository branch, an empty database, and a new visual design system. Nothing from the old codebase or old database is reused. Existing member records are migrated once, deliberately, via the CSV/Excel import tool — not by copying the old database.

## 1. Project Overview

KKYF is a youth fellowship that meets every Sunday. The organization is structured into Tents — sub-groups, each run by one or more Tent Admins. This portal replaces manual, paper-based attendance with a fast, mobile-first digital check-in system, while adding first-timer detection, follow-up tracking, birthday awareness, and reporting.

### 1.1 Goals

- Replace manual attendance with a fast, mobile check-in flow — tap a name, done
- Centralize member data across all tents in one clean database
- Detect first-timers automatically at the point of check-in, with an inline add-member flow
- Track first-timer follow-up through to conversion
- Support unlimited tents — the 10 starting tents plus any created later
- Allow multiple admins per tent (not just one)
- Give the Super Admin a full cross-tent view; keep Tent Admins scoped strictly to their tent
- Ship a distinctive, non-templated visual design — not a generic admin theme

### 1.2 Organizational Structure

KKYF starts with 10 named tents, created manually by the Super Admin after first login (not seeded): Amazing, Exceptional, Elevators, Generals, Highflyers, House of Eden, Lacasa De Kratos, Otis, Pathfinders, Seal of Love. The system must support creating additional tents at any time — tent count is not hardcoded anywhere in the app.

Each tent can have multiple Tent Admins (not capped at one). A member belongs to exactly one tent permanently.

## 2. Roles & Permissions

| Role | Count | Creation | Scope |
|---|---|---|---|
| Super Admin | Unlimited — starts with 1, more can be added anytime | CLI script (`scripts/create-super-admin.php`, re-runnable) **or** promotion of an existing Tent Admin by a current Super Admin | All tents, full access |
| Tent Admin | Unlimited, many per tent | Public registration form → status = pending → Super Admin approves | Own tent only |

Approval is required — a Tent Admin cannot log in until a Super Admin approves their registration request from the Tent Admins panel.

**Multiple Super Admins:** the system is not limited to one Super Admin. `create-super-admin.php` can be run more than once to create additional Super Admin accounts, and any existing Super Admin can promote an approved Tent Admin to Super Admin directly from the Tent Admins panel. Promotions are deliberate, logged actions — never automatic or bulk.

### 2.1 Super Admin Capabilities

- View, add, edit, deactivate members in any tent
- Create, edit, and deactivate tents — unlimited, anytime
- Approve or reject Tent Admin registration requests
- Assign/revoke Tent Admin access, including multiple admins per tent
- Mark attendance for any member, any tent, current or past Sunday
- View the CSV/Excel import wizard and migrate legacy member data
- View all dashboards, all first-timer trackers, all follow-ups, across every tent
- Click-to-call any member

### 2.2 Tent Admin Capabilities

- View, add, edit members within their own tent only
- Mark attendance (current Sunday and retroactive) for their tent's members
- Use the check-in screen, including the inline first-timer add flow
- View and update their tent's first-timer follow-up tracker
- View their tent dashboard, attendance history, and upcoming birthdays
- Click-to-call members in their tent

*Tent Admins cannot access other tents' data, cannot manage tents or admin approvals, and cannot access the import wizard.*

## 3. Member Management

| Field | Type | Required | Notes |
|---|---|---|---|
| Full Name | Text | Yes | |
| Phone Number | Text (E.164 preferred) | Yes | Click-to-call, future SMS/WhatsApp |
| Profile Photo | Image | No | Max 2MB |
| Date of Birth | Month + Day | No | No year required |
| Occupation | Enum | Yes | Student / Worker |
| School Name | Text | If Student | |
| Tent | FK | Yes | Fixed at creation |
| First Timer Flag | Boolean | Auto | Set true on creation, historical — never changes back |
| Join Date | Date | Auto | Date record created |
| Status | Enum | Yes | Active / Inactive |

### 3.1 First-Timer Detection Flow (core interaction)

1. Tent Admin opens the Sunday check-in screen and searches for a member's name
2. If the name is not found, the UI offers: "Not on the list? Add as a new member"
3. Admin fills a short inline form: name, phone, DOB, occupation, school (if student) — no page navigation
4. On submit, the member is created, auto-flagged as a first-timer, and immediately checked in for that Sunday
5. A `first_timer_followups` record is created automatically, status = pending
6. On any later Sunday, the member already exists — admin just searches and taps to check in

## 4. Attendance System

### 4.1 Check-in Flow

- Admin (or self-service member) opens the check-in page for the current Sunday
- Searchable list of the tent's members loads, filtered as the admin types
- Tap a name → marked present instantly, with a toast confirmation
- Duplicate check-in for the same member/Sunday is prevented at the database level (unique constraint)

### 4.2 Retroactive Marking

Both roles can navigate to any past Sunday and mark attendance after the fact; entries are flagged `is_retroactive = 1` for future audit-log support.

### 4.3 Offline Mode — Phase 2

The v1 build ships fully online. Offline queueing via IndexedDB + Service Worker background sync is designed for but deferred to Phase 2 once the core flows are validated live (see Section 9).

## 5. First-Timer Follow-Up Tracker

| Status | Set By | Meaning |
|---|---|---|
| Pending | Auto on creation | No contact attempted yet |
| Called | Any tent admin / super admin | Reached out by phone |
| Converted | Any tent admin / super admin | Became a returning member |
| Not Returning | Any tent admin / super admin | Confirmed will not return |

Records can optionally be assigned to a specific admin within the tent for follow-up ownership. Super Admin sees all tents' trackers, filterable by tent; Tent Admin sees only their own.

## 6. Data Migration — Legacy Member Import

- Export existing members from the old portal to Excel/CSV
- Super Admin uses the Import Wizard: select target tent → upload file → map columns → preview → confirm
- Required columns: Full Name, Phone. Optional: Birth Month, Birth Day, Occupation, School Name
- Invalid rows are skipped and reported — valid rows are inserted with `is_first_timer = 0` and `join_date = today` (historical join dates are not required for migration)
- No attendance history is imported — attendance starts fresh from go-live Sunday

## 7. Design System

The visual identity is defined by the client-supplied Material 3 token set — **not** improvised per-page. Full specification lives in two companion documents that every build step must reference:

- **`DESIGN_SYSTEM.md`** — every color token in light *and* dark mode (as ready-to-paste CSS variables + Tailwind config), the full typography scale (Geist for display/labels, Inter for body), spacing/radius/elevation rules.
- **`COMPONENTS.md`** — every reusable UI piece (buttons, inputs, member cards, status badges, nav, modals, toasts, dashboard widgets, empty states) defined once with exact markup, built entirely from the tokens above.

Brand direction: Corporate/Modern, high-fidelity, mobile-first. **Primary green** (`#006e2c`) is the action color (check-in, success, positive states). **Secondary indigo** (`#5343d6`) anchors branding, navigation, and headers. Generous rounded geometry (12px buttons/inputs, 16px cards, 24px hero widgets, pill-shaped badges). Both a light and a dark theme are required — see `DESIGN_SYSTEM.md §1–2` for the theming mechanism (CSS variables + Tailwind `class` dark-mode strategy, no separate build).

No build toolchain: Tailwind CSS via CDN, Alpine.js for lightweight interactivity, Lucide for icons, Notyf for toast/alert notifications. This keeps deployment as simple upload-and-go, matching the GitHub → cPanel auto-deploy pipeline.

## 8. Technical Architecture

| Layer | Choice |
|---|---|
| Backend | PHP 8.2+, plain scripts per page (no heavy framework) — matches shared cPanel hosting |
| Database | MySQL 8, InnoDB, utf8mb4, PDO prepared statements only |
| Frontend | Tailwind (CDN) + Alpine.js + Lucide + Notyf — zero Node build step |
| Auth | PHP sessions + bcrypt, 8-hour session lifetime |
| File storage | Local filesystem uploads directory, outside or protected within web root |
| Deployment | GitHub branch → merge to main → cPanel auto-deploy on push |

### 8.1 Directory Structure

```
kkyf-portal/
  public/            <- web root
    index.php, login.php, register.php, dashboard.php
    checkin.php, members.php, member-view.php
    attendance-history.php, followups.php
    tents.php, tent-admins.php, import.php   (super admin only)
    manifest.json, sw.js (phase 2)
  app/
    config/   (db.php, app.php)
    includes/ (auth.php, functions.php, header.php, footer.php)
  migrations/001_schema.sql
  scripts/create-super-admin.php
  .env.example
```

## 9. Build Phases

| Phase | Scope |
|---|---|
| **Phase 1 — Core** (build first) | Auth + approval flow, tent CRUD, member CRUD, Sunday check-in with inline first-timer add, attendance history, first-timer follow-up tracker, CSV import, role-scoped dashboards, click-to-call |
| **Phase 2 — Automation** | Offline mode (IndexedDB + Service Worker), absentee auto-alerts, attendance streaks & badges, birthday/anniversary CRON + SMS via AfricasTalking, WhatsApp click-to-send |
| **Phase 3 — Reporting & Admin** | Weekly/monthly/yearly reports with PDF/Excel export, tent leaderboard, full activity log, bulk SMS composer |

## 10. Non-Functional Requirements

- Performance: check-in response under 500ms; supports 200–500 members across all tents
- Security: PDO prepared statements everywhere, bcrypt password hashing, CSRF tokens on all forms, role + tent-scope enforcement on every data query
- Usability: mobile-first, all key actions reachable within 2 taps
- Data integrity: unique `(member_id, sunday_date)` constraint prevents duplicate check-ins
- Compatibility: Android Chrome, iOS Safari

## 11. Deployment & Cutover Plan

1. Create a new branch off main in the existing GitHub repo (fully clean — no old files carried over)
2. Build Phase 1 on that branch against a separate/clean database
3. Run `migrations/001_schema.sql` on the clean database — zero seed rows
4. Run `scripts/create-super-admin.php` once to create the single Super Admin login
5. Log in as Super Admin, create the 10 tents (plus any more needed)
6. Run the CSV/Excel import per tent to migrate legacy member data
7. Ask Tent Admins to self-register; approve each from the Tent Admins panel
8. Merge branch into main — cPanel auto-deploys — old app code and data are fully replaced

---
*— End of Document —*
