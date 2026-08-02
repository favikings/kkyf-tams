# KKYF Membership Portal

Mobile-first membership portal for KKYF (Kingsway Kingdom Youth Fellowship) — Sunday attendance check-in, first-timer detection & follow-up, birthdays, and reporting. Pure **PHP 8+ / MySQL 8**, no frameworks, no Node build step. See `AGENTS.md` and the specs in `doc/` before changing code.

## Folder structure

```
kkyf-portal/
  public/                  <- web root (document root)
    api/                   <- JSON endpoints only (fetch() from Alpine)
    assets/css/theme.css   <- design-system variables (Step 5)
  app/
    config/
      db.php               <- loadEnv(), env(), db() singleton PDO
      app.php              <- timezone, constants, error reporting, session
    includes/              <- auth.php, functions.php, header/footer (later steps)
  migrations/              <- 001_schema.sql
  scripts/                 <- create-super-admin.php
  .env / .env.example      <- environment config (never commit .env)
```

## Setup order

1. **Configure environment** — copy `.env.example` to `.env` and set DB credentials (a local `.env` for XAMPP already exists in this checkout).
2. **Create the database** — create an empty MySQL database matching `DB_NAME`, then run `migrations/001_schema.sql` (Step 2).
3. **Create a Super Admin** — `php scripts/create-super-admin.php` (Step 2). Re-runnable; supports multiple Super Admins.
4. **Serve `public/`** — point your web server's document root at `public/` (e.g. `http://localhost/kkyf-tams-v3/public` on XAMPP).
5. **First login order** — log in as Super Admin → create the tents → run the CSV import wizard → approve Tent Admins as they register.

## Build status

See `doc/PROGRESS.md` for the 14-step build order and current status.
