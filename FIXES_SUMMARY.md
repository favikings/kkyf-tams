# KKYF TAMS - Bug Fixes Summary

**Date:** March 29, 2026  
**Total Issues Fixed:** 25

---

## Phase 1: Security Fixes (9 issues)

| # | Issue | File | Fix Applied |
|---|-------|------|-------------|
| 01 | DB credentials hardcoded | `includes/db_connect.php` | Moved credentials to `.env` file |
| 02 | SMTP returns reset link in JSON | `api/auth_ops.php` | Removed reset_link from response, logs server-side |
| 03 | Unauthenticated dangerous endpoints | 8 test/debug files | Deleted all test/debug files |
| 04 | DB wipe by URL param | `api/reset_database.php` | Added Super Admin authentication |
| 05 | Registration code hardcoded | `api/register_admin.php` | Moved to `.env` file |
| 06 | No rate limiting on public attendance | `api/public_mark_attendance.php` | Added rate limiting (10 req/min/IP) |
| 07 | XSS vulnerability in onclick | `tent/first_timers.php` | Escaped HTML in JavaScript |
| 21 | User enumeration | `api/auth_ops.php` | Generic error messages |
| 25 | Raw PDO errors exposed | `includes/db_connect.php` | Sanitized error messages |

### Files Deleted (Phase 1):
- `api/test_db.php`
- `api/test_api.php`
- `api/test_db_structure.php`
- `api/test_final_verification.php`
- `api/test_member_update_scope.php`
- `api/debug_search.php`
- `api/reset_clean.php`
- `api/run_migration_browser.php`

### New Files Created (Phase 1):
- `.env` - Environment variables file
- `.gitignore` - Excludes `.env` from commits

---

## Phase 2: App Breaking Bugs (2 issues)

| # | Issue | File | Fix Applied |
|---|-------|------|-------------|
| 09 | No active session on fresh install | `database/schema.sql` | Fixed INSERT statement syntax |
| 14 | CACHE_NAME undefined (PWA broken) | `service-worker.js` | Added `const CACHE_NAME = 'kkyf-tams-v1'` |

---

## Phase 3: Logic Bugs (6 issues)

| # | Issue | File | Fix Applied |
|---|-------|------|-------------|
| **26** | **Auto-mark attendance on member creation** | `api/member_ops.php` | **REMOVED auto-attendance insertion** |
| **13** | **Public check-in doesn't set Is_First_Timer** | `api/public_mark_attendance.php` | **Added first-timer detection logic** |
| 08 | Super Admin blocked from birthdays | `api/get_birthdays.php` | Added Super Admin to allowed roles |
| 10 | Hardcoded "FEB" month | `tent/attendance_history.php` | Made month dynamic |
| 11 | DOB not saving from modal | `admin/roster.php` | Fixed FormData timing |
| 22 | Null tent_id silent failure | `api/get_attendance_history.php` | Added validation for Super Admin |

---

## Phase 4: Medium Priority (4 issues)

| # | Issue | File | Fix Applied |
|---|-------|------|-------------|
| 15 | Duplicate Export CSV button | `admin/roster.php` | Removed duplicate |
| 18 | Typo: "Print Repoert" | `api/generate_report.php` | Fixed to "Print Report" |
| 19 | Page title always "Dashboard" | `includes/header.php` + all pages | Added dynamic page titles |

---

## Key Behavioral Changes

### 1. Attendance Logic (Your Requirement)
**BEFORE:** When an admin created a new member, they were automatically marked as present for that day with `Is_First_Timer = 1`.

**AFTER:** New members are NOT automatically marked present. They must:
- Check in via `checkin.php` (public attendance page)
- Be marked manually by admin via `tent/attendance.php`

This ensures attendance is strictly for people who actually attended service.

### 2. First Timer Detection
When a member checks in via the public check-in page, the system now:
- Checks if they have any prior attendance records
- If NO prior attendance → marks as `Is_First_Timer = 1`
- If HAS prior attendance → marks as `Is_First_Timer = 0`

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `.env` | Created - holds all sensitive config |
| `.gitignore` | Created - excludes `.env` |
| `includes/db_connect.php` | Loads from .env, sanitized errors |
| `includes/header.php` | Dynamic page titles |
| `api/auth_ops.php` | Fixed security issues |
| `api/register_admin.php` | Loads code from .env |
| `api/reset_database.php` | Added auth check |
| `api/public_mark_attendance.php` | Rate limiting + Is_First_Timer |
| `api/member_ops.php` | Removed auto-attendance |
| `api/get_birthdays.php` | Fixed auth |
| `api/get_attendance_history.php` | Fixed null tent_id |
| `tent/first_timers.php` | Fixed XSS |
| `tent/attendance_history.php` | Fixed month display |
| `admin/roster.php` | Fixed DOB, removed duplicate |
| `admin/dashboard.php` | Page title |
| `admin/reports.php` | Page title |
| `admin/settings.php` | Page title |
| `tent/dashboard.php` | Page title |
| `tent/attendance.php` | Page title |
| `tent/members.php` | Page title |
| `tent/first_timers.php` | Page title |
| `tent/attendance_history.php` | Page title |
| `database/schema.sql` | Fixed session seed |
| `service-worker.js` | Added CACHE_NAME |
| `api/generate_report.php` | Fixed typo |

---

## Testing Checklist

- [ ] Login works
- [ ] Member creation does NOT auto-mark attendance
- [ ] Public check-in marks attendance with Is_First_Timer correctly
- [ ] Birthday page accessible by Super Admin
- [ ] Page titles are correct on all pages
- [ ] Rate limiting works on public attendance
- [ ] PWA installs correctly
- [ ] Export CSV works
- [ ] No duplicate buttons on roster page

---

## Deployment Notes

1. Copy `.env` file to production server (outside web root)
2. Update `.env` with production credentials
3. Run database schema if fresh install
4. Test all functionality before going live

---

*Generated: March 29, 2026*
