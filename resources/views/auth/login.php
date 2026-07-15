<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="auth-shell relative overflow-hidden bg-[#f5f1e8]">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.18),transparent_26%)]"></div>
    <header class="auth-topbar border-b border-black/5 bg-white/70 backdrop-blur">
        <a class="font-display tracking-[-0.03em]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">KKYF Portal</a>
        <span class="auth-inline-link text-portal-muted">Need help? Contact admin</span>
    </header>

    <div class="auth-stage relative z-10">
        <section class="auth-panel auth-panel-login overflow-hidden border border-white/70 bg-white/90 shadow-panel backdrop-blur" aria-labelledby="login-title">
            <div class="auth-emblem border border-emerald-100 bg-white shadow-soft" aria-hidden="true">
                <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/images/logo.jpg" alt="KKYF logo">
            </div>

            <div class="auth-copy">
                <h1 class="font-display text-portal-ink" id="login-title">Welcome Back</h1>
                <p class="text-portal-muted">Access the KKYF governance and membership portal.</p>
            </div>

            <form class="auth-form" method="POST" action="login" autocomplete="on">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <?php if (!empty($error)): ?>
                    <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <label>
                    <span class="font-bold text-portal-ink">Email Address</span>
                    <span class="input-shell">
                        <i data-lucide="mail"></i>
                        <input class="border border-portal-line bg-[#fcfbf8]" type="email" name="email" required autocomplete="email" placeholder="name@example.com">
                    </span>
                </label>

                <label>
                    <span class="font-bold text-portal-ink">Password</span>
                    <span class="input-shell">
                        <i data-lucide="lock"></i>
                        <input class="border border-portal-line bg-[#fcfbf8]" id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="login-password" aria-label="Show password">
                            <i data-lucide="eye"></i>
                        </button>
                    </span>
                </label>

                <div class="auth-utility-row text-portal-muted" aria-label="Login options">
                    <label class="auth-checkbox">
                        <input type="checkbox" name="remember_me" value="1">
                        <span>Remember Me</span>
                    </label>
                    <span class="auth-inline-link">Admin-assisted sign in</span>
                </div>

                <button class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white" type="submit"><span>Login</span><i data-lucide="arrow-right"></i></button>
            </form>

            <div class="auth-panel-footer bg-[#f7f4ed]">
                Don't have an account? <span>Contact Admin</span>
            </div>
        </section>

        <div class="auth-security-note border border-white/70 bg-white/70 text-portal-muted shadow-soft backdrop-blur">
            <i data-lucide="shield-check"></i>
            <span>End-to-end encrypted session</span>
        </div>
    </div>

    <footer class="auth-site-footer relative z-10 text-portal-muted">
        <div>
            <strong class="font-display text-portal-ink">KKYF Portal</strong>
            <span>&copy; 2024 Ken Katas Youth Foundation. All rights reserved.</span>
        </div>
        <div class="auth-site-links" aria-label="Legal and support links">
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
            <span>Contact Support</span>
        </div>
    </footer>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
