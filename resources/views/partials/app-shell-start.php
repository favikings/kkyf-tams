<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$role = $user['role'] ?? 'Guest';
$navItems = [
    ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/members', 'label' => 'Members', 'icon' => 'users', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/first-timers', 'label' => 'First Timers', 'icon' => 'user-plus', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/tents', 'label' => 'Tents', 'icon' => 'network', 'roles' => ['Super Admin']],
    ['href' => '/my-tent', 'label' => 'My Tent', 'icon' => 'map-pin', 'roles' => ['Tent Admin']],
    ['href' => '/attendance', 'label' => 'Attendance', 'icon' => 'clipboard-check', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/attendance/history', 'label' => 'Reports', 'icon' => 'chart-column', 'roles' => ['Super Admin', 'Tent Admin']],
];
?>
<main class="app-shell">
    <button class="mobile-menu-button" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">
        <i data-lucide="menu"></i>
        <span>Menu</span>
    </button>
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <div class="app-frame">
        <aside class="sidebar" id="app-sidebar">
            <a class="brand-mark" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                <span class="brand-icon">K</span>
                <span class="brand-title">
                    KKYF Admin
                    <small>Management Portal</small>
                </span>
            </a>

            <a class="sidebar-primary-action" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                <i data-lucide="plus"></i>
                New Member
            </a>

            <nav class="sidebar-nav" aria-label="Primary navigation">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                    <?php $isActive = str_contains($currentPath, $item['href']); ?>
                    <a class="nav-item <?= $isActive ? 'is-active' : '' ?>" href="<?= htmlspecialchars($basePath . $item['href'], ENT_QUOTES, 'UTF-8') ?>">
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

        <div class="main-area">
            <nav class="topbar" aria-label="Page context">
                <form class="topbar-search" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                    <i data-lucide="search"></i>
                    <input type="search" name="q" placeholder="Search members..." aria-label="Search members">
                </form>
                <div class="topbar-actions">
                    <button class="topbar-icon" type="button" aria-label="Notifications"><i data-lucide="bell"></i></button>
                    <button class="topbar-icon" type="button" aria-label="Help"><i data-lucide="circle-help"></i></button>
                    <div class="user-avatar" title="<?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </nav>
