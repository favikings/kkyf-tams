<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<section class="content-panel state-panel" aria-labelledby="admin-title">
        <div class="eyebrow">Super Admin Only</div>
        <h1 id="admin-title">Protected Admin Area</h1>
        <p class="lede">
            Super Admin middleware is active for this route. Use this surface for protected governance tools as we add later phases.
        </p>
        <div class="state-actions">
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/tents"><i data-lucide="network"></i> Manage Tents</a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard"><i data-lucide="layout-dashboard"></i> Back to Dashboard</a>
        </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
