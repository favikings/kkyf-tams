# Phase 6 Migration MVP

This phase prepares a safe migration path from the legacy production tables to the v2 tables.

## Safety Rules

- Do not run migration scripts directly on live production first.
- Take a full database backup before any migration attempt.
- Restore the backup into a local or staging database.
- Run the migration in dry-run mode first.
- Validate counts and spot-check records before running write mode.
- Do not delete, rename, truncate, or alter legacy tables.
- Migration must be repeatable in staging.

## Source Tables

- `Tents`
- `Admin_User`
- `Members`
- `Attendance_Log`
- `Sessions`

## Target Tables

- `tents`
- `users`
- `members`
- `attendance`
- `migration_logs`

## Backup Command Example

```powershell
mysqldump -u root kkyf_tams > backups/kkyf_tams_before_v2_migration.sql
```

If your MySQL user has a password:

```powershell
mysqldump -u root -p kkyf_tams > backups/kkyf_tams_before_v2_migration.sql
```

## Local/Staging Restore Example

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS kkyf_tams_v2_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root kkyf_tams_v2_staging < backups/kkyf_tams_before_v2_migration.sql
```

Then run all v2 migrations on the same staging database.

## Dry Run

```powershell
php scripts/migrate_legacy_to_v2.php --dry-run
```

## Write Mode

Only after dry-run and validation:

```powershell
php scripts/migrate_legacy_to_v2.php --write
```

## Validation Checklist

- Legacy tent count matches migrated v2 tent count.
- Legacy admin count maps into v2 users.
- Legacy member count maps into v2 members.
- Legacy attendance count maps into v2 attendance, allowing skipped duplicates.
- Tent Admin users have assigned v2 `tent_id` values where source assignments exist.
- `migration_logs` contains no unresolved `error` rows.

