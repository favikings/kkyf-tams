<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
    <main class="shell">
        <section class="foundation-panel" aria-labelledby="page-title">
            <div class="masthead">
                <div class="eyebrow">Phase 0 Foundation</div>
                <h1 id="page-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="lede">
                    The v2 application foundation is isolated from the live production portal.
                    This entry point exists for local and staging development only.
                </p>
            </div>

            <div class="status-grid" aria-label="Phase 0 foundation status">
                <?php foreach ($statusItems as $item): ?>
                    <div class="status-item"><i data-lucide="check-circle"></i> <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>

            <div class="footer-note">
                No members, tents, attendance, reports, SMS, or offline sync features are included in Phase 0.
            </div>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>if (window.lucide) window.lucide.createIcons();</script>
</body>

</html>
