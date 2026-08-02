# KKYF Portal — Acceptance Criteria & QA Checklist
### Definition-of-done per build step, plus the global QA rules that apply to every step.

Companion to `KKYF_Build_Prompts.md` and `TECH_SPEC.md`. **Before marking a step complete in `PROGRESS.md`, every item in that step's checklist below must be verified.**

---

## 0. Global QA rules (apply to every step)

Every `public/*.php` file that touches member/attendance/follow-up data must pass ALL of:

- [ ] **PDO only** — every query is a prepared statement via `db()`; zero string-interpolated SQL.
- [ ] **Tent scoping** — every tent-scoped page calls `scopedTentId()` and filters by it for `tent_admin`, even if a `tent_id` is passed in the querystring.
- [ ] **CSRF** — every state-changing POST (page form or API) calls `verifyCsrf()`.
- [ ] **Guard first** — `requireLogin();` or `requireSuperAdmin();` is the first line after includes on every protected page.
- [ ] **Semantic tokens only** — no raw hex, no default Tailwind palette classes, no inline `<style>`. Light + dark both render correctly.
- [ ] **Component recipes** — only `COMPONENTS.md` markup; no invented button/input/badge/card variants.
- [ ] **No hardcoded tents** — tent lists/counts always queried from `tents`.
- [ ] **Soft delete only** — deactivations set `is_active`/`status`, never `DELETE`.
- [ ] **Transactions** — multi-step writes (quick-add, imports) wrapped per `TECH_SPEC.md §4`.
- [ ] **`php -l` passes** on every touched PHP file.
- [ ] **Smoke-tested** in both light and dark mode, mobile width (≤375px) and desktop (≥1024px).

---

## STEP 1 — Project scaffold & environment config

- [ ] Folders exist: `public/`, `public/api/`, `public/assets/css/`, `app/config/`, `app/includes/`, `migrations/`, `scripts/`.
- [ ] `app/config/db.php`: `db()` singleton PDO + `env()` + `loadEnv()` (no Composer).
- [ ] `app/config/app.php`: timezone (default `Africa/Lagos`), `APP_NAME`/`APP_URL` constants, session start (8h, httponly, samesite=Lax).
- [ ] `.env.example` lists `DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_NAME, APP_URL, TIMEZONE, APP_DEBUG`.
- [ ] `README.md` explains folder structure and setup order.

## STEP 2 — Migration & Super Admin script

- [ ] `migrations/001_schema.sql` creates only: `tents, users, members, attendance, first_timer_followups, member_notes` — InnoDB, utf8mb4, FKs, unique `(member_id, sunday_date)`.
- [ ] **Zero seed rows** — structure only.
- [ ] `scripts/create-super-admin.php` is CLI, prompts name/email/phone/password, bcrypt-hashes, inserts `role='super_admin', status='approved', tent_id=NULL`.
- [ ] **Re-runnable** — running again creates another Super Admin; duplicate email errors out without inserting.

## STEP 3 — Auth

- [ ] `app/includes/auth.php` implements every function from `TECH_SPEC.md §3` with the exact signatures.
- [ ] `login.php`: verifies with `password_verify()`, blocks `status != 'approved'` or `is_active = 0`, generic failure message.
- [ ] `logout.php` destroys session → redirect to login.
- [ ] `index.php` redirects to `dashboard.php` or `login.php` only.
- [ ] Login styled per DESIGN_SYSTEM/COMPONENTS (centered card, secondary background, Geist heading, primary button), correct in light + dark.

## STEP 4 — Tent Admin registration + approval

- [ ] `register.php`: form for name/email/phone/tent/password+confirm → inserts `role='tent_admin', status='pending'`; confirmation screen (no auto-login).
- [ ] Zero tents ⇒ shows "Ask your Super Admin to add tents first", form suppressed.
- [ ] `tent-admins.php` (super only): pending list with Approve/Reject (POST + CSRF); approved admins grouped by tent with deactivate toggle; no one-admin-per-tent limit.
- [ ] Promote-to-Super-Admin button per approved admin: confirmation dialog, CSRF-protected, `UPDATE users SET role='super_admin', tent_id=NULL`; button stays visible with multiple super admins.

## STEP 5 — Shared design system

- [ ] `public/assets/css/theme.css` = verbatim `:root`/`.dark` blocks from `DESIGN_SYSTEM.md §2`.
- [ ] `header.php` load order: fonts → theme.css → Tailwind CDN → tailwind.config (§3) → Alpine/Lucide/Notyf → theme store in `<head>` (TECH_SPEC §5). No flash of wrong theme.
- [ ] Notyf colors pulled from CSS variables (`COMPONENTS.md` Toast section).
- [ ] Desktop sidebar (secondary), mobile bottom tab bar (<768px), user name/role/tent in sidebar footer, dark-mode toggle, logout link.
- [ ] `footer.php` renders `lucide.createIcons()` + flash-driven Notyf toasts.
- [ ] Super-admin-only nav section (tents/tent-admins/import) hidden for tent_admin.

## STEP 6 — Tent management

