# KKYF Membership Portal — Agent Guide

This is the single entry point for any agent (OpenCode, Claude, Codex, or human) working in this repo. **Read this file first.** Then read the reference documents below before writing a single file. If a rule in this file and a reference document ever conflict, the reference document wins — raise the conflict instead of silently picking a side.

## Shared Context (verbatim — this is the session context every agent must hold)

```
SHARED CONTEXT:
Building "KKYF Membership Portal" v3 — pure PHP 8+ / MySQL 8, no frameworks, no Node build step.
Frontend: Tailwind CSS via CDN, Alpine.js for interactivity, Lucide icons, Notyf for toasts.
Visual design: follow DESIGN_SYSTEM.md and COMPONENTS.md exactly — semantic color tokens only
(bg-primary, text-on-surface, etc.), never raw hex or default Tailwind palette colors. Both light
and dark mode are required, implemented via the CSS-variable + Tailwind `class` strategy described
in DESIGN_SYSTEM.md §1 and TECH_SPEC.md §5. Typography: Geist for display/labels, Inter for body.
Directory layout, naming conventions, DB access pattern, auth contract, and API response shape are
all defined in TECH_SPEC.md — follow it exactly, don't improvise structure.
Auth model: sessions + bcrypt. Roles: super_admin (sees everything, more than one allowed) and
tent_admin (scoped to one tent_id, status must be 'approved' to log in). No seed data in
migrations — a separate script creates Super Admin accounts and can be run more than once.
Every DB query must use PDO prepared statements. Every tent-scoped page must enforce
tent_id === session tent_id for tent_admin role, per TECH_SPEC.md §3.
Reference the attached PRD v3.0, DESIGN_SYSTEM.md, COMPONENTS.md, TECH_SPEC.md, and schema SQL —
do not invent structure, naming, or visual choices that aren't in these documents.
```

Every new session reads this file (opencode/Codex auto-load `AGENTS.md` from the repo root, Claude reads `CLAUDE.md` — this file doubles as that contract). The block above is the same one from `doc/KKYF_Build_Prompts.md`; if you ever paste a session header manually, use this exact text.

## What this project is

KKYF (Kingsway Kingdom Youth Fellowship) Membership Portal v3 — a mobile-first PWA for Sunday attendance check-in, first-timer detection & follow-up, birthdays, and reporting. Pure **PHP 8+ / MySQL 8**, **no frameworks, no Node build step**. Frontend is Tailwind CSS (CDN), Alpine.js, Lucide icons, Notyf toasts. Deployment is upload-and-go to shared cPanel hosting.

## Reference documents — read before writing any code

All canonical, non-negotiable specs. Read in this order:

| # | Document | Covers |
|---|---|---|
| 1 | `doc/KKYF_PRD_v3_Clean_Rebuild.md` | *What* to build: goals, roles, flows, phases |
| 2 | `doc/TECH_SPEC.md` | *How* to build: directory layout, naming, auth contract, DB access, dark mode, API shape, validation rules, "What NOT to do" (§9) |
| 3 | `doc/DESIGN_SYSTEM.md` | Every color token (light + dark), typography, spacing, radii, elevation |
| 4 | `doc/COMPONENTS.md` | Every reusable UI component with exact markup |
| 5 | `doc/kkyf_schema_v3.sql` | Exact database schema (structure only, zero seed rows) |
| 6 | `doc/KKYF_Build_Prompts.md` | The 14-step build order with per-step prompts |
| 7 | `doc/API_CONTRACT.md` | Exact JSON request/response payloads for every API endpoint |
| 8 | `doc/PAGES_AND_ROLES.md` | Page × role × scope matrix and 403 behavior |
| 9 | `doc/ACCEPTANCE.md` | Per-step definition-of-done / QA checklist |
| 10 | `doc/PROGRESS.md` | **Living status** of the 14 build steps — always update it when you finish work |
| 11 | `doc/DECISIONS.md` | Resolved decisions + open ambiguities. Check here before inventing an answer |

