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
    includes/              <- auth, reset/mail helpers, shared functions + chrome
  migrations/              <- 001_schema.sql, then 002 and 003
  scripts/                 <- create-super-admin.php
  composer.json / lock     <- approved PHPMailer SMTP dependency
  .env / .env.example      <- environment config (never commit .env)
```

## Setup order

1. **Configure environment** — copy `.env.example` to `.env` and set DB credentials (a local `.env` for XAMPP already exists in this checkout).
2. **Install PHP dependencies** — run `composer install --no-dev --optimize-autoloader`. PHPMailer is the sole approved runtime package and powers authenticated SMTP for password recovery.
3. **Create the database** — create an empty MySQL database matching `DB_NAME`, then run migrations `001`, `002`, and `003` in order.
4. **Create a Super Admin** — `php scripts/create-super-admin.php` (Step 2). Re-runnable; supports multiple Super Admins.
5. **Serve the app** — use `http://localhost/kkyf-tams/` with the repository-root rewrite, or point a dedicated document root at `public/`.
6. **First login order** — log in as Super Admin → create the tents → run the CSV import wizard → approve Tent Admins as they register.

## Build status

See `doc/PROGRESS.md` for the 14-step build order and current status.
