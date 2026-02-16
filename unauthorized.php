<?php
// unauthorized.php
require_once 'includes/db_connect.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - KKYF TAMS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        inter: ['"Inter"', 'sans-serif'],
                    },
                    colors: {
                        primary: '#00BD06',
                        'primary-hover': '#009605',
                        secondary: '#1E293B',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden text-center p-8 animate-fade-in-up">

        <!-- Icon -->
        <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-2">Unauthorized Access</h1>

        <p class="text-slate-500 text-sm font-inter mb-8">
            <?php
            if (isset($_GET['msg']) && $_GET['msg'] === 'tent_mismatch') {
                echo "You do not have permission to view this Tent's data.";
            } else {
                echo "You do not have the required permissions to view this page.";
            }
            ?>
        </p>

        <a href="<?= BASE_PATH ?>/index.php"
            class="inline-block w-full bg-[#00BD06] hover:bg-[#009605] text-white font-semibold py-3.5 rounded-full shadow-lg shadow-green-500/30 transition-all duration-200">
            Return to Dashboard
        </a>

    </div>

    <p class="mt-8 text-xs text-slate-400 text-center">
        Error Code: 403 Forbidden
    </p>

</body>

</html>