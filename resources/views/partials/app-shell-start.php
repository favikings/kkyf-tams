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
<main class="app-shell min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.16),transparent_24%),linear-gradient(180deg,#faf7f1_0%,#f2eee5_100%)]">
    <div class="fixed inset-0 z-40 hidden bg-[#102017]/45 backdrop-blur-sm xl:hidden" data-sidebar-close data-portal-backdrop></div>
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-50 flex h-screen w-[292px] -translate-x-full flex-col gap-6 overflow-y-auto border-r border-white/10 bg-[#102017] px-5 py-6 text-white shadow-2xl transition-transform duration-300 xl:translate-x-0" id="app-sidebar" data-portal-sidebar>
            <a class="flex items-center gap-3 text-white no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-lg font-extrabold text-white shadow-soft">K</span>
                <span class="grid leading-tight">
                    <span class="font-display text-[1.45rem] tracking-[-0.03em]">KKYF Admin</span>
                    <small class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-white/65">Management Portal</small>
                </span>
            </a>

            <nav class="grid gap-2" aria-label="Primary navigation" data-sidebar-nav>
                <?php foreach ($navItems as $item): ?>
                    <?php if (!in_array($role, $item['roles'], true)) continue; ?>
                    <?php $isActive = $activeHref === $item['href']; ?>
                    <a
                        class="inline-flex min-h-12 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold no-underline transition <?= $isActive ? 'bg-white/14 text-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.08)]' : 'text-white/72 hover:bg-white/10 hover:text-white' ?>"
                        href="<?= htmlspecialchars($basePath . $item['href'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <i data-lucide="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-auto border-t border-white/10 pt-5">
                <form method="POST" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="inline-flex min-h-11 items-center gap-2 rounded-full border border-white/12 bg-white/6 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/12" type="submit">
                        <i data-lucide="log-out"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 px-4 pb-8 pt-4 xl:ml-[292px] xl:px-8 xl:py-8">
            <nav class="mx-auto grid w-full max-w-[1280px] gap-4 rounded-lg border border-white/70 bg-white/82 px-5 py-5 shadow-soft backdrop-blur xl:grid-cols-[minmax(0,1fr)_minmax(280px,440px)_auto] xl:items-center" aria-label="Page context">
                <div class="flex items-center justify-between gap-3 xl:hidden">
                    <button class="inline-flex h-11 items-center gap-2 rounded-lg border border-[#d7dfd6] bg-white px-3 text-sm font-bold text-portal-ink shadow-sm" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">
                        <i data-lucide="menu"></i>
                        <span>Menu</span>
                    </button>
                    <div class="flex items-center justify-end gap-3">
                        <button class="inline-grid h-11 w-11 place-items-center rounded-full border border-[#d7dfd6] bg-[#f7f3ea] text-portal-ink transition hover:bg-white" type="button" aria-label="Notifications"><i data-lucide="bell"></i></button>
                        <button class="inline-grid h-11 w-11 place-items-center rounded-full border border-[#d7dfd6] bg-[#f7f3ea] text-portal-ink transition hover:bg-white" type="button" aria-label="Help"><i data-lucide="circle-help"></i></button>
                        <div class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-[#102017] to-emerald-800 text-sm font-bold text-white shadow-soft" title="<?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
                <div class="hidden min-w-0 xl:block">
                    <strong class="block truncate font-sans text-[1.35rem] font-extrabold text-portal-ink"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="text-[0.72rem] font-bold uppercase tracking-[0.22em] text-portal-muted"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?> workspace</span>
                </div>
                <form class="relative w-full" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                    <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-portal-muted" data-lucide="search"></i>
                    <input class="h-12 w-full rounded-full border border-[#d7dfd6] bg-[#fcfbf8] pl-12 pr-4 text-sm text-portal-ink outline-none ring-0 placeholder:text-portal-muted/75 focus:border-emerald-500" type="search" name="q" placeholder="Search members..." aria-label="Search members">
                </form>
                <div class="hidden items-center justify-end gap-3 xl:flex">
                    <button class="inline-grid h-11 w-11 place-items-center rounded-full border border-[#d7dfd6] bg-[#f7f3ea] text-portal-ink transition hover:bg-white" type="button" aria-label="Notifications"><i data-lucide="bell"></i></button>
                    <button class="inline-grid h-11 w-11 place-items-center rounded-full border border-[#d7dfd6] bg-[#f7f3ea] text-portal-ink transition hover:bg-white" type="button" aria-label="Help"><i data-lucide="circle-help"></i></button>
                    <div class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-[#102017] to-emerald-800 text-sm font-bold text-white shadow-soft" title="<?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </nav>
            <section class="fixed bottom-4 left-4 right-4 z-30 hidden gap-4 rounded-[28px] border border-emerald-200/60 bg-white/92 px-4 py-4 shadow-panel backdrop-blur md:left-auto md:w-[430px] md:grid-cols-[minmax(0,1fr)_auto]" data-pwa-install-banner hidden aria-label="Install app prompt">
                <div class="grid gap-1 min-w-0">
                    <span class="text-[0.68rem] font-extrabold uppercase tracking-[0.16em] text-emerald-800">Install App</span>
                    <strong class="text-sm font-bold text-portal-ink">Install KKYF Portal on this device.</strong>
                    <small class="text-sm leading-6 text-portal-muted" data-pwa-install-message>Quick access for members and attendance.</small>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-full border border-[#d7dfd6] bg-[#f8f5ee] px-4 text-sm font-semibold text-portal-ink transition hover:bg-white" data-pwa-install-dismiss>Not now</button>
                    <button type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-700 px-4 text-sm font-bold text-white shadow-soft transition hover:-translate-y-[1px]" data-pwa-install-action>
                        <i data-lucide="download"></i>
                        Install
                    </button>
                </div>
            </section>
