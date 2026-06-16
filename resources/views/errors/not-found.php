<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="app-shell state-shell">
    <section class="content-panel state-panel" aria-labelledby="error-title">
        <div class="eyebrow">404</div>
        <h1 id="error-title">Not Found</h1>
        <p class="lede">The requested v2 record could not be found.</p>
        <div class="state-actions">
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members"><i data-lucide="users"></i> Return to Members</a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        </div>
    </section>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
