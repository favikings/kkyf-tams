# KKYF Portal — Build Prompts
### Step-by-step prompts for building each component locally (Claude / OpenCode)

How to use this document: run each step in order — most components depend on the previous ones existing. Paste the **Shared Context** block once at the top of your session (or keep it in a `CLAUDE.md` / project instructions file so you don't repeat it), then paste each numbered prompt as its own message. Attach **all four** reference docs to your project so the assistant never has to guess: `KKYF_PRD_v3_Clean_Rebuild.md`, `DESIGN_SYSTEM.md`, `COMPONENTS.md`, `TECH_SPEC.md`, plus `kkyf_schema_v3.sql`.

---

## Shared Context Block

Paste this once at the start of a new session, then reuse for every step below:

```
SHARED CONTEXT:
Building "KKYF Membership Portal" v3 — pure PHP 8+ / MySQL 8, no frameworks, no Node build step.
Frontend: Tailwind CSS via CDN, Alpine.js for interactivity, Lucide icons, Notyf for toasts.
Visual design: follow DESIGN_SYSTEM.md and COMPONENTS.md exactly — semantic color tokens only
(bg-primary, text-on-surface, etc.), never raw hex or default Tailwind palette colors. Both light
and dark mode are required, implemented via the CSS-variable + Tailwind `class` strategy described
in DESIGN_SYSTEM.md §1 and TECH_SPEC.md §5. Typography: Geist for display/labels, Inter for body.
Directory layout, naming conventions, DB access pattern, auth contract, and API response shape are
all defined in TECH_SPEC.md — follow it exactly, don't improvise structure.
Auth model: sessions + bcrypt. Roles: super_admin (sees everything, more than one allowed) and
tent_admin (scoped to one tent_id, status must be 'approved' to log in). No seed data in
migrations — a separate script creates Super Admin accounts and can be run more than once.
Every DB query must use PDO prepared statements. Every tent-scoped page must enforce
tent_id === session tent_id for tent_admin role, per TECH_SPEC.md §3.
Reference the attached PRD v3.0, DESIGN_SYSTEM.md, COMPONENTS.md, TECH_SPEC.md, and schema SQL —
do not invent structure, naming, or visual choices that aren't in these documents.
```

---

## Build Order

1. Project scaffold & environment config
2. Database migration & Super Admin creation script
3. Auth: login, logout, session/role guards
4. Tent Admin registration + Super Admin approval flow
5. Shared design system: header, footer, sidebar/bottom nav
6. Tent management (Super Admin CRUD, unlimited tents)
7. Member management (list, add, edit, profile view, notes, click-to-call)
8. Sunday check-in flow + inline first-timer add
9. Attendance history (past Sundays)
10. First-timer follow-up tracker
11. CSV / Excel import wizard
12. Role-aware dashboards
13. PWA manifest + install support
14. QA pass & deployment checklist

---

## STEP 1 — Project Scaffold & Environment Config

> **Build note:** This creates the skeleton every later step writes into. Run once.

```
Scaffold the KKYF Portal PHP project per the shared context's directory layout.
Create: public/, app/config/, app/includes/, migrations/, scripts/ folders.

Create app/config/db.php: a db() function returning a singleton PDO connection,
reading DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS from a .env file (write a tiny
loadEnv() parser — no Composer dependency). Also expose an env(key, default) helper.

Create app/config/app.php: sets timezone from TIMEZONE env var (default Africa/Lagos),
defines APP_NAME/APP_URL constants, and starts a session with an 8-hour cookie lifetime,
httponly + samesite=Lax.

Create .env.example with DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_NAME, APP_URL, TIMEZONE.

Create a minimal README.md explaining folder structure and setup order.
```

---

## STEP 2 — Database Migration & Super Admin Creation Script

> **Build note:** Zero seed data policy — tents and members are created by the user, not migrations.

```
Using the attached schema reference, write migrations/001_schema.sql covering these
tables only for Phase 1: tents, users, members, attendance, first_timer_followups,
member_notes. InnoDB, utf8mb4, proper foreign keys, and a unique (member_id, sunday_date)
constraint on attendance. Do NOT insert any rows — structure only, no seed data.

Then write scripts/create-super-admin.php: a CLI script (run via `php scripts/create-super-admin.php`)
that interactively prompts for name, email, phone, and password (hide password input if possible),
hashes the password with PASSWORD_BCRYPT, and inserts one row into users with
role='super_admin', status='approved', tent_id=NULL. This script is re-runnable — it must NOT
refuse to run if a super_admin already exists; the app supports multiple Super Admins. If the
email already exists in users, show an error and exit instead of inserting a duplicate.
```

---

## STEP 3 — Auth: Login, Logout, Session & Role Guards

```
Create app/includes/auth.php with: currentUser(), isLoggedIn(), isSuperAdmin(),
requireLogin() (redirects to login.php), requireSuperAdmin() (403 if not super admin),
scopedTentId() (returns the tent_admin's tent_id, or null for super_admin),
login(userRow), logout(), csrfToken()/verifyCsrf() (session-based CSRF token helpers),
and flash($type,$message)/getFlashes() for one-time toast messages after redirects.

Create public/login.php: email+password form, verifies against users table with
password_verify(), blocks login if status != 'approved' or is_active = 0, shows a
generic error on failure (don't reveal which field was wrong), sets session on success.

Create public/logout.php that destroys the session and redirects to login.

Create public/index.php as a front controller that redirects to dashboard.php or login.php.

Style login.php using DESIGN_SYSTEM.md tokens — centered card on a secondary-colored
background, Geist display heading (text-headline-lg), primary-colored submit button
using the Primary Button recipe from COMPONENTS.md. Must render correctly in both
light and dark mode.
```

---

## STEP 4 — Tent Admin Registration + Approval Flow

```
Create public/register.php: public form for name, email, phone, tent (dropdown of
existing tents from the tents table), password + confirm. On submit, insert into users
with role='tent_admin', status='pending'. Show a confirmation screen, not immediate login.
If no tents exist yet, show a message instead of the form: 'Ask your Super Admin to add
tents first' — do not let registration proceed with zero tents.

Create public/tent-admins.php (Super Admin only, use requireSuperAdmin()): lists pending
registration requests with Approve / Reject buttons (POST with CSRF token), and a second
section listing all approved tent admins grouped by tent, with a toggle to deactivate.
Approving sets status='approved', approved_by, approved_at. A tent can have multiple
approved admins — don't enforce a one-admin-per-tent limit anywhere.

On the same page, add a "Promote to Super Admin" button next to each approved Tent Admin
(Super Admin only, CSRF-protected). It runs an UPDATE setting role='super_admin', tent_id=NULL
on that user, then shows a confirmation toast. Do not remove or hide the button after multiple
Super Admins exist — the app supports any number of them. Require a confirmation dialog
(browser confirm() or an Alpine modal) before submitting, since this is a high-privilege action.
```

---

## STEP 5 — Shared Design System — Header, Footer, Navigation, Dark Mode

> **Build note:** Build this before any authenticated page so every later step can just require it. This step turns DESIGN_SYSTEM.md and COMPONENTS.md into actual shared includes.

```
Create public/assets/css/theme.css containing exactly the :root and .dark CSS variable
blocks from DESIGN_SYSTEM.md §2 — copy them verbatim, don't modify any values.

Create app/includes/header.php and app/includes/footer.php as the shared page chrome.

Header load order: Google Fonts (Geist + Inter), then <link> to assets/css/theme.css,
then the Tailwind CDN <script>, then an inline tailwind.config script using exactly the
config from DESIGN_SYSTEM.md §3 (darkMode: 'class', the full semantic color map, the
custom border radii, and the typography utility classes from §4) — plus Alpine.js,
Lucide icons (unpkg), and Notyf (jsdelivr) configured per COMPONENTS.md's Toast section
so toast colors pull from the CSS variables instead of Notyf's defaults.

Before any other script, register the Alpine.js theme store exactly as specified in
TECH_SPEC.md §5 (localStorage-persisted, defaults to prefers-color-scheme, toggles the
`dark` class on <html>) — this must run in <head> to avoid a flash of the wrong theme.
Body uses bg-background text-on-surface.

When a user is logged in: render a fixed secondary-colored sidebar on desktop (per
COMPONENTS.md's Navigation section — logo, nav links to dashboard/checkin/members/
attendance-history/followups, and a Super-Admin-only section for tents/tent-admins/import)
and a bottom tab bar with icons on mobile (<768px) — hide the sidebar on mobile. Show the
logged-in user's name and role/tent in the sidebar footer, with a dark-mode toggle button
(COMPONENTS.md's Dark Mode Toggle Control) and a log out link.

footer.php closes the layout divs, renders lucide.createIcons(), and — if flash messages
are queued via getFlashes() — emits small inline scripts that fire Notyf toasts for each one.

Every element in these two files must use semantic Tailwind classes from the token map
(bg-surface, text-on-surface, bg-secondary, etc.) — no raw hex, no default Tailwind
palette colors (bg-gray-800, text-green-600, etc.).
```

---

## STEP 6 — Tent Management (Super Admin, Unlimited Tents)

```
Create public/tents.php, Super Admin only. List all tents as cards (name, color swatch,
member count, leader name/phone). 'Add Tent' opens an Alpine.js modal (no page reload)
with fields: name, color (color picker), leader name, leader phone, WhatsApp group link.

Support unlimited tents — there is no hardcoded limit of 10 anywhere in this file or
elsewhere in the app; the 10 starting tents are just the first ones the user types in.

Support editing a tent's fields inline, and deactivating (soft-delete via is_active=0,
never hard-delete a tent that has members).
```

---

## STEP 7 — Member Management

```
Create public/members.php: list members. Super Admin sees a tent filter dropdown
(?tent_id= querystring) and can view any tent; Tent Admin is hard-scoped to their own
tent_id from scopedTentId() regardless of querystring tampering. Show name, phone,
occupation, first-timer badge if is_first_timer, status. Search box filters client-side
or via a `?q=` param. Include an 'Add Member' button opening a full form (not the
quick inline one from check-in) with all member fields from the schema.

Create public/member-view.php?id=: profile page showing all fields, a click-to-call
phone icon (tel: link), attendance history for that member (list of Sundays attended),
first-timer follow-up status if applicable, and a private notes section (list + add-note
form posting to itself, using member_notes table, admin_id = current user).

Create public/member-edit.php?id=: edit form, same validation rules as add (school
required only if occupation=student). Enforce tent-scope: a tent_admin can only edit
members whose tent_id matches their own.
```

---

## STEP 8 — Sunday Check-in Flow + Inline First-Timer Add

> **Build note:** This is the most-used screen — optimize for speed and minimal taps.

```
Create public/checkin.php. Default to the current Sunday (helper: most recent Sunday,
or today if today is Sunday). Show a big search input at the top (Alpine.js-driven,
filters the member list live as you type, no page reload) listing this tent's members
(or, for Super Admin, a tent picker first). Each row has a large tap target — tapping
an unchecked name posts an AJAX/fetch request to mark them present instantly (optimistic
UI update + Notyf toast), without a full page reload. Already-checked-in members show
a checkmark and move to a 'Checked in' section or get visually greyed with a checkmark.

Below the search results, if no match is found for the typed name, show a card:
"Can't find {name}? Add as a new member" with a button that opens an Alpine.js modal
containing a short form: full name (prefilled from search), phone, DOB month/day,
occupation, school (shown only if occupation=student). On submit this must, in one
transaction: insert into members with is_first_timer=1 and first_seen_sunday=today,
insert an attendance row for today, and insert a first_timer_followups row with
status='pending'. Show a success toast and add them to the checked-in list without reload.

Enforce the unique (member_id, sunday_date) constraint gracefully — if someone double-taps,
show 'Already checked in' rather than an error page.
```

---

## STEP 9 — Attendance History

```
Create public/attendance-history.php: a date picker (defaults to list of past Sundays,
e.g. a dropdown/calendar restricted to Sundays only) plus a tent filter for Super Admin.
Selecting a Sunday shows who attended (name, check-in time, retroactive flag if
is_retroactive=1) and a count summary. Include a 'Mark attendance for this date'
action that reuses the same tap-to-check-in UI as checkin.php but against the selected
past date, setting is_retroactive=1 on insert.
```

---

## STEP 10 — First-Timer Follow-Up Tracker

```
Create public/followups.php. List first_timer_followups joined to members (name, tent,
first_visit date, status, assigned admin if any, notes). Super Admin sees all tents
with a tent filter; Tent Admin sees only their tent. Each row has a status dropdown
(pending/called/converted/not_returning) that updates via a small form or fetch call,
records updated_by and updated_at. Include an optional 'assign to' dropdown of admins
in that tent, and a notes textarea. Group or sort with 'pending' first so nothing
gets missed.
```

---

## STEP 11 — CSV / Excel Import Wizard

```
Create public/import.php, Super Admin only, multi-step using Alpine.js state (no full
page reloads between steps): Step 1 pick target tent from a dropdown. Step 2 upload a
.csv file (max 10MB, UTF-8). CSV only in v1 — no .xlsx support and zero Composer
dependencies; admins convert Excel files to CSV in their spreadsheet app first (Save
As → CSV UTF-8). Step 3
show the first 5 parsed rows and let the admin map each CSV column to a portal field
(Full Name, Phone required; Birth Month, Birth Day, Occupation, School Name optional)
via select dropdowns per column. Step 4 validate every row (name+phone required, phone
roughly numeric, occupation must be student/worker if present, school required if
occupation=student) and show a preview: N valid rows / N rows with errors, listing the
specific error per invalid row. Step 5 on confirm, insert all valid rows with
is_first_timer=0, join_date=today, tent_id=selected tent, created_by=current admin,
skip invalid rows, and show a final report: imported count, skipped count, error list.

Do not import any attendance history — only member records.
```

---

## STEP 12 — Role-Aware Dashboards

```
Create public/dashboard.php. For Super Admin: show total members across all tents,
today's total check-ins, first-timers today, count of pending tent-admin approvals
(linking to tent-admins.php), and a card grid of every tent showing member count +
today's check-in count, linking into members.php filtered to that tent. For Tent
Admin: same stat cards scoped to their tent only, plus an upcoming-birthdays list
(members with birth_month/birth_day closest to today, wrap-around at year end) and
quick-action buttons for Mark Attendance and Add Member.
```

---

## STEP 13 — PWA Manifest + Install Support

```
Create public/manifest.json (name, short_name, start_url=/dashboard.php,
display=standalone, background_color=#f8f9fa (light surface token), theme_color=#006e2c
(primary token), 192/512 icons). Note the manifest itself can't switch with the
in-app dark toggle — it only affects the OS splash screen, so light-mode brand
colors are the correct choice here.
Link it from header.php. Skip the service worker / offline caching for this phase —
that's Phase 2 — just make the app installable to a home screen for now.
```

---

## STEP 14 — QA Pass & Deployment Checklist

```
Review every public/*.php file that touches member or attendance data and confirm:
(1) every query uses PDO prepared statements, (2) every tent-scoped page calls
scopedTentId() and filters by it for tent_admin role even if a tent_id is passed in
the querystring, (3) every state-changing POST checks verifyCsrf(), (4) requireLogin()
or requireSuperAdmin() is the first line after includes on every protected page.

Then produce a short DEPLOY.md: steps to run 001_schema.sql on a clean database,
run create-super-admin.php, set .env on the server, point cPanel's document root to
/public, and the order of first actions to take when logging in for the first time
(create tents, then run the import wizard, then approve tent admins as they register).
```

---

*Tip: after each step, quickly smoke-test the new page(s) before moving to the next — later steps assume earlier tables and includes already exist and work.*

*— End of Document —*
