# KKYF Portal — Technical Spec & Engineering Conventions
### The "how to build it" document. Read this alongside the PRD before writing any file.

---

## 1. File & Folder Responsibilities

```
kkyf-portal/
  public/                  <- web root, one plain PHP file per page
    index.php              <- redirects to dashboard.php or login.php only
    login.php / register.php / logout.php
    dashboard.php
    checkin.php
    members.php / member-view.php / member-edit.php
    attendance-history.php
    followups.php
    tents.php / tent-admins.php / import.php   (super admin only)
    api/                    <- JSON endpoints only, called via fetch() from Alpine
      checkin.php           <- POST mark attendance
      member-quick-add.php  <- POST inline first-timer add
      followup-status.php   <- POST update follow-up status
    assets/css/theme.css    <- the :root/.dark variable block from DESIGN_SYSTEM.md
  app/
    config/
      db.php                <- db(), env(), loadEnv()
      app.php                <- session start, timezone, constants
    includes/
      auth.php               <- everything in TECH_SPEC §3
      functions.php          <- date helpers, formatters, e(), redirect()
      header.php / footer.php
  migrations/001_schema.sql
  scripts/create-super-admin.php
  .env.example
```

**Rule:** a `public/*.php` file never contains raw SQL inline mixed with HTML — it calls a small query, assigns to a variable at the top of the file, then renders. Keep the "fetch data" block and the "render HTML" block visually separated by a comment divider in every page.

---

## 2. Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| PHP files | kebab-case | `attendance-history.php`, `member-view.php` |
| PHP functions | camelCase | `currentSunday()`, `scopedTentId()` |
| PHP variables | camelCase | `$tentId`, `$firstTimerCount` |
| Database tables | snake_case, plural | `first_timer_followups` |
| Database columns | snake_case | `is_first_timer`, `birth_month` |
| CSS/Tailwind custom classes | kebab-case | `.text-headline-lg-mobile` |
| JS/Alpine data properties | camelCase | `x-data="{ searchQuery: '' }"` |
| API JSON keys | snake_case (matches DB columns directly) | `{ "member_id": 12, "sunday_date": "2026-08-02" }` |

---

## 3. Auth & Session Contract

`app/includes/auth.php` is the **only** place session/role logic lives. Every other file calls its functions — never reads `$_SESSION` directly outside this file.

