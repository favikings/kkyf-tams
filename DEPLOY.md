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

Apache resolves `kkyfglobal.org/kkyftams/...` straight off `kkyfglobal.org`'s own document root + that literal path — no subdomain's docroot setting changes that. So `public/`'s contents are synced to the `kkyftams/` folder root instead, with `app/`, `migrations/`, `scripts/` landing as sibling folders that are locked down with a checked-in `Require all denied` `.htaccess` each (they can't be moved outside the web root, since there's no "outside" reachable within the FTP account's chroot):

```
kkyftams/            <- FTP account's home dir; also the app's public URL path
  .env               <- uploaded manually (gitignored); blocked by public/.htaccess's rule, now at kkyftams/.htaccess
  app/                <- blocked by app/.htaccess (Require all denied)
  migrations/         <- blocked by migrations/.htaccess (Require all denied)
  scripts/            <- blocked by scripts/.htaccess (Require all denied)
  (public/'s contents, e.g. index.php, login.php, assets/, api/, .htaccess)
```

`.github/workflows/deploy.yml` handles this automatically — it runs one `FTP-Deploy-Action` step per folder with the right `server-dir` for each. Layout A only applies if you repoint the workflow back to a single `server-dir: /` sync of the whole repo.

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

**Layout B (subfolder deploy) has no document-root step** — there's nothing to repoint, since the folder is reached via the parent domain's own docroot + path. Security instead comes from the per-folder `.htaccess` files described above. Just visit `kkyfglobal.org/kkyftams/` directly — you should be redirected to `login.php`.

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
