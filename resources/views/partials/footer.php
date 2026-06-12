    <?php
    $assetBasePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
    $jsFile = dirname(__DIR__, 3) . '/public/assets/js/app.js';
    $jsVersion = is_file($jsFile) ? (string) filemtime($jsFile) : (string) time();
    ?>
    <script src="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>

</html>
