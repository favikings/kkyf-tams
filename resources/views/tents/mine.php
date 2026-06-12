<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel" aria-labelledby="my-tent-title">
        <div class="eyebrow">Tent Admin</div>
        <h1 id="my-tent-title">My Tent</h1>

        <?php if ($tent === null): ?>
            <p class="lede">No tent has been assigned to your v2 account yet.</p>
        <?php else: ?>
            <article class="tent-card">
                <div class="tent-card-header">
                    <div>
                        <h2><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="muted">
                            <?= htmlspecialchars($tent['leader_name'] ?: 'No leader set', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <span class="status-pill <?= $tent['status'] === 'active' ? 'is-active' : 'is-inactive' ?>">
                        <?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <dl class="detail-list">
                    <div>
                        <dt>Leader Phone</dt>
                        <dd><?= htmlspecialchars($tent['leader_phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd><?= htmlspecialchars($tent['whatsapp_link'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Color</dt>
                        <dd><?= htmlspecialchars($tent['color'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>
            </article>
        <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