## Build order

Follow the 14 steps in `doc/KKYF_Build_Prompts.md` in order — most steps depend on earlier ones. Before starting a step, check `doc/PROGRESS.md` to see what already exists and what is verified. Do **not** rebuild what is already marked done.

## Non-negotiable rules

These are the condensed version of TECH_SPEC §3–9. Breaking any of these is a build failure:

- **Stack:** pure PHP 8+, MySQL 8, PDO. Tailwind CSS via CDN, Alpine.js, Lucide, Notyf. No Composer dependency unless explicitly approved (see `doc/DECISIONS.md`). No jQuery, no other frameworks.
- **Design tokens only:** every color must be a semantic class from `DESIGN_SYSTEM.md §3` (`bg-primary`, `text-on-surface`, `bg-surface-lowest`, …). **Never** raw hex values, never default Tailwind palette classes (`bg-green-500`, `text-gray-700`). Both light and dark modes are mandatory, implemented with the CSS-variable + `dark` class strategy from `DESIGN_SYSTEM.md §1` / `TECH_SPEC.md §5`. Typography: Geist for display/labels, Inter for body. No inline `<style>` blocks.
- **Component markup:** use the exact recipes in `COMPONENTS.md`. Never invent a new variant of an existing component (buttons, inputs, cards, badges, nav, modals, toasts, empty states, dark-mode toggle).
- **Directory & naming:** exactly per `TECH_SPEC.md §1–2`. One plain PHP file per page under `public/`, `app/` for config/includes, `migrations/`, `scripts/`. kebab-case files, camelCase PHP, snake_case DB/API keys. `public/assets/css/theme.css` holds the verbatim variable block from `DESIGN_SYSTEM.md §2`.
- **DB access:** only `db()` from `app/config/db.php` opens a connection. **Every** query is a PDO prepared statement — never string-interpolated SQL. Multi-step writes use transactions (`TECH_SPEC.md §4`). Soft-delete only — nothing is ever hard-`DELETE`d.
- **Auth contract:** all session/role logic lives in `app/includes/auth.php`; no file reads `$_SESSION` directly. Every protected page's first executable lines are `requireLogin();` or `requireSuperAdmin();`. Signatures in `TECH_SPEC.md §3` are fixed — don't rename.
- **Tent scoping:** every tent-scoped query uses `scopedTentId()`. A `tent_admin`'s scope is their own `tent_id` **regardless of any `tent_id` in the querystring or POST body**. Super Admins may pick a tent via querystring only.
- **CSRF:** every state-changing POST (page form or API) verifies the CSRF token.
- **API shape:** every endpoint returns `{ "success": true, "data": {…} }` or `{ "success": false, "error": "…" }` with `Content-Type: application/json`. Exact payloads in `doc/API_CONTRACT.md`.
- **Validation:** member create/edit (manual add, quick-add, CSV import) all call the single `validateMember()` function per `TECH_SPEC.md §8`. Never duplicate validation logic.
- **Zero seed data:** migrations insert no rows. Tents/members/approvals are created by users. `scripts/create-super-admin.php` is re-runnable and may create **multiple** Super Admins — never make it refuse when one already exists.
- **No hardcoded tents:** never hardcode a tent list, count, or the number "10" in code, dropdowns, validation, or comments. Always query the `tents` table.

## When a rule is ambiguous

Do not improvise. Check `doc/DECISIONS.md` (resolved + open items). If the answer is genuinely open, ask the user with a clear question rather than guessing.

## Definition of done

Before marking a step complete in `doc/PROGRESS.md`, verify it against `doc/ACCEPTANCE.md` (both the global QA rules and the step's checklist). At minimum: `php -l` on every touched PHP file, PDO + scoping + CSRF + role-guard review, and a manual smoke test of the new page(s).

## Communication

- Never invent file paths, function names, DB columns, or API keys that aren't in the reference docs.
- If you must add something not covered by the docs, record it in `doc/DECISIONS.md` so it becomes a rule for everyone.
