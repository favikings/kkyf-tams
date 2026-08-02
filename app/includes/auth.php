<?php

declare(strict_types=1);

/**
 * Session & role guard — the ONLY place session/role logic lives (TECH_SPEC §3).
 * No other file reads $_SESSION directly.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

/**
 * Returns the currently logged-in user as ['id','name','email','role','tent_id'],
 * or null when no one is logged in.
 */
function currentUser(): ?array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        return null;
    }

    $tentId = isset($user['tent_id']) && $user['tent_id'] !== null
        ? (int) $user['tent_id']
        : null;

    return [
        'id' => (int) ($user['id'] ?? 0),
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => (string) ($user['role'] ?? ''),
        'tent_id' => $tentId,
    ];
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function isSuperAdmin(): bool
{
    $user = currentUser();

    return $user !== null && $user['role'] === 'super_admin';
}

/**
 * Redirects to login.php when no one is logged in.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * 403 for anyone who is not a super_admin. Guests are sent to the login page
 * first; authenticated non-super-admins get the branded "Access denied" page.
 */
function requireSuperAdmin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    if (!isSuperAdmin()) {
        accessDenied();
    }
}

/**
 * A tent_admin's own tent_id, or null for a super_admin (whose scope is all
 * tents and who may pick one via querystring). Also null when not logged in.
 */
function scopedTentId(): ?int
{
    $user = currentUser();
    if ($user === null || $user['role'] === 'super_admin') {
        return null;
    }

    return $user['tent_id'];
}

/**
 * Stores the logged-in user in the session. Expects a users-table row
 * (from a PDO fetch) — only the fields exposed by currentUser() are kept.
 */
function login(array $userRow): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = [
        'id' => (int) $userRow['id'],
        'name' => (string) $userRow['name'],
        'email' => (string) $userRow['email'],
        'role' => (string) $userRow['role'],
        'tent_id' => $userRow['tent_id'] !== null ? (int) $userRow['tent_id'] : null,
    ];
}

/**
 * Destroys the session and its cookie.
 */
function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Returns the session's CSRF token, generating it on first use.
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

/**
 * Verifies $_POST['csrf'] against the session token. Dies with HTTP 403 and
 * a JSON body ({"success":false,"error":"csrf_mismatch","message":"..."}) on
 * any mismatch — not the non-standard 419, which some Apache/PHP-FPM configs
 * rewrite to a generic 500, silently breaking every CSRF-protected endpoint.
 * The "error":"csrf_mismatch" field lets frontend code tell this apart from
 * an ordinary tent-scope 403. Call this first in every state-changing POST
 * (page or API).
 */
function verifyCsrf(): void
{
    $sessionToken = is_string($_SESSION['_csrf'] ?? null) ? $_SESSION['_csrf'] : '';
    $sentToken = $_POST['csrf'] ?? null;

    if (!is_string($sentToken) || !hash_equals($sessionToken, $sentToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'csrf_mismatch',
            'message' => 'Your session expired or the form was out of date. Please refresh and try again.',
        ]);
        exit;
    }
}

/**
 * Queues a one-time toast message for the next page load (after a redirect).
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flashes'][] = ['type' => $type, 'message' => $message];
}

/**
 * Consumes and returns all queued flash messages:
 * [ ['type' => 'success'|'error', 'message' => '…'], … ]
 * Each request may call this once — it clears the queue.
 */
function getFlashes(): array
{
    $flashes = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);

    return is_array($flashes) ? $flashes : [];
}

/**
 * Renders the branded "Access denied" 403 page using design-system tokens
 * (PAGES_AND_ROLES.md §4). Never a bare die() string.
 */
