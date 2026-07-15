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
    <meta name="theme-color" content="#00BD06">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="KKYF TAMS">
    <link rel="manifest" href="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            corePlugins: {
                preflight: false
            },
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'Segoe UI', 'sans-serif'],
                        display: ['Cormorant Garamond', 'Georgia', 'serif']
                    },
                    colors: {
                        portal: {
                            ink: '#17211b',
                            muted: '#5f6f64',
                            line: '#d7dfd6',
                            mist: '#f5f3ee',
                            fog: '#ece8df',
                            accent: '#1b8a4b',
                            accentDeep: '#0f5a30',
                            accentSoft: '#e8f4ec',
                            night: '#102017'
                        }
                    },
                    boxShadow: {
                        panel: '0 24px 70px rgba(16, 32, 23, 0.10)',
                        soft: '0 10px 30px rgba(16, 32, 23, 0.08)'
                    },
                    borderRadius: {
                        shell: '28px'
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>

<body class="font-sans text-portal-ink" data-base-path="<?= htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8') ?>">
