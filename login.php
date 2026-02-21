<?php
// login.php
require 'includes/db_connect.php';

session_start();

$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'empty_fields')
        $error = "Please fill in all fields.";
    elseif ($_GET['error'] == 'invalid_credentials')
        $error = "Invalid username/email or password.";
    elseif ($_GET['error'] == 'system_error')
        $error = "System error. Please try again later.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic Validation
    if (empty($username) || empty($password)) {
        header("Location: " . BASE_PATH . "/login.php?error=empty_fields");
        exit;
    }

    try {
        // Prepare Statement (Prevents SQL Injection)
        $stmt = $pdo->prepare("
            SELECT a.ID, a.Username, a.Password_Hash, a.Role, a.Assigned_Tent_ID, t.Tent_Name 
            FROM Admin_User a
            LEFT JOIN Tents t ON a.Assigned_Tent_ID = t.Tent_ID
            WHERE a.Username = ? OR a.Email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password_Hash'])) {
            // Auth Success
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['assigned_tent_id'] = $user['Assigned_Tent_ID'];
            $_SESSION['tent_name'] = $user['Tent_Name'];

            // Redirect based on Role
            if ($user['Role'] === 'Super Admin') {
                header("Location: " . BASE_PATH . "/admin/dashboard.php");
            } elseif ($user['Role'] === 'Tent Admin') {
                header("Location: " . BASE_PATH . "/tent/dashboard.php");
            } else {
                header("Location: " . BASE_PATH . "/index.php");
            }
            exit;

        } else {
            header("Location: " . BASE_PATH . "/login.php?error=invalid_credentials");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        header("Location: " . BASE_PATH . "/login.php?error=system_error");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Tent Manager | Sign In</title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= BASE_PATH ?>/manifest.json">
    <meta name="theme-color" content="#00BD06">
    <link rel="apple-touch-icon" href="<?= BASE_PATH ?>/assets/img/icon-192.png">

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#00bd06",
                        "background-light": "#f5f8f5",
                        "background-dark": "#0f230f",
                        "deep-slate": "#1E293B",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"],
                        "sans": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .organic-shape-1 {
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
        }

        .organic-shape-2 {
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-sans text-deep-slate antialiased">
    <div class="flex min-h-screen w-full flex-col lg:flex-row">
        <!-- Left Side: High-Energy Brand Hero -->
        <div
            class="relative hidden w-full items-center justify-center overflow-hidden bg-deep-slate lg:flex lg:w-1/2 xl:w-7/12">
            <!-- Animated-like Abstract Background -->
            <div class="absolute inset-0 z-0">
                <div class="organic-shape-1 absolute -top-20 -left-20 h-96 w-96 bg-primary/20 blur-3xl"></div>
                <div
                    class="organic-shape-2 absolute bottom-[-10%] right-[-10%] h-[500px] w-[500px] bg-primary/10 blur-3xl">
                </div>
                <div class="absolute top-1/2 left-1/2 h-full w-full -translate-x-1/2 -translate-y-1/2 opacity-20">
                    <svg class="h-full w-full" viewbox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M44.7,-76.4C58.1,-69.2,69.2,-58.1,76.4,-44.7C83.7,-31.3,87.1,-15.7,85.8,-0.8C84.4,14.1,78.3,28.2,69.2,40.1C60.1,51.9,48,61.6,34.5,68.9C21.1,76.2,6.3,81.1,-8.5,80.6C-23.4,80.1,-38.3,74.2,-50.7,65.3C-63.1,56.5,-73,44.7,-79.1,31.2C-85.2,17.7,-87.5,2.4,-84.8,-11.9C-82.1,-26.2,-74.4,-39.5,-63.9,-49.7C-53.5,-59.8,-40.2,-66.8,-27.4,-74.5C-14.7,-82.2,2.3,-90.6,18.7,-88.2C35.1,-85.8,50.8,-72.6,44.7,-76.4Z"
                            fill="#00bd06" transform="translate(100 100)"></path>
                    </svg>
                </div>
            </div>
            <!-- Content -->
            <div class="relative z-10 px-12 text-center">
                <div
                    class="mb-8 inline-flex items-center justify-center rounded-2xl bg-primary p-4 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-5xl text-white">camping</span>
                </div>
                <h1 class="font-display text-6xl font-black tracking-tight text-white xl:text-7xl">
                    Tent <span class="text-primary">Manager</span>
                </h1>
                <p class="mt-6 font-display text-xl font-medium text-slate-300 xl:text-2xl">
                    Organized. Communal. Tech-Forward.
                </p>
                <div class="mt-12 flex justify-center gap-4">
                    <div class="h-1.5 w-12 rounded-full bg-primary"></div>
                    <div class="h-1.5 w-4 rounded-full bg-slate-600"></div>
                    <div class="h-1.5 w-4 rounded-full bg-slate-600"></div>
                </div>
            </div>
            <!-- Decorative Floating Elements -->
            <div
                class="absolute bottom-12 left-12 flex items-center gap-3 rounded-full bg-white/5 px-4 py-2 backdrop-blur-md">
                <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                <span class="text-sm font-medium text-slate-300">System Online</span>
            </div>
        </div>
        <!-- Right Side: Login Workspace -->
        <div
            class="flex flex-1 items-center justify-center bg-white px-6 py-12 dark:bg-background-dark sm:px-12 lg:w-1/2 xl:w-5/12">
            <div class="w-full max-w-md">
                <!-- Mobile Branding -->
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
                        <span class="material-symbols-outlined text-2xl text-white">camping</span>
                    </div>
                    <h2 class="font-display text-xl font-bold text-deep-slate dark:text-white">Tent Manager</h2>
                </div>
                <div class="mb-10">
                    <h3 class="font-display text-3xl font-extrabold text-deep-slate dark:text-white sm:text-4xl">Sign In
                    </h3>
                    <p class="mt-2 text-slate-500 dark:text-slate-400">Access your Tent Manager account</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form class="space-y-6" method="POST" action="login.php">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            for="username">Email or Username</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="material-symbols-outlined text-xl text-slate-400">person</span>
                            </div>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-4 pl-12 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="username" name="username" placeholder="name@company.com" type="text" required />
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="password">Password</label>
                            <a class="mb-2 text-sm font-bold text-primary hover:underline"
                                href="forgot_password.php">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="material-symbols-outlined text-xl text-slate-400">lock</span>
                            </div>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-4 pl-12 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="password" name="password" placeholder="••••••••" type="password" required />
                            <button
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600"
                                type="button" onclick="togglePassword()">
                                <span class="material-symbols-outlined text-xl" id="eyeIcon">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input
                            class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800"
                            id="remember" type="checkbox" />
                        <label class="ml-3 text-sm font-medium text-slate-600 dark:text-slate-400" for="remember">Keep
                            me logged in</label>
                    </div>
                    <button
                        class="flex w-full items-center justify-center gap-2 rounded-full bg-primary py-4 text-base font-bold text-white shadow-lg shadow-primary/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                        type="submit">
                        <span>Login</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>
                <!-- Footer -->
                <div class="mt-8 text-center space-y-4">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                        Admin Portal
                    </p>
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col items-center gap-3">
                        <a class="text-sm font-bold text-primary hover:underline flex items-center justify-center gap-2"
                            href="checkin.php">
                            <span class="material-symbols-outlined text-[18px]">how_to_reg</span> Public Attendance
                        </a>
                        <a class="text-sm font-medium text-slate-500 hover:text-primary transition-colors flex items-center justify-center gap-2"
                            href="join.php">
                            <span class="material-symbols-outlined text-[18px]">group_add</span> New Member Registration
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerText = 'visibility_off';
            } else {
                input.type = 'password';
                icon.innerText = 'visibility';
            }
        }

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_PATH ?>/service-worker.js')
                    .then(reg => console.log('SW registered!', reg))
                    .catch(err => console.log('SW failed!', err));
            });
        }
    </script>
</body>

</html>