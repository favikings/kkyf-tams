<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="app-shell state-shell">
    <section class="content-panel state-panel" aria-labelledby="error-title">
        <div class="eyebrow">403</div>
        <h1 id="error-title">Unauthorized</h1>
        <p class="lede">Your account does not have permission to access this v2 route.</p>
        <div class="state-actions">
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard"><i data-lucide="arrow-left"></i> Return to Dashboard</a>
        </div>
    </section>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
