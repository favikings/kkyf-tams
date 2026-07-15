<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
$role = $user['role'] ?? 'Guest';
$pageTitle = $title ?? 'KKYF Portal';
$navItems = [
    ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/members', 'label' => 'Members', 'icon' => 'users', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/first-timers', 'label' => 'First Timers', 'icon' => 'user-plus', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/tents', 'label' => 'Tents', 'icon' => 'network', 'roles' => ['Super Admin']],
    ['href' => '/my-tent', 'label' => 'My Tent', 'icon' => 'map-pin', 'roles' => ['Tent Admin']],
    ['href' => '/attendance', 'label' => 'Attendance', 'icon' => 'clipboard-check', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/absentees', 'label' => 'Absentees', 'icon' => 'triangle-alert', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/activity-logs', 'label' => 'Activity Logs', 'icon' => 'history', 'roles' => ['Super Admin']],
    ['href' => '/reports', 'label' => 'Reports', 'icon' => 'chart-column', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/birthdays', 'label' => 'Birthdays', 'icon' => 'cake', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/anniversaries', 'label' => 'Anniversaries', 'icon' => 'party-popper', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/sms', 'label' => 'SMS', 'icon' => 'messages-square', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/attendance/history', 'label' => 'Attendance History', 'icon' => 'chart-column', 'roles' => ['Super Admin', 'Tent Admin']],
];
$matchesPath = static function (string $currentPath, string $href): bool {
    if ($currentPath === $href) {
        return true;
    }

    return $href !== '/' && str_starts_with($currentPath, $href . '/');
};

$activeHref = null;
$activeLength = -1;

foreach ($navItems as $item) {
    if (!in_array($role, $item['roles'], true)) {
        continue;
    }

    if ($matchesPath($currentPath, $item['href']) && strlen($item['href']) > $activeLength) {
        $activeHref = $item['href'];
        $activeLength = strlen($item['href']);
    }
}
?>
<main class="app-shell bg-transparent">
    <button class="mobile-menu-button md:!hidden" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">
        <i data-lucide="menu"></i>
        <span>Menu</span>
    </button>
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <div class="app-frame xl:grid-cols-[292px_minmax(0,1fr)]">
        <aside class="sidebar border-r border-white/10 bg-portal-night text-white xl:px-6 xl:py-7" id="app-sidebar">
            <a class="brand-mark text-white no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                <span class="brand-icon bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-soft">K</span>
                <span class="brand-title font-display tracking-[-0.03em]">
                    KKYF Admin
                    <small>Management Portal</small>
                </span>
            </a>

            <a class="sidebar-primary-action rounded-full bg-white text-portal-ink shadow-soft no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                <i data-lucide="plus"></i>
                New Member
            </a>

            <nav class="sidebar-nav" aria-label="Primary navigation">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                    <?php $isActive = $activeHref === $item['href']; ?>
                    <a class="nav-item rounded-2xl <?= $isActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($basePath . $item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <i data-lucide="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <form method="POST" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="link-button" type="submit"><i data-lucide="log-out"></i> Logout</button>
                </form>
            </div>
        </aside>

        <div class="main-area bg-transparent xl:px-9 xl:py-8">
            <nav class="topbar rounded-[28px] border border-white/70 bg-white/80 shadow-soft backdrop-blur md:grid md:grid-cols-[minmax(0,1fr)_minmax(260px,430px)_auto]" aria-label="Page context">
                <div class="topbar-title min-w-0">
                    <strong class="font-display text-[1.45rem] font-semibold tracking-[-0.03em] text-portal-ink"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-portal-muted"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> workspace</span>
                </div>
                <form class="topbar-search" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                    <i data-lucide="search"></i>
                    <input class="border border-portal-line bg-white/90 text-portal-ink shadow-none" type="search" name="q" placeholder="Search members..." aria-label="Search members">
                </form>
                <div class="topbar-actions">
                    <button class="topbar-icon border border-portal-line bg-portal-mist text-portal-ink" type="button" aria-label="Notifications"><i data-lucide="bell"></i></button>
                    <button class="topbar-icon border border-portal-line bg-portal-mist text-portal-ink" type="button" aria-label="Help"><i data-lucide="circle-help"></i></button>
                    <div class="user-avatar bg-gradient-to-br from-portal-night to-emerald-800 text-white" title="<?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </nav>
            <section class="pwa-install-banner" data-pwa-install-banner hidden aria-label="Install app prompt">
                <div class="pwa-install-copy">
                    <span class="pwa-install-eyebrow">Install App</span>
                    <strong>Install KKYF Portal on this device.</strong>
                    <small data-pwa-install-message>Quick access for members and attendance.</small>
                </div>
                <div class="pwa-install-actions">
                    <button type="button" class="secondary-button" data-pwa-install-dismiss>Not now</button>
                    <button type="button" class="btn" data-pwa-install-action>
                        <i data-lucide="download"></i>
                        Install
                    </button>
                </div>
            </section>