Required functions (exact signatures, don't rename):
```php
currentUser(): ?array          // ['id','name','email','role','tent_id']
isLoggedIn(): bool
isSuperAdmin(): bool
requireLogin(): void            // redirect to login.php if not logged in
requireSuperAdmin(): void       // 403 if not super_admin
scopedTentId(): ?int            // tent_admin's tent_id, or null for super_admin
login(array $userRow): void
logout(): void
csrfToken(): string
verifyCsrf(): void              // dies with 403 + {"success":false,"error":"csrf_mismatch",
                                 //   "message":"..."} if $_POST['csrf'] mismatches session.
                                 // Uses 403, not the non-standard 419 — some Apache/PHP-FPM
                                 // configs rewrite unrecognized status codes to a generic 500,
                                 // silently breaking every CSRF-protected endpoint. The
                                 // "error":"csrf_mismatch" field lets frontend code tell this
                                 // apart from an ordinary tent-scope 403 when it needs to.
flash(string $type, string $message): void
getFlashes(): array
```

**Multiple Super Admins:** the app supports more than one `super_admin` row. `scripts/create-super-admin.php` does **not** refuse to run if one already exists — running it again creates an additional Super Admin. Any logged-in Super Admin can also promote an approved Tent Admin to Super Admin from `tent-admins.php` (a button that runs `UPDATE users SET role='super_admin', tent_id=NULL WHERE id=?`, guarded by `requireSuperAdmin()` + CSRF). This is a deliberate, logged action — never automatic.

**Every protected `public/*.php` file's first executable lines (after includes) must be:**
```php
requireLogin();                 // or requireSuperAdmin() for super-admin-only pages
```
No page performs its own ad-hoc `if ($_SESSION...)` check.

**Every tent-scoped query must look like this** (never trust a `tent_id` from `$_GET`/`$_POST` for a tent_admin):
```php
$tentId = scopedTentId() ?? (int)($_GET['tent_id'] ?? 0); // super_admin may pick a tent via querystring
// then, if not super admin, scopedTentId() already forced the correct value —
// the querystring is only ever consulted for super_admin's optional filter.
```

---

## 4. Database Access Pattern

- **Only** `db()` from `app/config/db.php` opens a connection — never `new PDO()` anywhere else.
- **Always** prepared statements, **never** string-interpolated SQL:
```php
$stmt = db()->prepare("SELECT * FROM members WHERE tent_id = ? AND status = 'active'");
$stmt->execute([$tentId]);
$members = $stmt->fetchAll();
```
- Multi-step writes that must succeed or fail together (e.g. the first-timer quick-add: insert member → insert attendance → insert follow-up) are wrapped in a transaction:
```php
db()->beginTransaction();
try {
    // inserts...
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    throw $e;
}
```
- Soft-delete only, with two narrow, explicitly documented exceptions (`doc/DECISIONS.md` R-37): a member with zero attendance rows may be hard-`DELETE`d from `member-view.php`, and a Super Admin may hard-`DELETE` every member in one tent from `tents.php`'s Danger Zone (both require the confirmation flow specced in R-37 — never add a hard delete elsewhere without recording it there first). Every other delete uses `is_active` / `status = 'inactive'`.

---

## 5. Dark Mode Implementation

Single Alpine store, defined once in `header.php`, before any component uses it:

```html
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
      dark: localStorage.getItem('kkyf-theme')
        ? localStorage.getItem('kkyf-theme') === 'dark'
        : window.matchMedia('(prefers-color-scheme: dark)').matches,
      toggle() {
        this.dark = !this.dark;
        localStorage.setItem('kkyf-theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
      },
      init() {
        document.documentElement.classList.toggle('dark', this.dark);
      }
    });
  });
</script>
```
To avoid a flash of the wrong theme on load, this store-init script must run in `<head>`, before the page body paints — not deferred to the bottom of the page. Pair with the toggle button markup from `COMPONENTS.md` §"Dark Mode Toggle Control". Every color in the app comes from the CSS variables in `DESIGN_SYSTEM.md §2`, so no component needs its own dark-mode logic — flipping the `dark` class on `<html>` is sufficient everywhere.

---

## 6. AJAX / API Endpoint Contract

All interactive, no-reload actions (check-in tap, inline first-timer add, follow-up status change) go through `public/api/*.php`, not the page file itself.

- Every API file: `requireLogin()` first, `verifyCsrf()` for any state change, then a single `try/catch` that returns JSON.
- **Response shape, always:**
```json
{ "success": true, "data": { } }
```
or
```json
{ "success": false, "error": "Already checked in for this Sunday." }
```
- Set `header('Content-Type: application/json')` before echoing.
- Frontend calls these with plain `fetch()` inside Alpine methods — no jQuery, no axios.
```js
async function checkIn(memberId) {
  const res = await fetch('api/checkin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ member_id: memberId, csrf: csrfToken })
  });
  const json = await res.json();
  if (json.success) { /* optimistic UI update + notyf.success(...) */ }
  else { notyf.error(json.error); }
}
```

---

## 7. Error Handling

- User-facing errors: always a Notyf toast (via the flash system for full-page reloads, via the API JSON `error` field for AJAX) — never a raw PHP warning/notice reaching the browser.
- `app/config/app.php` sets `display_errors=0` and logs to a file in production; a `.env` flag `APP_DEBUG=true` can re-enable `display_errors` locally.
- 403s (wrong role/tent) render a simple branded "Access denied" page using the design system, not a bare `die()` string — except inside API endpoints, which return the JSON error shape instead.
- CSRF failures specifically (`verifyCsrf()`) always use status 403 with `{"success":false,"error":"csrf_mismatch"}` — for plain page-form submissions (not API calls), render this as a branded "Your session expired, please try again" page rather than a raw JSON dump, since the browser is expecting HTML there.

---

## 8. Validation Rules (apply identically everywhere a member is created/edited — manual add, quick first-timer add, CSV import)

| Field | Rule |
|---|---|
| `full_name` | required, max 200 chars |
| `phone` | optional (not every member has one; schema column is nullable — migration 002), digits/plus only after stripping spaces/dashes when provided, max 20 chars |
| `occupation` | required, must be `student` or `worker` |
| `school_name` | optional (even when `occupation === 'student'` — school may be unknown), max 200 chars |
| `birth_month` | optional, 1–12 |
| `birth_day` | optional, 1–31 |
| `tent_id` | required, must exist in `tents` and be `is_active = 1` |

Centralize this as one `validateMember(array $input): array` function (returns `['errors' => [...], 'clean' => [...]]`) in `app/includes/functions.php`, called from the manual add form, the quick-add API endpoint, and the CSV import row-validator — do not write this logic three times.

---

## 9. What NOT to do

- No Composer dependency unless explicitly justified in a build prompt (e.g. the xlsx parser in Step 11) — CSV is the default path.
- No raw hex colors or Tailwind default palette classes (`bg-green-500`, `text-gray-700`) anywhere — semantic tokens only.
- No inline `<style>` blocks per page — shared styles live in `assets/css/theme.css`.
- No jQuery, no separate JS framework — Alpine.js only.
- No full-page reloads for check-in taps, follow-up status changes, or modal-driven actions — those are API + fetch.
- No hardcoded tent list or tent count anywhere in code (including dropdowns, validation, or comments implying "10 tents") — always query the `tents` table.