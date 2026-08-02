# KKYF Portal — Pages, Roles & Scoping Matrix
### Every page, who may access it, what scope applies, and the 403 behavior.

Companion to `TECH_SPEC.md §3` and `PRD §2`. Read both before creating any page file. **If a page isn't in this matrix, do not build it without recording it in `DECISIONS.md` first.**

Legend: 🟢 all authenticated · 🔒 super_admin only · 📣 public

---

## 1. Page matrix (`public/*.php`)

| Page | Role | Scope rule | First guard line |
|---|---|---|---|
| `index.php` | 📣 | Front controller — redirects to `dashboard.php` or `login.php` | none |
| `login.php` | 📣 | Guest only; redirects to dashboard if already logged in | none |
| `register.php` | 📣 | Tent-admin self-registration; **blocked if zero tents exist** | none |
| `logout.php` | 🟢 | Destroys session, redirects to login | none (POST/GET) |
| `dashboard.php` | 🟢 | Super: all tents. Tent: own tent only | `requireLogin();` |
| `checkin.php` | 🟢 | Super: tent picker first. Tent: own tent | `requireLogin();` |
| `members.php` | 🟢 | Super: `?tent_id=` filter (default all or first). Tent: own tent | `requireLogin();` |
| `member-view.php` | 🟢 | Row must belong to caller's scope | `requireLogin();` |
| `member-edit.php` | 🟢 | Row must belong to caller's scope | `requireLogin();` |
| `attendance-history.php` | 🟢 | Super: `?tent_id=` filter. Tent: own tent | `requireLogin();` |
| `followups.php` | 🟢 | Super: `?tent_id=` filter. Tent: own tent | `requireLogin();` |
| `tents.php` | 🔒 | — | `requireSuperAdmin();` |
| `tent-admins.php` | 🔒 | — | `requireSuperAdmin();` |
| `import.php` | 🔒 | — | `requireSuperAdmin();` |
| `assets/css/theme.css`, `manifest.json` | 📣 | Static assets — served without guards | — |

## 2. API endpoints (`public/api/*.php`)

| Endpoint | Role | Scope | Details |
|---|---|---|---|
| `checkin.php` | 🟢 | tent-scoped | `API_CONTRACT.md §2` |
| `member-quick-add.php` | 🟢 | tent-scoped | `API_CONTRACT.md §3` |
| `followup-status.php` | 🟢 | tent-scoped | `API_CONTRACT.md §4` |

All three call `requireLogin();` then `verifyCsrf();` — see `API_CONTRACT.md`.

---

## 3. The tent-scope rule (verbatim, applies to every tent-scoped page)

```php
$tentId = scopedTentId() ?? (int)($_GET['tent_id'] ?? 0);
```

- For a **tent_admin**, `scopedTentId()` returns their own `tent_id` — the querystring/body value is **never consulted**, so `?tent_id=999` tampering cannot escape scope.
- For a **super_admin**, `scopedTentId()` returns `null`, so the querystring is the optional tent filter.

Every read/`UPDATE`/`DELETE`(soft) that touches `members`, `attendance`, or `first_timer_followups` **must** include `WHERE tent_id = ?` bound to the effective `$tentId` — even when the row was identified by `id`.

---

## 4. 403 / access-denied behavior

| Where | Behavior |
|---|---|
| Page (full render) | Branded "Access denied" page using design tokens (`bg-surface-lowest`, `text-on-surface`, error icon). Rendered by the shared `accessDenied()` helper in `app/includes/auth.php` — never a bare `die()` string. |
| API endpoint | JSON shape: `{ "success": false, "error": "Access denied." }` with HTTP 403. |
| Wrong-tent row access (`member-view.php`, `member-edit.php`, retroactive mark) | Treated as not-found: redirect or 403 message — do not leak that a row exists in another tent. |

---

## 5. Public-vs-private guard rules

- **Public only:** `index.php`, `login.php`, `register.php`, `logout.php`, static assets. Everything else requires a guard.
- A protected page's first executable lines after `include`s are **exactly**:
  ```php
  requireLogin();                 // or requireSuperAdmin() for super-only pages
  ```
- No page performs its own ad-hoc `if ($_SESSION …)` check — that logic lives only in `app/includes/auth.php`.
- Tent-admin capabilities that are **forbidden** (PRD §2.2): managing tents, managing admin approvals, import wizard, viewing other tents. Guards + scoping above enforce this — never add a nav link a role can't reach.
