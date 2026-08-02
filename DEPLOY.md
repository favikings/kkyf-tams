# KKYF Membership Portal — Deployment (cPanel)

Short runbook for getting the portal live on shared cPanel hosting (PHP 8+, MySQL 8). No build step — upload-and-go.

## 1. Prepare the database

1. In cPanel → **MySQL® Databases**, create an empty database (e.g. `kkyf_portal`) and a MySQL user, grant it **All Privileges** on that database.
2. In **phpMyAdmin**, select the new database and run `migrations/001_schema.sql` (Import → choose file → Go). This creates all tables, **zero seed rows**.
   - Also run `migrations/002_members_phone_nullable.sql` right after (makes `members.phone` optional; the app treats phone as optional).

## 2. Upload the files

There are two possible layouts, depending on whether the app gets its own document root or has to live inside an existing domain's docroot as a plain subfolder (e.g. `kkyfglobal.org/kkyftams`, which is how the live deploy is actually configured — see R-34 in `doc/DECISIONS.md`).

**A. Dedicated docroot (subdomain/addon domain you can repoint):**

```
kkyf-tams-v3/
  .env               <- copy from .env.example, fill in (never commit the real one)
  app/               <- config + includes (above web root, not publicly served)
  migrations/        <- 001_schema.sql, 002_members_phone_nullable.sql
  public/            <- <-- point the domain's document root here
  scripts/           <- create-super-admin.php
```

**B. Subfolder of an existing domain, no separate docroot available (the live setup):**

Apache resolves `kkyfglobal.org/kkyftams/...` straight off `kkyfglobal.org`'s own document root + that literal path — no subdomain's docroot setting changes that. The repo is synced **as-is** (`app/`, `public/`, `migrations/`, `scripts/` stay siblings, exactly matching the repo — every `require __DIR__ . '/../app/...'` and relative `fetch()` call in the codebase assumes this layout, so changing it breaks paths):

```
kkyftams/            <- FTP account's home dir; also the app's public URL path
  .htaccess           <- routes requests into public/, denies app/, migrations/, scripts/, .env
  .env                <- uploaded manually (gitignored); blocked by the rule above
  app/                <- blocked by app/.htaccess (belt-and-suspenders, Require all denied)
  migrations/         <- blocked by migrations/.htaccess (same)
  scripts/            <- blocked by scripts/.htaccess (same)
  public/             <- served transparently at kkyfglobal.org/kkyftams/ via the root .htaccess rewrite
```

The root `.htaccess` (repo root, deployed to `kkyftams/.htaccess`) is what makes this work — it rewrites any request that isn't already under `public/` into `public/`, without changing the browser's URL, and denies `app/`, `migrations/`, `scripts/`, `.env` outright. `.github/workflows/deploy.yml` just does a single `server-dir: /` sync of the whole repo — no special per-folder handling needed. Layout A is what you'd get by additionally repointing a domain's document root at `public/`, which isn't possible for a plain subfolder of an existing domain.

## 3. Set .env on the server

Copy `.env.example` to `.env` and set:

```
DB_HOST=localhost        # cPanel hostname for your DB user
DB_PORT=3306
DB_NAME=kkyf_portal
DB_USER=<mysql_user>
DB_PASS=<mysql_password>
APP_NAME=KKYF Membership Portal
APP_URL=https://yourdomain.com
TIMEZONE=Africa/Lagos
APP_DEBUG=false          # true only for local dev; false hides errors in production
```

Keep `.env` out of the web root (it already is — it lives one level above `public/`).

## 4. Point cPanel's document root at /public (layout A only)

In cPanel → **Domains** (or "Manage Domains"), set the domain's **Document Root** to the `public` folder, e.g.:

```
/home/<user>/kkyf-tams-v3/public
```

This keeps `app/`, `migrations/`, `.env`, and `scripts/` off the web. Visit the domain — you should be redirected to `login.php`.

**Layout B (subfolder deploy) has no document-root step** — there's nothing to repoint, since the folder is reached via the parent domain's own docroot + path. The root `.htaccess`'s rewrite rule does the equivalent job instead. Just visit `kkyfglobal.org/kkyftams/` directly — you should be redirected to `login.php`.

## 5. Create the first Super Admin

From SSH or the cPanel **Terminal**:

```bash
php scripts/create-super-admin.php
```

Enter name, email, phone, and an 8+ character password. The script is **re-runnable** and supports multiple Super Admins — run it again any time you need another.

## 6. First login — the right order

1. **Log in as Super Admin** → create your **Tents** (`Admin → Tents`). Nothing else works until tents exist.
2. **Import members** (`Admin → Import`) — pick a tent, upload your CSV/XLSX, map columns, validate, and import. This is how the initial roster gets in.
3. **Approve Tent Admins as they register** (`Admin → Tent Admins`) — Tent Admins sign up on the public `register.php` page, stay `pending`, and can only log in after you approve them.

Then go live: **Check In** (Dashboard → Start Sunday Check-in) on service day, review **Attendance History**, and work through **Follow-Ups** for first-timers.
