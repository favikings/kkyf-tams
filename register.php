<?php
// register.php
require_once 'includes/db_connect.php';

// Fetch Tents for Dropdown
try {
    $stmt = $pdo->query("SELECT Tent_ID, Tent_Name FROM Tents ORDER BY Tent_Name ASC");
    $tents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading tents: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Tent Manager | Register</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script src="assets/js/stitch_alerts.js"></script>
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
        <!-- Left Side: Abstract -->
        <div
            class="relative hidden w-full items-center justify-center overflow-hidden bg-deep-slate lg:flex lg:w-1/2 xl:w-7/12">
            <div class="absolute inset-0 z-0">
                <div class="organic-shape-1 absolute -top-20 -left-20 h-96 w-96 bg-primary/20 blur-3xl"></div>
                <div
                    class="organic-shape-2 absolute bottom-[-10%] right-[-10%] h-[500px] w-[500px] bg-primary/10 blur-3xl">
                </div>
            </div>
            <div class="relative z-10 px-12 text-center">
                <div
                    class="mb-8 inline-flex items-center justify-center rounded-2xl bg-primary p-4 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-5xl text-white">how_to_reg</span>
                </div>
                <h1 class="font-display text-5xl font-black tracking-tight text-white xl:text-6xl">
                    Join the <span class="text-primary">Team</span>
                </h1>
                <p class="mt-6 font-display text-xl font-medium text-slate-300">
                    Become a Tent Admin and lead your squad.
                </p>
            </div>
        </div>

        <!-- Right Side: Form -->
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

                <div class="mb-8">
                    <h3 class="font-display text-3xl font-extrabold text-deep-slate dark:text-white sm:text-4xl">Create
                        Account</h3>
                    <p class="mt-2 text-slate-500 dark:text-slate-400">Register as a new Tent Admin.</p>
                </div>

                <form class="space-y-5" onsubmit="handleRegister(event)">
                    <div class="grid grid-cols-1 gap-5">
                        <!-- Email -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="email">Email Address</label>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-3.5 px-4 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="email" name="email" type="email" placeholder="you@example.com" required />
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="username">Username</label>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-3.5 px-4 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="username" name="username" type="text" placeholder="jdoe" required />
                        </div>

                        <!-- Tent Selection -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="tent_id">Assigned Tent</label>
                            <div class="relative">
                                <select id="tent_id" name="tent_id" required
                                    class="block w-full rounded-xl border-none bg-slate-50 py-3.5 px-4 text-deep-slate ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700 appearance-none">
                                    <option value="" disabled selected>Select your Tent...</option>
                                    <?php foreach ($tents as $tent): ?>
                                        <option value="<?= $tent['Tent_ID'] ?>"><?= htmlspecialchars($tent['Tent_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <span class="material-symbols-outlined">expand_more</span>
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="password">Password</label>
                            <div class="relative">
                                <input
                                    class="block w-full rounded-xl border-none bg-slate-50 py-3.5 px-4 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                    id="password" name="password" type="password" placeholder="••••••••" required
                                    minlength="6" />
                                <button type="button" onclick="togglePassword('password', 'eye1')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600">
                                    <span class="material-symbols-outlined" id="eye1">visibility</span>
                                </button>
                            </div>
                        </div>

                        <!-- Reg Code -->
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                                for="reg_code">Registration Code</label>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-3.5 px-4 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="reg_code" name="reg_code" type="password" placeholder="Secret Code" required />
                        </div>
                    </div>

                    <button
                        class="w-full rounded-full bg-primary py-4 text-base font-bold text-white shadow-lg shadow-primary/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                        type="submit" id="submitBtn">
                        Create Account
                    </button>

                    <div class="text-center">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                            Already have an account?
                            <a class="ml-1 font-bold text-primary hover:underline" href="login.php">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="stitch-alert-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- Add SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/stitch_alerts.js"></script>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerText = 'visibility_off';
            } else {
                input.type = 'password';
                icon.innerText = 'visibility';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerText;
            btn.innerText = 'Creating Account...';
            btn.disabled = true;

            const formData = new FormData(e.target);

            try {
                const res = await fetch('api/register_admin.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Success!
                    if (typeof StitchAlert !== 'undefined') {
                        await StitchAlert.showSuccess('Welcome!', 'Account created successfully. Please login.');
                    } else {
                        alert('Account created successfully. Please login.');
                    }
                    // Robust Redirect
                    window.location.assign('login.php');
                } else {
                    if (typeof StitchAlert !== 'undefined') {
                        StitchAlert.showError('Registration Failed', data.error);
                    } else {
                        alert('Error: ' + data.error);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('System Error: Could not connect to server.');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>