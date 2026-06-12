<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$role = $user['role'] ?? 'Guest';
$navItems = [
    ['href' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/tents', 'label' => 'Tents', 'icon' => 'tent', 'roles' => ['Super Admin']],
    ['href' => '/members', 'label' => 'Members', 'icon' => 'users', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/attendance', 'label' => 'Attendance', 'icon' => 'clipboard-check', 'roles' => ['Super Admin', 'Tent Admin']],
    ['href' => '/my-tent', 'label' => 'My Tent', 'icon' => 'map-pin', 'roles' => ['Tent Admin']],
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
                <span class="brand-icon"><i data-lucide="sprout"></i></span>
                <span class="brand-title">
                    KKYF v2
                    <small>Membership Portal</small>
                </span>
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
                <div class="topbar-title">
                    <strong><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </nav>
