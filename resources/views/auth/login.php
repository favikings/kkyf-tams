<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.18),transparent_26%),linear-gradient(180deg,#faf7f1_0%,#f1ece2_100%)]">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.18),transparent_26%)]"></div>
    <header class="relative z-10 flex min-h-16 items-center justify-between border-b border-black/5 bg-white/70 px-5 backdrop-blur xl:px-10">
        <a class="font-display text-[1.75rem] tracking-[-0.04em] text-portal-ink no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">KKYF Portal</a>
        <span class="text-sm font-medium text-portal-muted">Need help? Contact admin</span>
    </header>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-64px)] w-full max-w-6xl items-center justify-center px-4 py-12 xl:px-8">
        <section class="grid w-full max-w-[1080px] overflow-hidden rounded-[36px] border border-white/80 bg-white/88 shadow-panel backdrop-blur xl:grid-cols-[1.05fr_0.95fr]" aria-labelledby="login-title">
            <div class="relative hidden overflow-hidden bg-[#102017] p-10 text-white xl:flex xl:flex-col xl:justify-between">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.08),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.16),transparent_28%)]"></div>
                <div class="relative">
                    <div class="inline-flex items-center rounded-full border border-white/10 bg-white/8 px-4 py-2 text-[0.72rem] font-extrabold uppercase tracking-[0.18em] text-white/72">Membership Portal v2</div>
                    <h1 class="mt-8 font-display text-[clamp(3rem,5vw,3.8rem)] leading-[0.88] tracking-[-0.06em]" id="login-title">Sign in to your KKYF workspace.</h1>
                    <p class="mt-6 max-w-md text-base leading-8 text-white/72">Manage attendance, members, follow-up, and reporting from one clean admin portal built for the team.</p>
                </div>
                <div class="relative mt-10 grid gap-4">
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-5">
                        <strong class="block text-sm font-bold text-white">Unified operations</strong>
                        <span class="mt-2 block text-sm leading-7 text-white/68">Attendance, member tracking, absentees, and reports stay in one shared workflow.</span>
                    </div>
                    <div class="rounded-[28px] border border-white/10 bg-white/8 p-5">
                        <strong class="block text-sm font-bold text-white">Built for fast check-ins</strong>
                        <span class="mt-2 block text-sm leading-7 text-white/68">Keep service-day activity moving with a cleaner, more focused admin experience.</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-center px-6 py-8 xl:px-10 xl:py-10">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex items-center gap-4">
                        <div class="grid h-16 w-16 place-items-center rounded-[22px] border border-emerald-100 bg-white shadow-soft" aria-hidden="true">
                            <img class="h-11 w-11 rounded-full object-cover" src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/images/logo.jpg" alt="KKYF logo">
                        </div>
                        <div>
                            <p class="text-[0.72rem] font-extrabold uppercase tracking-[0.18em] text-emerald-800">Welcome Back</p>
                            <h2 class="font-display text-[clamp(2.2rem,5vw,2.7rem)] leading-none tracking-[-0.05em] text-portal-ink">Sign In</h2>
                        </div>
                    </div>

                    <p class="mb-8 text-base leading-8 text-portal-muted">Access the KKYF governance and membership portal with your admin credentials.</p>

                    <form class="grid gap-5" method="POST" action="login" autocomplete="on">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <?php if (!empty($error)): ?>
                            <div class="rounded-[22px] border border-red-200 bg-red-50 px-4 py-4 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>

                        <label class="grid gap-2">
                            <span class="text-sm font-bold text-portal-ink">Email Address</span>
                            <span class="relative block">
                                <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-portal-muted" data-lucide="mail"></i>
                                <input class="h-14 w-full rounded-[20px] border border-[#d7dfd6] bg-[#fcfbf8] pl-12 pr-4 text-sm text-portal-ink outline-none placeholder:text-portal-muted/75 focus:border-emerald-500" type="email" name="email" required autocomplete="email" placeholder="name@example.com">
                            </span>
                        </label>

                        <label class="grid gap-2">
                            <span class="text-sm font-bold text-portal-ink">Password</span>
                            <span class="relative block">
                                <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-portal-muted" data-lucide="lock"></i>
                                <input class="h-14 w-full rounded-[20px] border border-[#d7dfd6] bg-[#fcfbf8] pl-12 pr-14 text-sm text-portal-ink outline-none placeholder:text-portal-muted/75 focus:border-emerald-500" id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                                <button class="absolute right-3 top-1/2 inline-grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full text-portal-muted transition hover:bg-[#f1ece2] hover:text-portal-ink" type="button" data-password-toggle data-password-target="login-password" aria-label="Show password">
                                    <i data-lucide="eye"></i>
                                </button>
                            </span>
                        </label>

                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-portal-muted" aria-label="Login options">
                            <label class="inline-flex items-center gap-2">
                                <input class="h-4 w-4 rounded border-[#d7dfd6] text-emerald-700" type="checkbox" name="remember_me" value="1">
                                <span>Remember Me</span>
                            </label>
                            <span>Admin-assisted sign in</span>
                        </div>

                        <button class="inline-flex min-h-14 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 text-sm font-bold text-white shadow-soft transition hover:-translate-y-[1px]" type="submit"><span>Login</span><i data-lucide="arrow-right"></i></button>
                    </form>

                    <div class="mt-8 rounded-[24px] bg-[#f7f2e8] px-5 py-4 text-sm text-portal-muted">
                        Don't have an account? <span class="font-bold text-portal-ink">Contact Admin</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="relative z-10 flex flex-col items-center justify-between gap-3 px-5 pb-8 text-center text-sm text-portal-muted xl:flex-row xl:px-10 xl:text-left">
        <div class="flex flex-col gap-1">
            <strong class="font-display text-[1.3rem] tracking-[-0.03em] text-portal-ink">KKYF Portal</strong>
            <span>&copy; 2024 Ken Katas Youth Foundation. All rights reserved.</span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-4" aria-label="Legal and support links">
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
            <span>Contact Support</span>
        </div>
    </footer>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
