# KKYF Portal v2 Go-Live Checklist

This checklist is for moving the v2 portal live without overwriting legacy production behavior by accident.

## 1. Decide the live entrypoint

This repository currently contains two app surfaces:

- Legacy portal at the repository root: `index.php`, `admin/`, `tent/`, `api/`
- v2 portal at `public/` with app code in `app/`, `resources/`, `routes/`

Recommended live setup:

- Point the web root to `public/`
- Or expose v2 under a separate URL such as `/portal-v2/public`

Do **not** point the server to the repository root if the goal is to launch the new v2 app.

## 2. Prepare production `.env`

Use `.env.example` as the base, then set production-safe values:

```env
APP_NAME="KKYF Membership Portal v2"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-live-domain-or-path
BASE_PATH=/your-live-base-path
LOG_PATH=storage/logs/app.log
SESSION_NAME=KKYF_V2_SESSION

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_v2_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

SMS_ENABLED=false
SMS_DRIVER=log_only
SMS_SENDER_ID=
AFRICASTALKING_USERNAME=
AFRICASTALKING_API_KEY=
AFRICASTALKING_ENDPOINT=https://api.africastalking.com/version1/messaging
```

Notes:

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set `APP_URL` and `BASE_PATH` to the exact live URL/path
- Keep `SMS_ENABLED=false` until the real provider is configured and tested

## 3. Database safety

Before touching live data:

- Export a full SQL backup of the current live database
- Confirm whether v2 will use:
  - a new dedicated v2 database, or
  - the existing live database with additive lowercase v2 tables only

Recommended path:

- Use a dedicated v2 production database first
- Validate everything
- Only then decide whether you need the old tables co-located

## 4. Run required migrations

Run the SQL files in `database/migrations/` in filename order:

1. `2026_06_11_000001_create_users_table.sql`
2. `2026_06_11_000002_create_activity_logs_table.sql`
3. `2026_06_11_000003_create_tents_table.sql`
4. `2026_06_11_000004_create_members_table.sql`
5. `2026_06_11_000005_change_member_birthdate_to_month_day.sql`
6. `2026_06_12_000006_create_attendance_table.sql`
7. `2026_06_12_000007_create_migration_logs_table.sql`
8. `2026_06_16_000008_create_first_timers_table.sql`
9. `2026_06_16_000009_create_absentee_alerts_table.sql`
10. `2026_06_16_000010_create_streaks_table.sql`
11. `2026_06_16_000011_create_badges_table.sql`
12. `2026_06_16_000012_create_member_badges_table.sql`
13. `2026_06_16_000013_create_sms_logs_table.sql`

Do **not** run local seeders on live.

## 5. If migrating legacy data

Use a staging copy first.

Suggested order:

1. Back up legacy database
2. Dry-run migration on staging
3. Validate counts
4. Run actual migration on staging
5. Re-check tents, members, attendance, admins
6. Only then repeat on live if approved

Useful commands:

```powershell
php database\legacy_migration_check.php --dry-run
php database\migrate_legacy_to_v2.php --write
```

## 6. Post-migration maintenance scripts

Run these after attendance/member data is in place:

```powershell
php scripts\refresh_streaks_and_badges.php
php scripts\calculate_absentees.php
```

These help normalize:

- streak counts
- badges
- absentee alerts

## 7. File and folder permissions

Confirm the web server can write to:

- `storage/logs/`
- `storage/uploads/member-photos/`
- `storage/uploads/tent-banners/`

If these are not writable, uploads and logs may fail silently or partially.

## 8. PWA checks

v2 now expects these public files to exist and be reachable:

- `public/manifest.json`
- `public/service-worker.js`
- `public/offline.html`
- `public/assets/images/icon-192.png`
- `public/assets/images/icon-512.png`
- `public/assets/images/logo.jpg`

Live verification:

- open the app in Chrome or Edge
- confirm manifest loads
- confirm service worker registers
- confirm install prompt works on supported browsers
- confirm offline page loads when connection drops

## 9. Smoke-test routes after deployment

Test these manually:

- `/login`
- `/dashboard`
- `/members`
- `/members/show?id=...`
- `/attendance`
- `/attendance/history`
- `/tents`
- `/my-tent`
- `/birthdays`
- `/anniversaries`
- `/first-timers`
- `/absentees`
- `/reports`
- `/sms`
- `/activity-logs`

## 10. Functional smoke tests

Test at least one of each:

- Super Admin login
- Tent Admin login
- Add member
- Edit member
- Upload profile photo
- Create first-timer
- Convert first-timer
- Mark attendance
- View attendance history
- Open dashboard
- Open reports
- Send SMS in simulation mode
- Open birthdays and anniversaries
- Install PWA on a supported browser

## 11. Current launch cautions

These are not hard blockers, but they should be consciously accepted:

- SMS is still in simulation mode unless AfricasTalking is configured
- Root legacy app and v2 app coexist, so server entrypoint must be chosen carefully
- Legacy scripts/pages still exist in the repo and should not be confused with the v2 route-based app

## 12. Recommended launch order

1. Take fresh backup
2. Upload code
3. Apply production `.env`
4. Point server to `public/`
5. Run migrations
6. Run migration scripts only if approved
7. Run streak/badge and absentee refresh scripts
8. Smoke test with Super Admin
9. Smoke test with Tent Admin
10. Verify mobile layout and PWA
11. Announce limited rollout
12. Monitor logs and activity logs

## 13. Rollback plan

If anything fails:

1. Disable access to the v2 route or switch the web root back
2. Restore database backup if needed
3. Restore previous `.env`
4. Review `storage/logs/app.log`
5. Review activity logs and server error logs

