<?php
require_once 'includes/db_connect.php';
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Tent Manager | Reset Password</title>
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
                    <span class="material-symbols-outlined text-5xl text-white">key</span>
                </div>
                <h1 class="font-display text-4xl font-black tracking-tight text-white xl:text-5xl">
                    Secure <span class="text-primary">Your Account</span>
                </h1>
                <p class="mt-6 font-display text-xl font-medium text-slate-300">
                    Choose a strong password to protect your tent.
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

                <div class="mb-10">
                    <h3 class="font-display text-3xl font-extrabold text-deep-slate dark:text-white sm:text-4xl">Reset
                        Password</h3>
                    <p class="mt-2 text-slate-500 dark:text-slate-400">Create a new password for your account.</p>
                </div>

                <form class="space-y-6" onsubmit="handleReset(event)">
                    <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            for="password">New Password</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="material-symbols-outlined text-xl text-slate-400">lock</span>
                            </div>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-4 pl-12 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="password" type="password" placeholder="••••••••" required minlength="6" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300"
                            for="confirm_password">Confirm Password</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="material-symbols-outlined text-xl text-slate-400">lock_clock</span>
                            </div>
                            <input
                                class="block w-full rounded-xl border-none bg-slate-50 py-4 pl-12 text-deep-slate placeholder-slate-400 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary dark:bg-slate-800/50 dark:text-white dark:ring-slate-700"
                                id="confirm_password" type="password" placeholder="••••••••" required minlength="6" />
                        </div>
                    </div>

                    <button
                        class="flex w-full items-center justify-center gap-2 rounded-full bg-primary py-4 text-base font-bold text-white shadow-lg shadow-primary/30 transition-all hover:scale-[1.02] active:scale-[0.98]"
                        type="submit" id="submitBtn">
                        <span>Reset Password</span>
                        <span class="material-symbols-outlined">check_circle</span>
                    </button>

                    <a href="login.php"
                        class="flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 py-4 text-base font-bold text-slate-600 transition-all hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                        Back to Login
                    </a>
                </form>

            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="stitch-alert-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        async function handleReset(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;

            const token = document.getElementById('token').value;
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;

            if (p1 !== p2) {
                StitchAlert.showError('Error', 'Passwords do not match.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span>Processing...</span>';

            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('token', token);
                formData.append('password', p1);

                const res = await fetch('api/auth_ops.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    await StitchAlert.showSuccess('Success', 'Password updated! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    StitchAlert.showError('Error', data.error || 'Failed to reset password.');
                }
            } catch (err) {
                console.error(err);
                StitchAlert.showError('System Error', 'Could not connect to server.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>
</body>

</html>