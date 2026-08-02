<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$loggedIn = isLoggedIn();
$user = currentUser();

$pageTitle = (string) ($pageTitle ?? APP_NAME);

$activePage = basename($_SERVER['PHP_SELF'] ?? '');
$isMembersActive = in_array($activePage, ['members.php', 'member-view.php', 'member-edit.php'], true);

$navItems = [
    ['href' => 'dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'active' => $activePage === 'dashboard.php'],
    ['href' => 'checkin.php', 'icon' => 'clipboard-check', 'label' => 'Check In', 'active' => $activePage === 'checkin.php'],
    ['href' => 'members.php', 'icon' => 'users', 'label' => 'Members', 'active' => $isMembersActive],
    ['href' => 'attendance-history.php', 'icon' => 'calendar-days', 'label' => 'Attendance History', 'active' => $activePage === 'attendance-history.php'],
    ['href' => 'followups.php', 'icon' => 'phone', 'label' => 'Follow-Ups', 'active' => $activePage === 'followups.php'],
];

$adminNavItems = [
    ['href' => 'tents.php', 'icon' => 'tent', 'label' => 'Tents', 'active' => $activePage === 'tents.php'],
    ['href' => 'tent-admins.php', 'icon' => 'user-cog', 'label' => 'Tent Admins', 'active' => $activePage === 'tent-admins.php'],
    ['href' => 'import.php', 'icon' => 'upload', 'label' => 'Import', 'active' => $activePage === 'import.php'],
];

$mobileNavItems = [
    ['href' => 'dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'active' => $activePage === 'dashboard.php'],
    ['href' => 'checkin.php', 'icon' => 'clipboard-check', 'label' => 'Check In', 'active' => $activePage === 'checkin.php'],
    ['href' => 'members.php', 'icon' => 'users', 'label' => 'Members', 'active' => $isMembersActive],
    ['href' => 'attendance-history.php', 'icon' => 'calendar-days', 'label' => 'History', 'active' => $activePage === 'attendance-history.php'],
    ['href' => 'followups.php', 'icon' => 'phone', 'label' => 'Follow-Ups', 'active' => $activePage === 'followups.php'],
];

$userRoleLabel = 'Tent Admin';
if ($user !== null) {
    if ($user['role'] === 'super_admin') {
        $userRoleLabel = 'Super Admin';
    } elseif ($user['tent_id'] !== null) {
        $tentStmt = db()->prepare('SELECT name FROM tents WHERE id = ?');
        $tentStmt->execute([$user['tent_id']]);
        $tentRow = $tentStmt->fetch();
        if ($tentRow !== false) {
            $userRoleLabel .= ' · ' . $tentRow['name'];
        }
    }
}

$initials = '';
if ($user !== null) {
    $parts = preg_split('/\s+/', trim($user['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#006e2c">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400..700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
  <style>[x-cloak]{display:none}</style>
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

      Alpine.store('installPrompt', {
        show: false,
        deferredEvent: null,
        isIos: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
        isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        dismissed() {
          const t = localStorage.getItem('kkyf-install-dismissed');
          return t !== null && Number.isFinite(Number(t)) && Date.now() - Number(t) < 7 * 24 * 60 * 60 * 1000;
        },
        dismissNow() {
          localStorage.setItem('kkyf-install-dismissed', String(Date.now()));
          this.show = false;
          this.deferredEvent = null;
        },
      });

      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        const s = Alpine.store('installPrompt');
        if (s.isStandalone || s.dismissed()) {
          return;
        }
        s.deferredEvent = e;
        s.show = true;
      });

      window.addEventListener('appinstalled', () => {
        Alpine.store('installPrompt').dismissNow();
      });
    });
  </script>
  <?php if ($loggedIn): ?>
  <script>
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js');
  </script>
  <?php endif; ?>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script defer src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
  <script>
    window.notyf = new Notyf({
      duration: 3500,
      position: { x: 'right', y: 'top' },
      types: [
        { type: 'success', background: 'rgb(var(--color-primary))', icon: false },
        { type: 'error', background: 'rgb(var(--color-error))', icon: false },
      ]
    });
  </script>
</head>
<body class="min-h-screen bg-background text-on-surface font-body">
<?php if ($loggedIn): ?>
  <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-primary text-on-primary md:flex">
    <a href="dashboard.php" class="flex min-h-[64px] shrink-0 items-center gap-2.5 px-5">
      <img src="assets/icons/icon-192.png" alt="KKYF" class="w-9 h-9 rounded-lg">
      <span class="font-display text-[16px] leading-6 font-semibold tracking-[-0.01em] text-on-primary"><?= e(APP_NAME) ?></span>
    </a>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
      <?php foreach ($navItems as $item): ?>
        <a href="<?= e($item['href']) ?>"
           class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2.5 text-[14px] leading-5 <?= $item['active'] ? 'bg-on-primary/15 font-medium text-on-primary' : 'text-on-primary/85 hover:bg-on-primary/10' ?>">
          <i data-lucide="<?= e($item['icon']) ?>" class="h-5 w-5 shrink-0"></i>
          <span class="font-display"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>

      <?php if (isSuperAdmin()): ?>
        <div class="px-3 pb-2 pt-5 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary/60">Admin</div>
        <?php foreach ($adminNavItems as $item): ?>
          <a href="<?= e($item['href']) ?>"
             class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2.5 text-[14px] leading-5 <?= $item['active'] ? 'bg-on-primary/15 font-medium text-on-primary' : 'text-on-primary/85 hover:bg-on-primary/10' ?>">
            <i data-lucide="<?= e($item['icon']) ?>" class="h-5 w-5 shrink-0"></i>
            <span class="font-display"><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>

    <div class="shrink-0 border-t border-on-primary/10 p-3">
      <div class="flex items-center gap-3 px-3 py-2">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-on-primary/15 font-display text-[12px] leading-4 font-semibold text-on-primary"><?= e($initials) ?></span>
        <div class="min-w-0 flex-1">
          <div class="truncate font-display text-[14px] leading-5 font-semibold text-on-primary"><?= e($user['name']) ?></div>
          <div class="truncate text-[12px] leading-4 text-on-primary/60"><?= e($userRoleLabel) ?></div>
        </div>
      </div>
      <div class="mt-1 flex items-center justify-between gap-2 border-t border-on-primary/10 px-2 pt-3">
        <button type="button" x-data @click="$store.theme.toggle()" aria-label="Toggle dark mode"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full hover:bg-on-primary/10">
          <i data-lucide="sun" class="h-5 w-5" x-show="$store.theme.dark" x-cloak></i>
          <i data-lucide="moon" class="h-5 w-5" x-show="!$store.theme.dark" x-cloak></i>
        </button>
        <a href="logout.php"
           class="flex h-11 flex-1 items-center justify-center gap-2 rounded-full text-[14px] leading-5 font-medium text-on-primary/85 hover:bg-on-primary/10">
          <i data-lucide="log-out" class="h-5 w-5"></i>
          <span class="font-display">Log out</span>
        </a>
      </div>
    </div>
  </aside>

  <nav class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around bg-primary py-2 text-on-primary md:hidden" aria-label="Primary navigation">
    <?php foreach ($mobileNavItems as $item): ?>
      <a href="<?= e($item['href']) ?>"
         class="flex min-h-[52px] min-w-0 flex-col items-center justify-center gap-1 px-2 py-1 <?= $item['active'] ? 'text-on-primary' : 'text-on-primary/60' ?>">
        <i data-lucide="<?= e($item['icon']) ?>" class="h-5 w-5 shrink-0"></i>
        <span class="truncate font-display text-[12px] leading-4 font-medium tracking-[0.04em]"><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>

  <div class="<?= $loggedIn ? 'md:pl-64' : '' ?>" <?= $loggedIn ? 'x-data="{ drawerOpen: false }"' : '' ?>>
    <?php if ($loggedIn): ?>
    <div class="fixed inset-x-0 top-0 z-30 flex h-14 items-center justify-between bg-primary px-4 text-on-primary md:hidden">
      <a href="dashboard.php" class="flex min-h-[44px] items-center gap-2">
        <img src="assets/icons/icon-192.png" alt="KKYF" class="w-8 h-8 rounded-lg">
        <span class="font-display text-[20px] leading-7 font-semibold">KKYF</span>
      </a>
      <button type="button" @click="drawerOpen = true" aria-label="Open navigation menu"
              class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-on-secondary/10">
        <i data-lucide="menu" class="w-5 h-5"></i>
      </button>
    </div>
    <?php endif; ?>
    <main class="<?= $loggedIn ? 'min-h-screen px-5 pb-24 pt-20 md:px-10 md:pb-10 md:pt-10' : 'min-h-screen px-5 py-6 md:px-10 md:py-10' ?>">
