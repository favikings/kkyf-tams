<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel" aria-labelledby="admin-title">
        <div class="eyebrow">Super Admin Only</div>
        <h1 id="admin-title">Role Protected Area</h1>
        <p class="lede">
            This route confirms that Super Admin access is enforced in the v2 middleware.
        </p>
        <p><a class="as-link" href="tents"><i data-lucide="tent"></i> Manage tents</a></p>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
