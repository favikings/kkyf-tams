<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/password-reset.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $startedAt = microtime(true);
    $email = (string) ($_POST['email'] ?? '');
    $reset = null;

    try {
        $reset = issuePasswordReset($email, passwordResetClientIp());
        if ($reset !== null) {
            try {
                sendPasswordResetEmail($reset);
            } catch (Throwable $mailException) {
                invalidatePasswordReset((string) $reset['token']);
                error_log('Password reset mail failed: ' . $mailException->getMessage());
            }
        }
    } catch (Throwable $exception) {
        error_log('Password reset request failed: ' . $exception->getMessage());
    }

    // Keep fast paths (unknown/ineligible/throttled addresses) close to the
    // normal local delivery path so response time is not an account oracle.
    $minimumDurationMicroseconds = 1500000;
    $elapsedMicroseconds = (int) ((microtime(true) - $startedAt) * 1000000);
    if ($elapsedMicroseconds < $minimumDurationMicroseconds) {
        usleep($minimumDurationMicroseconds - $elapsedMicroseconds);
    }

    redirect('forgot-password.php?sent=1');
}

$sent = ($_GET['sent'] ?? '') === '1';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?> — Forgot Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <script defer src="https://unpkg.com/lucide@latest"></script>
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
            'surface-container-low': 'rgb(var(--color-surface-container-low) / <alpha-value>)',
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
</head>
<body class="min-h-screen bg-background text-on-surface font-body flex items-center justify-center px-4 py-8 md:px-8">
  <main class="w-full max-w-5xl overflow-hidden rounded-xl bg-surface-lowest shadow-elevated md:flex md:min-h-[620px]">
    <div class="relative hidden md:block md:w-1/2">
      <img src="assets/images/auth-hero.webp" alt="" class="absolute inset-0 h-full w-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>
      <div class="relative flex h-full flex-col justify-end p-8 lg:p-10">
        <h2 class="font-display text-[32px] font-bold leading-10 tracking-[-0.01em] text-white lg:text-[40px] lg:leading-[44px]">
          Every Tent,<br>Every Sunday.
        </h2>
        <p class="mt-3 max-w-sm text-[15px] leading-6 text-white/80">
          Return securely to your tent records, Sunday check-ins, and first-timer follow-ups.
        </p>
      </div>
    </div>

    <div class="flex w-full flex-col justify-center px-6 py-10 md:w-1/2 md:px-10 lg:px-14">
      <div class="mx-auto w-full max-w-sm">
        <div class="mb-8 flex items-center gap-2.5">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-container">
            <i data-lucide="tent-tree" class="h-5 w-5 text-on-primary-container"></i>
          </span>
          <span class="font-display text-[16px] leading-6 font-semibold tracking-[-0.01em] text-on-surface"><?= e(APP_NAME) ?></span>
        </div>

        <?php if ($sent): ?>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-container text-on-primary-container">
            <i data-lucide="mail-check" class="h-6 w-6"></i>
          </div>
          <h1 class="mt-5 font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">
            Check your inbox
          </h1>
          <p class="mt-3 text-[16px] leading-6 text-on-surface-variant">
            If an eligible account exists for that email, a password reset link has been sent.
          </p>
          <a href="login.php"
             class="mt-8 inline-flex w-full min-h-[44px] items-center justify-center px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
            Back to Sign In
          </a>
        <?php else: ?>
          <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">
            Forgot your password?
          </h1>
          <p class="mt-2 text-[14px] leading-5 text-on-surface-variant">
            Enter your account email. If it is eligible, we'll send a secure reset link that expires in 30 minutes.
          </p>

          <form method="post" action="forgot-password.php" class="mt-8 space-y-5">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

            <div>
              <label for="email" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">
                Email
              </label>
              <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                autofocus
                class="mt-1 w-full min-h-[44px] rounded-md bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface border border-outline-variant focus:outline-none focus:border-primary focus:bg-surface-lowest placeholder:text-on-surface-variant/60"
                placeholder="you@example.com"
              >
            </div>

            <button type="submit"
                    class="w-full min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
              Send Reset Link
            </button>
          </form>

          <a href="login.php" class="mt-5 inline-flex min-h-[44px] w-full items-center justify-center text-[14px] leading-5 font-medium text-primary">
            Back to Sign In
          </a>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script defer>
    lucide.createIcons();
  </script>
</body>
</html>