- [ ] `tents.php` (super only): tent cards (name, color swatch, member count, leader name/phone), Add modal, inline edit, deactivate (soft).
- [ ] No limit of 10 anywhere.

## STEP 7 — Member management

- [ ] `members.php`: list + search; super has `?tent_id=` filter, tent_admin hard-scoped; first-timer badge; full-form add.
- [ ] `member-view.php`: all fields, click-to-call `tel:`, member attendance history, follow-up status, private notes (list + add).
- [ ] `member-edit.php`: same validation as add; tent_admin can only edit own-tent members.
- [ ] All member create/edit routes through `validateMember()`.

## STEP 8 — Sunday check-in + inline first-timer add

- [ ] `checkin.php` defaults to current Sunday; live search filters without reload.
- [ ] Tap-to-check-in via `api/checkin.php`, optimistic UI + Notyf, duplicate tap ⇒ "Already checked in" toast, no error page.
- [ ] "Can't find {name}? Add as a new member" card → modal → one-transaction quick-add (`members` + `attendance` + `followups`, `is_first_timer=1`).
- [ ] School field shown only when occupation=student.

## STEP 9 — Attendance history

- [x] `attendance-history.php`: Sunday-only date picker, super tent filter, roster with check-in time + retroactive flag, count summary.
- [x] Retroactive marking reuses `api/checkin.php` with `sunday_date`; `is_retroactive` derived server-side (R-10).

## STEP 10 — Follow-up tracker

- [x] `followups.php`: list joined to members (name, tent, first_visit, status, assigned, notes); super sees all with filter, tent_admin own tent.
- [x] Status updates via `api/followup-status.php`; records `updated_by`/`updated_at`; assign-to-admin dropdown (admins of that tent); notes textarea; pending sorted first.

## STEP 11 — CSV/Excel import wizard

- [x] `import.php` (super only), 5 Alpine-driven steps: tent → upload (≤10MB, **CSV UTF-8 + `.xlsx`**, see DECISIONS R-25 — xlsx added beyond the v1 "CSV-only" prompt per user request) → column map (first 5 rows preview) → validate (valid/error counts + per-row errors) → confirm.
- [x] Required cols: Full Name, Phone; optional: Birth Month/Day, Occupation, School.
- [x] Valid rows: `is_first_timer=0`, `join_date=today`, `tent_id`=selected, `created_by`=current admin; invalid rows skipped + reported.
- [x] **No attendance history imported.**

## STEP 12 — Role-aware dashboards

- [ ] Super: total members, today's check-ins, first-timers today, pending approvals count (links to `tent-admins.php`), per-tent card grid.
- [ ] Tent: same stats scoped to own tent, upcoming-birthdays list (year wrap-around), quick actions (Mark Attendance / Add Member).

## STEP 13 — PWA manifest

- [x] `manifest.json` per build prompt (start_url `/dashboard.php`, `display=standalone`, light brand colors `#f8f9fa`/`#006e2c`, 192/512 icons), linked from `header.php`.
- [x] No service worker / offline caching this phase.

## STEP 14 — QA pass & deployment checklist

- [ ] Global QA rules re-verified across the whole `public/` tree.
- [ ] `DEPLOY.md` produced: schema run → create-super-admin → set `.env` → cPanel document root to `/public` → first-login order (tents → import → approve admins).

## STEP 15 — Dashboard redesign (hero + activity widgets)

- [x] Hero card (both roles): `assets/images/dashboard-hero.jpg`, `rounded-xl overflow-hidden min-h-[220px]`, `bg-gradient-to-t from-inverse-surface/80 to-transparent` overlay; bottom-left content — label-sm "WELCOME BACK, {first name}", headline "Ready for Service?", primary "Start Sunday Check-in" → `checkin.php`. Identical for both roles.
- [x] 3 stat cards (grid-cols-3 → 1 on mobile): Today's Attendance "x / y" + progress bar (`bg-surface-container-high` track, `bg-primary` fill, `style="width: pct%"`); First-Timers + secondary-container "NEW" pill when > 0, no fabricated trend line; card 3 role-aware (Super "Active Tents"/"online", Tent "Pending Follow-Ups"/"need contact").
- [x] Recent Check-ins (md:col-span-3): 5 most recent for current Sunday, scoped (tent_admin) / all tents (super), `checked_in_at DESC`; avatar initials circle (alternating primary/tertiary-container), name + `ID: KKYF-{padded id}`, 12h time + "Checked In"/"Registered" badge (`is_first_timer=1 AND first_seen_sunday=today`).
- [x] Birthdays This Week (md:col-span-2): runs for both roles (super = all tents), next-7-days window; date badge (44px `bg-surface-container-low`, month label-sm + day title-md) + name; no "Turning {age}" (no birth year stored); full-width Secondary Button "Send Greetings" rendered disabled (opacity-50, pointer-events-none, no handler) — Phase-2 placeholder.
- [x] Light + dark both render clearly — hero overlay/text tokens flip with the theme store; verified token-only classes (no raw hex / palette colors).
- [x] Only `dashboard.php` changed; auth, routing, checkin.php, members.php untouched.
