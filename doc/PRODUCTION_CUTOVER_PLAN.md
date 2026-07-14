# KKYF v2 Production Cutover Plan

Use this plan when promoting the v2 app to `main` where `main` auto-deploys.

## Goal

Replace the legacy app with the v2 app and carry forward existing production data safely.

## What we know

- Legacy production tables match the expected old schema:
  - `Admin_User`
  - `Attendance_Log`
  - `Audit_Log`
  - `Members`
  - `Password_Resets`
  - `Sessions`
  - `Tents`
- The migration code expects isolated source tables named:
  - `legacy_tents`
  - `legacy_admin_user`
  - `legacy_members`
  - `legacy_attendance_log`
- The migration code writes into:
  - `users`
  - `tents`
  - `members`
  - `attendance`
  - `migration_logs`

## Release principle for auto-deployed `main`

Because `main` deploys automatically, do not merge first and figure out the data later.

The safe order is:

1. Prepare production config and database plan.
2. Rehearse the migration on staging with a fresh copy of production data.
3. Schedule a short cutover window.
4. Freeze writes on the old app.
5. Take a final production backup.
6. Run the production migration.
7. Merge to `main` only when the database and server are ready for v2.

## Pre-cutover checklist

- Confirm the live server will serve the v2 app from `public/`.
- Confirm the production `.env` is ready for v2.
- Confirm a staging rehearsal has passed.
- Confirm you have a rollback path for both code and database.
- Confirm SMS remains disabled for first launch.
- Confirm the live team knows there will be a short read-only or offline window during cutover.

## Production `.env`

If the web root points directly to `public/`:

```env
APP_NAME="KKYF Membership Portal v2"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
BASE_PATH=
LOG_PATH=storage/logs/app.log
SESSION_NAME=KKYF_V2_SESSION

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_production_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

SMS_ENABLED=false
SMS_DRIVER=log_only
```

If the app is served from a path like `/kkyf-tams-1/public`:

```env
APP_URL=https://your-domain.com/kkyf-tams-1/public
BASE_PATH=/kkyf-tams-1/public
```

## Correct v2 migration order

Run these SQL files in this order:

1. `database/migrations/2026_06_11_000001_create_users_table.sql`
2. `database/migrations/2026_06_11_000003_create_tents_table.sql`
3. `database/migrations/2026_06_11_000004_create_members_table.sql`
4. `database/migrations/2026_06_12_000006_create_attendance_table.sql`
5. `database/migrations/2026_06_11_000002_create_activity_logs_table.sql`
6. `database/migrations/2026_06_12_000007_create_migration_logs_table.sql`
7. `database/migrations/2026_06_16_000008_create_first_timers_table.sql`
8. `database/migrations/2026_06_16_000009_create_absentee_alerts_table.sql`
9. `database/migrations/2026_06_16_000010_create_streaks_table.sql`
10. `database/migrations/2026_06_16_000011_create_badges_table.sql`
11. `database/migrations/2026_06_16_000012_create_member_badges_table.sql`
12. `database/migrations/2026_06_16_000013_create_sms_logs_table.sql`

`2026_06_11_000005_change_member_birthdate_to_month_day.sql` is not needed on a fresh v2 database because `members.date_of_birth` is already defined as `CHAR(5)` in the create-table migration.

## Staging rehearsal

### 1. Export production

```powershell
mysqldump -u YOUR_USER -p --routines --triggers --single-transaction YOUR_OLD_DB > backups\legacy_prod_pre_cutover.sql
```

### 2. Restore to staging

```powershell
mysql -u YOUR_USER -p -e "CREATE DATABASE IF NOT EXISTS kkyf_tams_v2_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u YOUR_USER -p kkyf_tams_v2_staging < backups\legacy_prod_pre_cutover.sql
```

### 3. Create isolated `legacy_*` source tables

```powershell
mysql -u YOUR_USER -p kkyf_tams_v2_staging < database\legacy_prepare_tables.sql
```

### 4. Run v2 schema migrations

Apply the SQL files listed in the corrected migration order above to `kkyf_tams_v2_staging`.

### 5. Configure `.env` for staging

