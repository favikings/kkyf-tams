# KKYF Portal — API Contract
### Exact JSON request/response payloads for every `public/api/*.php` endpoint.

Companion to `TECH_SPEC.md §6`. Read both before touching any endpoint file. **If an endpoint's payload isn't listed here, it doesn't exist — do not invent one.**

---

## 1. Conventions (apply to every endpoint)

| Rule | Spec |
|---|---|
| URL | `public/api/*.php` — never a page file |
| Method | `POST` for everything (all current endpoints mutate state) |
| Content-Type | `application/json` on **both** request and response |
| Auth | first executable line after includes: `requireLogin();` |
| CSRF | every endpoint calls `verifyCsrf();` before any write |
| Scope | every tent-scoped write derives `$tentId = scopedTentId() ?? (int)($_POST['tent_id'] ?? 0);` — a `tent_admin` can never act outside their own `tent_id` |
| Response shape | `{ "success": true, "data": { … } }` or `{ "success": false, "error": "…" }` |
| Errors | a single `try/catch` returns the JSON error shape; never a bare `die()` or PHP warning |
| Keys | snake_case, matching DB columns exactly (`member_id`, `sunday_date`, …) |
| Dates | `sunday_date` is `Y-m-d` and must resolve to a Sunday; the default is the current Sunday |
| Validation | member fields always validated via `validateMember()` (TECH_SPEC §8) — never duplicated |

### Reserved error messages
| Case | Error string |
|---|---|
| Already checked in | `Already checked in for this Sunday.` |
| CSRF failure | (handled by `verifyCsrf()`, HTTP 403, `{"success":false,"error":"csrf_mismatch","message":"..."}`) |
| Not logged in | (handled by `requireLogin()`, redirect) |
| Wrong tent / not found | `Member not found in your tent.` / `Tent not found.` |

---

## 2. `POST api/checkin.php` — mark attendance

Used by the check-in screen (current Sunday) **and** retroactive marking from `attendance-history.php`.

### Request
```json
{
  "member_id": 12,
  "sunday_date": "2026-08-02",   // optional; default = current Sunday
  "csrf": "…"
}
```

> **`is_retroactive` is never sent by the frontend.** It is derived server-side: `sunday_date === currentSunday()` ⇒ `0`, otherwise ⇒ `1` (DECISIONS R-10). Same endpoint serves `checkin.php` (no `sunday_date`) and `attendance-history.php`'s retroactive marking (passes a past Sunday).

### Success response
```json
{
  "success": true,
  "data": {
    "member_id": 12,
    "member_full_name": "Jane Doe",
    "sunday_date": "2026-08-02",
    "checked_in_at": "2026-08-02T09:04:11+01:00",
    "is_retroactive": 1,
    "already_checked_in": false
  }
}
```

### DB side effects
Insert one `attendance` row (`member_id`, `tent_id` = scoped, `sunday_date`, `marked_by` = current user, `is_retroactive` derived as above).

### Error cases
| Case | HTTP | Error |
|---|---|---|
| Member not in caller's tent | 404 | `Member not found in your tent.` |
| `sunday_date` not a Sunday | 422 | `sunday_date must be a Sunday.` |
| Duplicate `(member_id, sunday_date)` — double-tap | 409 | `Already checked in for this Sunday.` |

> **Double-tap handling:** catch the `23000` PDO unique-constraint exception and return the 409 error above — never a 500.

---

## 3. `POST api/member-quick-add.php` — inline first-timer add from check-in

Must run **in one transaction**: insert member → insert attendance → insert follow-up (`TECH_SPEC.md §4`).

### Request
```json
{
  "full_name": "Jane Doe",           // required, prefilled from the check-in search box
  "phone": "+2348012345678",         // optional — not every member has one
  "birth_month": 3,                  // optional, 1–12 (small select)
  "birth_day": 14,                   // optional, 1–31 (small select)
  "occupation": "student",           // required; UI defaults to "worker"
  "school_name": "Example High",     // optional, shown only when occupation === "student"
  "tent_id": 3,                      // only consulted for super_admin; tent_admin scope wins
  "csrf": "…"
}
```

> **Deliberately minimal by design (DECISIONS R-11):** this is the fast, at-the-door flow — strictly shorter than the full member form (`member-edit.php`). **No photo upload here**; photos can be added later from the member's profile. Field rules match `validateMember()` (TECH_SPEC §8) exactly.

### Success response
```json
{
  "success": true,
  "data": {
    "member_id": 42,
    "full_name": "Jane Doe",
    "phone": "+2348012345678",
    "occupation": "student",
    "is_first_timer": true,
    "first_seen_sunday": "2026-08-02",
    "join_date": "2026-08-02",
    "sunday_date": "2026-08-02",
    "checked_in": true,
    "followup_id": 7,
    "followup_status": "pending"
  }
}
```

### DB side effects (one transaction)
1. `members` — `tent_id` (scoped), `is_first_timer = 1`, `first_seen_sunday = today`, `join_date = today`, `status = 'active'`, `created_by` = current user.
2. `attendance` — one row for the current Sunday.
3. `first_timer_followups` — `tent_id`, `first_visit = today`, `status = 'pending'`.

### Error cases
| Case | HTTP | Error |
|---|---|---|
| Validation failed | 422 | `{ "success": false, "error": "…", "errors": { "field": "msg" } }` |
| Unknown/inactive tent (super_admin only) | 404 | `Tent not found.` |

---

## 4. `POST api/followup-status.php` — update first-timer follow-up

### Request
```json
{
  "followup_id": 7,
  "status": "called",        // pending | called | converted | not_returning
  "assigned_to": 9,          // optional user id (admin in that tent)
  "notes": "Left a voicemail",  // optional
  "csrf": "…"
}
```

### Success response
```json
{
  "success": true,
  "data": {
    "followup_id": 7,
    "member_id": 42,
    "status": "called",
    "assigned_to": 9,
    "updated_by": 1,
    "updated_at": "2026-08-02T10:15:00+01:00"
  }
}
```

### DB side effects
`UPDATE first_timer_followups` set `status`, `assigned_to` (nullable), `notes` (nullable), `updated_by` = current user. `updated_at` is handled by `ON UPDATE CURRENT_TIMESTAMP`.

### Error cases
| Case | HTTP | Error |
|---|---|---|
| Follow-up not in caller's tent scope | 404 | `Follow-up not found in your tent.` |
| Invalid status value | 422 | `Invalid follow-up status.` |
| `assigned_to` not an approved admin of the record's tent | 422 | `Assigned admin is not valid for this tent.` |

---

## 5. Scope enforcement summary

| Role | `tent_id` used | Where from |
|---|---|---|
| `tent_admin` | their own `tent_id` | `scopedTentId()` — `tent_id` in body/querystring is ignored |
| `super_admin` | `tent_id` in body/querystring (optional) | `scopedTentId() ?? ($_POST['tent_id'] ?? 0)` |

Every member/attendance/follow-up row written must belong to the effective `tent_id`. Verify on read with `WHERE tent_id = ?` even after the insert/update — never trust the client to name the row's tent.

---

## 6. Not in this contract (deferred / non-API)

- **Manual "Add Member"** from `members.php` is **not** an API call — it posts to `member-edit.php` (add mode), see `PAGES_AND_ROLES.md`.
- **Member notes** add is a normal form POST to `member-view.php`, not an API endpoint.
- Offline sync / service worker endpoints are **Phase 2** — do not build.
