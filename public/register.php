<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Shared Tailwind class fragment for the input recipe (COMPONENTS.md §Inputs).
$inputBase = 'mt-1 w-full min-h-[44px] rounded-md bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface border focus:outline-none focus:bg-surface-lowest placeholder:text-on-surface-variant/60';
// Same recipe with room on the right for the show/hide password icon button.
$inputPasswordBase = 'mt-1 w-full min-h-[44px] rounded-md bg-surface-container-low pl-3.5 pr-11 py-2.5 text-[16px] leading-6 text-on-surface border focus:outline-none focus:bg-surface-lowest placeholder:text-on-surface-variant/60';

$errors = [];
$old = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'tent_id' => '',
];

$tentStmt = db()->prepare('SELECT id, name FROM tents WHERE is_active = 1 ORDER BY name');
$tentStmt->execute();
$tents = $tentStmt->fetchAll();
$tentIds = array_map('intval', array_column($tents, 'id'));

$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['tent_id'] = trim((string) ($_POST['tent_id'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($old['name'] === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($old['name']) > 150) {
        $errors['name'] = 'Name must be 150 characters or fewer.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($old['phone'] === '') {
        $errors['phone'] = 'Phone is required.';
    }

    if ($old['tent_id'] === '') {
        $errors['tent_id'] = 'Select a tent.';
    } elseif (!in_array((int) $old['tent_id'], $tentIds, true)) {
        $errors['tent_id'] = 'Select a valid tent.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!isset($errors['email'])) {
        $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$old['email']]);
        if ($exists->fetch() !== false) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    if ($errors === []) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $insert = db()->prepare(
            "INSERT INTO users (name, email, phone, password_hash, role, tent_id, status, is_active)
             VALUES (?, ?, ?, ?, 'tent_admin', ?, 'pending', 1)"
        );
        $insert->execute([$old['name'], $old['email'], $old['phone'], $hash, (int) $old['tent_id']]);

        redirect('register.php?registered=1');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?> — Register</title>
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
<body class="min-h-screen bg-background text-on-surface font-body flex items-center justify-center px-4 py-8 md:px-8">
  <main class="w-full max-w-5xl overflow-hidden rounded-xl bg-surface-lowest shadow-elevated md:flex md:min-h-[620px]">
    <!-- Photo panel — hidden on mobile, shown alongside the form from md: up -->
    <div class="relative hidden md:block md:w-1/2">
      <img src="assets/images/auth-hero.webp" alt="" class="absolute inset-0 h-full w-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>
      <div class="relative flex h-full flex-col justify-end p-8 lg:p-10">
        <h2 class="font-display text-[32px] font-bold leading-10 tracking-[-0.01em] text-white lg:text-[40px] lg:leading-[44px]">
          Lead Your Tent<br>With Ease.
        </h2>
        <p class="mt-3 max-w-sm text-[15px] leading-6 text-white/80">
          Register as a Tent Admin to manage attendance, members, and follow-ups for your tent.
        </p>
      </div>
    </div>

    <!-- Form panel -->
    <div class="flex w-full flex-col justify-center overflow-y-auto px-6 py-10 md:w-1/2 md:px-10 lg:px-14">
      <div class="mx-auto w-full max-w-sm">
        <div class="mb-8 flex items-center gap-2.5">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-container">
            <i data-lucide="tent-tree" class="h-5 w-5 text-on-primary-container"></i>
          </span>
          <span class="font-display text-[16px] leading-6 font-semibold tracking-[-0.01em] text-on-surface"><?= e(APP_NAME) ?></span>
        </div>

    <?php if ($registered): ?>
      <div class="text-center">
        <i data-lucide="check-circle" class="w-12 h-12 text-primary mx-auto mb-4"></i>
        <h1 class="font-display font-semibold text-[28px] leading-9 md:text-[32px] md:leading-10 tracking-[-0.01em] text-on-surface">
          Registration submitted
        </h1>
        <p class="mt-3 text-[16px] leading-6 text-on-surface-variant">
          Your account is awaiting approval. A Super Admin will review your request, and you'll be able to sign in once it's approved.
        </p>
        <a href="login.php"
           class="mt-6 inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
          Back to Sign In
        </a>
      </div>

    <?php elseif ($tents === []): ?>
      <div class="text-center">
        <i data-lucide="info" class="w-12 h-12 text-secondary mx-auto mb-4"></i>
        <h1 class="font-display font-semibold text-[28px] leading-9 md:text-[32px] md:leading-10 tracking-[-0.01em] text-on-surface">
          No tents yet
        </h1>
        <p class="mt-3 text-[16px] leading-6 text-on-surface-variant">
          Ask your Super Admin to add tents first. You can register as a Tent Admin once a tent exists.
        </p>
        <a href="login.php" class="mt-6 inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
          Back to Sign In
        </a>
      </div>

    <?php else: ?>
      <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">
        Create Your Account
      </h1>
      <p class="mt-2 text-[14px] leading-5 text-on-surface-variant">Register as a Tent Admin to manage your tent.</p>

      <form method="post" action="register.php" class="mt-8 space-y-5">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

        <div>
          <label for="name" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Full Name</label>
          <input type="text" id="name" name="name" required autocomplete="name" value="<?= e($old['name']) ?>"
            class="<?= $inputBase ?> <?= isset($errors['name']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>">
          <?php if (isset($errors['name'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="email" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Email</label>
          <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($old['email']) ?>"
            class="<?= $inputBase ?> <?= isset($errors['email']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>"
            placeholder="you@example.com">
          <?php if (isset($errors['email'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['email']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="phone" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Phone</label>
          <input type="tel" id="phone" name="phone" required autocomplete="tel" value="<?= e($old['phone']) ?>"
            class="<?= $inputBase ?> <?= isset($errors['phone']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>"
            placeholder="+234 800 000 0000">
          <?php if (isset($errors['phone'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['phone']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="tent_id" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
          <select id="tent_id" name="tent_id" required
            class="<?= $inputBase ?> <?= isset($errors['tent_id']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>">
            <option value="" disabled <?= $old['tent_id'] === '' ? 'selected' : '' ?>>Select your tent…</option>
            <?php foreach ($tents as $tent): ?>
              <option value="<?= (int) $tent['id'] ?>" <?= (string) $tent['id'] === $old['tent_id'] ? 'selected' : '' ?>>
                <?= e($tent['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['tent_id'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['tent_id']) ?></p>
          <?php endif; ?>
        </div>

        <div x-data="{ show: false }">
          <label for="password" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Password</label>
          <div class="relative">
            <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
              class="<?= $inputPasswordBase ?> <?= isset($errors['password']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>">
            <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'"
                    class="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full hover:bg-surface-container">
              <i data-lucide="eye" class="h-5 w-5 text-on-surface-variant" x-show="!show"></i>
              <i data-lucide="eye-off" class="h-5 w-5 text-on-surface-variant" x-show="show"></i>
            </button>
          </div>
          <?php if (isset($errors['password'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['password']) ?></p>
          <?php endif; ?>
        </div>

        <div x-data="{ show: false }">
          <label for="password_confirm" class="font-display font-medium text-[12px] leading-4 tracking-[0.04em] uppercase text-on-surface-variant">Confirm Password</label>
          <div class="relative">
            <input :type="show ? 'text' : 'password'" id="password_confirm" name="password_confirm" required autocomplete="new-password"
              class="<?= $inputPasswordBase ?> <?= isset($errors['password_confirm']) ? 'border-error focus:border-error' : 'border-outline-variant focus:border-primary' ?>">
            <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'"
                    class="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full hover:bg-surface-container">
              <i data-lucide="eye" class="h-5 w-5 text-on-surface-variant" x-show="!show"></i>
              <i data-lucide="eye-off" class="h-5 w-5 text-on-surface-variant" x-show="show"></i>
            </button>
          </div>
          <?php if (isset($errors['password_confirm'])): ?>
            <p class="mt-1 text-[14px] leading-5 text-error"><?= e($errors['password_confirm']) ?></p>
          <?php endif; ?>
        </div>

        <button type="submit"
          class="w-full min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">
          Register
        </button>
      </form>

      <p class="mt-6 text-center text-[14px] leading-5 text-on-surface-variant">
        Already have an account? <a href="login.php" class="text-primary font-medium">Sign in</a>
      </p>
    <?php endif; ?>
      </div>
    </div>
  </main>

  <script defer>
    lucide.createIcons();
  </script>
</body>
</html>
