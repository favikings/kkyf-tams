<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $assetBasePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
    $cssFile = dirname(__DIR__, 3) . '/public/assets/css/app.css';
    $cssVersion = is_file($cssFile) ? (string) filemtime($cssFile) : (string) time();
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'KKYF Membership Portal v2', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
