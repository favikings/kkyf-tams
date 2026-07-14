# KKYF Portal v2 Deployment Runbook

Use this runbook when you are ready to push v2 to a live or staging host.

## Goal

Deploy the new route-based v2 app safely, without accidentally booting the legacy root app.

## 1. Pick one deployment shape

### Option A: Best option

Point the domain or subdomain document root directly to:

```text
/path-to-project/public
```

Examples:

- `portal.kkyf.org` -> `/home/account/kkyf-tams-1/public`
- `members.yourdomain.com` -> `/home/account/kkyf-tams-1/public`

Why this is best:

- v2 boots directly from `public/index.php`
- cleaner URLs
- safer separation from the legacy root app

### Option B: Shared-folder fallback

Keep the project in its current folder and access v2 through:

```text
https://your-domain.com/kkyf-tams-1/public
```

In that case:

- `APP_URL=https://your-domain.com/kkyf-tams-1/public`
- `BASE_PATH=/kkyf-tams-1/public`

### Option C: Replace the current live root

This is only safe if you intentionally retire the old root portal.

If you do this, the server must still point to the v2 `public/` folder.

## 2. Create production environment file

Start from:

```text
.env.production.example
```

Copy it to `.env` on the server and replace placeholders.

### If document root points to `public/`

Use:

```env
APP_URL=https://your-domain.com
BASE_PATH=
```

### If app is served from `/kkyf-tams-1/public`

Use:

```env
APP_URL=https://your-domain.com/kkyf-tams-1/public
BASE_PATH=/kkyf-tams-1/public
```

Important:

- leave `APP_DEBUG=false`
- keep `SMS_ENABLED=false` until provider setup is complete

## 3. Upload application files

Upload the full project, especially:

- `app/`
- `config/`
- `database/`
- `doc/`
- `public/`
- `resources/`
- `routes/`
- `scripts/`
- `storage/`

Make sure these v2 public files exist after upload:

- `public/index.php`
- `public/.htaccess`
- `public/manifest.json`
- `public/service-worker.js`
- `public/offline.html`

## 4. Set writable folders

Make sure the host can write to:

- `storage/logs/`
- `storage/uploads/member-photos/`
- `storage/uploads/tent-banners/`

If your host requires manual folder creation, create missing folders before first use.

## 5. Create database and run migrations

Create the v2 database, then run every SQL file in:

```text
database/migrations/
```

Run them in filename order.

Do not run:

- `database/seeders/` on live

## 6. If bringing over legacy data

Only do this after:

- backup is complete
- migrations are complete
- `.env` is correct

Then:

1. dry-run on staging first
2. validate counts
3. run write migration
4. re-check tents, members, attendance, admins

Commands:

```powershell
php database\legacy_migration_check.php --dry-run
php database\migrate_legacy_to_v2.php --write
```

## 7. Post-import maintenance

After legacy import or any large attendance load, run:

```powershell
php scripts\refresh_streaks_and_badges.php
php scripts\calculate_absentees.php
```

This refreshes:

- member streaks
- earned badges
- absentee alert records

## 8. First smoke test after deploy

Test these in order:

1. `/login`
2. Super Admin login
3. Tent Admin login
4. `/dashboard`
5. `/members`
6. `/attendance`
7. `/attendance/history`
8. `/tents`
9. `/first-timers`
10. `/birthdays`
11. `/anniversaries`
12. `/reports`
13. `/sms`
14. `/activity-logs`

## 9. PWA verification

Open the app in Chrome or Edge and verify:

- manifest loads
- service worker registers
- install prompt works
- offline page works

If this fails, check:

- `APP_URL`
- `BASE_PATH`
- whether the server is actually serving from `public/`

## 10. Recommended launch sequence

1. Upload code to staging
2. Set staging `.env`
3. Point staging web root correctly
4. Run migrations
5. Import data if needed
6. Run streak/badge and absentee refresh scripts
7. Complete full smoke test
8. Repeat on production
9. Keep SMS in simulation mode initially
10. Monitor logs and activity logs for first live session

## 11. Known safe defaults for first launch

For first live launch:

- `APP_DEBUG=false`
- `SMS_ENABLED=false`
- `SMS_DRIVER=log_only`
- use the real imported tents only
- do not seed demo admins

## 12. Rollback

If the launch misbehaves:

1. switch traffic away from v2
2. restore previous `.env`
3. restore database backup if needed
4. check:
   - `storage/logs/app.log`
   - server PHP error logs
   - activity logs in the app