function accessDenied(): void
{
    http_response_code(403);

    echo <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Access denied — KKYF Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            background: 'rgb(var(--color-background) / <alpha-value>)',
            'on-background': 'rgb(var(--color-on-background) / <alpha-value>)',
            surface: 'rgb(var(--color-surface) / <alpha-value>)',
            'surface-dim': 'rgb(var(--color-surface-dim) / <alpha-value>)',
            'surface-bright': 'rgb(var(--color-surface-bright) / <alpha-value>)',
            'surface-lowest': 'rgb(var(--color-surface-container-lowest) / <alpha-value>)',
            'surface-low': 'rgb(var(--color-surface-container-low) / <alpha-value>)',
            'surface-container': 'rgb(var(--color-surface-container) / <alpha-value>)',
            'surface-high': 'rgb(var(--color-surface-container-high) / <alpha-value>)',
            'surface-highest': 'rgb(var(--color-surface-container-highest) / <alpha-value>)',
            'surface-variant': 'rgb(var(--color-surface-variant) / <alpha-value>)',
            'on-surface': 'rgb(var(--color-on-surface) / <alpha-value>)',
            'on-surface-variant': 'rgb(var(--color-on-surface-variant) / <alpha-value>)',
            'inverse-surface': 'rgb(var(--color-inverse-surface) / <alpha-value>)',
            'inverse-on-surface': 'rgb(var(--color-inverse-on-surface) / <alpha-value>)',
            outline: 'rgb(var(--color-outline) / <alpha-value>)',
            'outline-variant': 'rgb(var(--color-outline-variant) / <alpha-value>)',
            primary: 'rgb(var(--color-primary) / <alpha-value>)',
            'on-primary': 'rgb(var(--color-on-primary) / <alpha-value>)',
            'primary-container': 'rgb(var(--color-primary-container) / <alpha-value>)',
            'on-primary-container': 'rgb(var(--color-on-primary-container) / <alpha-value>)',
            'inverse-primary': 'rgb(var(--color-inverse-primary) / <alpha-value>)',
            secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
            'on-secondary': 'rgb(var(--color-on-secondary) / <alpha-value>)',
            'secondary-container': 'rgb(var(--color-secondary-container) / <alpha-value>)',
            'on-secondary-container': 'rgb(var(--color-on-secondary-container) / <alpha-value>)',
            tertiary: 'rgb(var(--color-tertiary) / <alpha-value>)',
            'on-tertiary': 'rgb(var(--color-on-tertiary) / <alpha-value>)',
            'tertiary-container': 'rgb(var(--color-tertiary-container) / <alpha-value>)',
            'on-tertiary-container': 'rgb(var(--color-on-tertiary-container) / <alpha-value>)',
            error: 'rgb(var(--color-error) / <alpha-value>)',
            'on-error': 'rgb(var(--color-on-error) / <alpha-value>)',
            'error-container': 'rgb(var(--color-error-container) / <alpha-value>)',
            'on-error-container': 'rgb(var(--color-on-error-container) / <alpha-value>)',
            'primary-fixed': 'rgb(var(--color-primary-fixed) / <alpha-value>)',
            'primary-fixed-dim': 'rgb(var(--color-primary-fixed-dim) / <alpha-value>)',
            'on-primary-fixed': 'rgb(var(--color-on-primary-fixed) / <alpha-value>)',
            'on-primary-fixed-variant': 'rgb(var(--color-on-primary-fixed-variant) / <alpha-value>)',
          },
          fontFamily: {
            display: ['"Geist"', 'ui-sans-serif', 'system-ui'],
            body: ['"Inter"', 'ui-sans-serif', 'system-ui'],
          },
          borderRadius: {
            sm: '0.25rem',
            DEFAULT: '0.5rem',
            md: '0.75rem',
            lg: '1rem',
            xl: '1.5rem',
            full: '9999px',
          },
          boxShadow: {
            card: '0 4px 12px 0 rgb(0 0 0 / 0.04)',
            elevated: '0 8px 24px 0 rgb(0 0 0 / 0.10)',
          },
          spacing: {
            18: '4.5rem',
          },
        },
      },
    }
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
  <script defer src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-background text-on-surface font-body flex items-center justify-center px-5 py-10">
  <div class="w-full max-w-md bg-surface-lowest rounded-xl p-6 md:p-8 shadow-card text-center">
    <i data-lucide="shield-alert" class="w-10 h-10 text-error mx-auto mb-3"></i>
    <h1 class="font-display font-semibold text-[32px] leading-10 tracking-[-0.01em] text-on-surface">Access denied</h1>
    <p class="mt-2 text-[16px] leading-6 text-on-surface-variant">You don't have permission to view this page.</p>
    <a href="dashboard.php"
       class="mt-6 inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary text-[14px] leading-5 font-semibold font-display tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
      Back to dashboard
    </a>
  </div>
</body>
</html>
HTML;
    exit;
}