Point `DB_NAME` to `kkyf_tams_v2_staging`.

### 6. Dry run

```powershell
php database\legacy_migration_check.php --dry-run
php database\migrate_legacy_to_v2.php --dry-run
```

### 7. Write migration

```powershell
php database\migrate_legacy_to_v2.php --write
```

### 8. Post-migration maintenance

```powershell
php scripts\refresh_streaks_and_badges.php
php scripts\calculate_absentees.php
```

## Validation queries

Run these after the staging write migration and again after production migration.

### Legacy source counts

```sql
SELECT COUNT(*) AS legacy_tents FROM legacy_tents;
SELECT COUNT(*) AS legacy_admins FROM legacy_admin_user;
SELECT COUNT(*) AS legacy_members FROM legacy_members;
SELECT COUNT(*) AS legacy_attendance FROM legacy_attendance_log;
```

### v2 target counts

```sql
SELECT COUNT(*) AS v2_tents FROM tents;
SELECT COUNT(*) AS v2_users FROM users;
SELECT COUNT(*) AS v2_members FROM members;
SELECT COUNT(*) AS v2_attendance FROM attendance;
```

### Migration log status

```sql
SELECT source_table, status, COUNT(*) AS row_count
FROM migration_logs
GROUP BY source_table, status
ORDER BY source_table, status;
```

### Error rows only

```sql
SELECT *
FROM migration_logs
WHERE status = 'error'
ORDER BY id DESC;
```

### Duplicate attendance check

```sql
SELECT member_id, attendance_date, COUNT(*) AS hit_count
FROM attendance
GROUP BY member_id, attendance_date
HAVING COUNT(*) > 1;
```

## Manual smoke test after staging migration

- Sign in as a migrated Super Admin.
- Sign in as a migrated Tent Admin.
- Open `/dashboard`.
- Open `/members`.
- Open `/attendance`.
- Open `/attendance/history`.
- Open `/tents`.
- Open `/birthdays`.
- Open `/anniversaries`.
- Open `/reports`.
- Confirm member totals are sensible.
- Confirm tent assignments are sensible.
- Confirm historical attendance appears.

## Production cutover sequence

### Phase A: Before merging to `main`

1. Put the legacy app into a short maintenance window or otherwise freeze writes.
2. Take a final production export.
3. Restore or work in the intended production v2 database.
4. Run `database/legacy_prepare_tables.sql`.
5. Run the corrected v2 schema migrations.
6. Set the production `.env` for v2.
7. Run:

```powershell
php database\legacy_migration_check.php --dry-run
php database\migrate_legacy_to_v2.php --write
php scripts\refresh_streaks_and_badges.php
php scripts\calculate_absentees.php
```

8. Run the validation queries.
9. Confirm the web server entrypoint is `public/`.

### Phase B: Merge and deploy

Only after Phase A succeeds:

1. Merge `mvp-development` into `main`.
2. Let the auto-deploy complete.
3. Smoke test production immediately:
   - `/login`
   - Super Admin login
   - Tent Admin login
   - `/dashboard`
   - `/members`
   - `/attendance`
   - `/reports`

## Git sequence

Use a normal merge, not a rushed direct edit on `main`.

```powershell
git checkout main
git pull origin main
git checkout mvp-development
git pull origin mvp-development
git checkout main
git merge --no-ff mvp-development
git push origin main
```

If there are uncommitted local changes, commit or stash them first.

## Rollback

If production fails after deploy:

1. Stop user access to v2 or point traffic back away from `public/`.
2. Restore the previous deployed code or revert the merge commit on `main`.
3. Restore the final production SQL backup if needed.
4. Restore the previous `.env`.
5. Review:
   - `storage/logs/app.log`
   - web server PHP error logs
   - `migration_logs`

## Important notes

- The migration script ignores `Audit_Log`, `Password_Resets`, and `Sessions`. That is expected.
- Legacy passwords should continue to work because the migration copies `Password_Hash` into `users.password_hash`.
- Attendance migration uses the first available v2 admin user as `checked_by`.
- Do not run seeders on production.
- Do not merge to `main` before the database and web root are ready, because `main` auto-deploys.
