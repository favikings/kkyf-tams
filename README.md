# KKYF Membership Portal v2

This branch contains the v2 foundation for the KKYF Membership Portal.

The existing live portal remains in place at the repository root, `admin/`, `tent/`, and `api/`. Phase 0 adds an isolated v2 foundation under `app/`, `config/`, `routes/`, `public/`, and `storage/`.

## Phase 0 Scope

- Project folder structure
- Environment configuration
- Database connection service
- Error logging
- Basic routing
- Basic layout
- `.env.example`
- Phase 0 migrations for `users` and `activity_logs`

Phase 0 does not include members, tents, attendance, SMS, offline sync, reports, or migration of production data.

## Phase 1 Scope

- v2 login
- v2 logout
- Password hash verification
- Session handling
- Auth middleware
- Role middleware
- Super Admin protected route
- Tent Admin restriction behavior

Phase 1 uses the new lowercase `users` table. It does not change the legacy `Admin_User` login system.

## Phase 2 Scope

- Create v2 tents
- Edit tent profile fields
- Deactivate tents
- Assign active v2 Tent Admin users to tents
- Allow Tent Admin users to view only their assigned tent
- Upload tent banner images
- Pick tent colors with a browser color picker

Phase 2 uses the new lowercase `tents` table. It does not change the legacy `Tents` table.

## Phase 3 Scope

- Add v2 members
- Edit member records
- View member profiles
- Search members by name or phone
- Filter members by tent for Super Admin
- Restrict Tent Admins to their assigned tent members
- Deactivate members
- Upload profile photos
- Store birthdays as month/day only, without birth year

Phase 3 uses the new lowercase `members` table. It does not change the legacy `Members` table.

## Phase 4 Scope

- Sunday attendance check-in
- Search active members during check-in
- Prevent duplicate check-in for the same Sunday
- Attendance history
- Basic Sunday attendance report
- Tent Admin attendance restricted to assigned tent

Phase 4 uses the new lowercase `attendance` table. It does not change the legacy `Attendance_Log` table.

## Phase 5 Scope

- Super Admin dashboard metrics:
  - Total members
  - Active members
  - Total tents
  - Attendance today
  - This month attendance count
- Tent Admin dashboard metrics:
  - Tent members
  - Active tent members
  - Tent attendance today
  - Current Sunday attendance
- Recent members and recent attendance panels

Phase 5 uses existing v2 tables only and follows `doc/UI_direction.md`.

## Phase 6 Scope

- Migration backup instructions
- `migration_logs` table
- Dry-run capable legacy-to-v2 migration script
- Table-by-table migration structure
- Validation summary output

Phase 6 does not delete, rename, truncate, or alter legacy production tables.

## Local Setup

1. Copy `.env.example` to `.env`.
2. Set `DB_NAME` to a local or staging database, such as `kkyf_tams_v2_dev`.
3. Create the database manually in MySQL.
4. Run the SQL files in `database/migrations/` in filename order.
5. For local testing only, run the SQL files in `database/seeders/` in filename order.
6. Open the v2 app at:

```text
http://localhost/kkyf-tams-1/public/
```

The legacy live app still starts from the root `index.php`.

## Local Test Login

After running the local seeder:

```text
Email: admin@example.test
Password: ChangeMe123!
```

Change this local/staging password before any shared testing.

Local Tent Admin test login:

```text
Email: tentadmin@example.test
Password: ChangeMe123!
```

## Phase 1 Test Routes

```text
/login
/dashboard
/admin
/unauthorized
/tents
/my-tent
/members
/members/show?id=1
/attendance
/attendance/history
/dashboard
```

Expected behavior:

- `/dashboard` redirects anonymous users to `/login`.
- Valid v2 users can log in and log out.
- `/admin` is available only to `Super Admin`.
- A non-Super Admin user is redirected to `/unauthorized`.
- `/tents` is available only to `Super Admin`.
- `/my-tent` is available only to `Tent Admin` and shows only that user's assigned tent.
- `/members` is available to authenticated users; Super Admin sees all members, while Tent Admin sees only assigned-tent members.
- `/attendance` is available to authenticated users; duplicate Sunday check-ins are blocked.
- `/dashboard` shows role-scoped metrics from current v2 records.

## Phase 6 Migration

Read the full safety guide first:

```text
doc/migration_phase6.md
```

Run the migration only against a local or staging copy first.

Dry run:

```text
php scripts/migrate_legacy_to_v2.php --dry-run
```

Write mode after validation:

```text
php scripts/migrate_legacy_to_v2.php --write
```

## Safety Notes

- Do not run Phase 0 migrations against the live production database.
- Do not delete or rename existing legacy tables.
- Existing production tables use names such as `Members`, `Attendance_Log`, `Admin_User`, `Tents`, `Sessions`, `Audit_Log`, and `Password_Resets`.
- v2 tables are lowercase and additive.

## Verification

Run PHP syntax checks on new PHP files:

```text
php -l public/index.php
php -l app/Core/App.php
php -l app/Core/Config.php
php -l app/Core/Database.php
php -l app/Core/Env.php
php -l app/Core/Logger.php
php -l app/Core/Router.php
php -l app/Core/Csrf.php
php -l app/Core/Redirect.php
php -l app/Core/View.php
php -l app/Controllers/HomeController.php
php -l app/Controllers/AuthController.php
php -l app/Controllers/DashboardController.php
```
