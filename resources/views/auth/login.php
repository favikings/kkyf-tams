<?php require dirname(__DIR__) . '/partials/header.php'; ?>

<main class="auth-shell">
    <section class="auth-panel" aria-labelledby="login-title">
        <div class="auth-copy">
            <div class="eyebrow">KKYF Portal v2</div>
            <h1 id="login-title">Sign in</h1>
            <p class="lede">Use your v2 account credentials to access the protected portal foundation.</p>
        </div>

        <form class="auth-form" method="POST" action="login" autocomplete="on">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <?php if (!empty($error)): ?>
                <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label>
                <span>Email</span>
                <input type="email" name="email" required autocomplete="email">
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <button type="submit"><i data-lucide="log-in"></i> Login</button>
        </form>
    </section>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
