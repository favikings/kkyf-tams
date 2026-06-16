<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="auth-shell">
    <header class="auth-topbar">
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">KKYF Portal</a>
        <button class="auth-help-button" type="button" aria-label="Help" disabled title="Help options will be added in a later phase">
            <i data-lucide="circle-help"></i>
        </button>
    </header>

    <div class="auth-stage">
        <section class="auth-panel auth-panel-login" aria-labelledby="login-title">
            <div class="auth-emblem" aria-hidden="true">
                <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/images/logo.jpg" alt="KKYF logo">
            </div>

            <div class="auth-copy">
                <h1 id="login-title">Welcome Back</h1>
                <p>Access the KKYF governance and membership portal.</p>
            </div>

            <form class="auth-form" method="POST" action="login" autocomplete="on">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <?php if (!empty($error)): ?>
                    <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <label>
                    <span>Email Address</span>
                    <span class="input-shell">
                        <i data-lucide="mail"></i>
                        <input type="email" name="email" required autocomplete="email" placeholder="name@example.com">
                    </span>
                </label>

                <label>
                    <span>Password</span>
                    <span class="input-shell">
                        <i data-lucide="lock"></i>
                        <input id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="login-password" aria-label="Show password">
                            <i data-lucide="eye"></i>
                        </button>
                    </span>
                </label>

                <div class="auth-utility-row" aria-label="Login options">
                    <label class="auth-checkbox">
                        <input type="checkbox" name="remember_me" value="1">
                        <span>Remember Me</span>
                    </label>
                    <span class="auth-inline-link is-disabled" aria-disabled="true" title="Password recovery will be added in a later phase">Forgot Password?</span>
                </div>

                <button type="submit"><span>Login</span><i data-lucide="arrow-right"></i></button>
            </form>

            <div class="auth-panel-footer">
                Don't have an account? <span>Contact Admin</span>
            </div>
        </section>

        <div class="auth-security-note">
            <i data-lucide="shield-check"></i>
            <span>End-to-end encrypted session</span>
        </div>
    </div>

    <footer class="auth-site-footer">
        <div>
            <strong>KKYF Portal</strong>
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
