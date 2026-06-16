<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel my-tent-v2" aria-labelledby="my-tent-title">
    <?php if ($tent === null): ?>
        <div class="empty-state">No tent has been assigned to your v2 account yet.</div>
    <?php else: ?>
        <div class="my-tent-header">
            <div>
                <div class="eyebrow">Localized Command Center</div>
                <h1 id="my-tent-title"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="attendance-meta-row">
                    <span><i data-lucide="user"></i> Leader: <?= htmlspecialchars($tent['leader_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                    <span><i data-lucide="badge-check"></i> Status: <?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <?php if (!empty($tent['whatsapp_link'])): ?>
                <a class="secondary-button" href="<?= htmlspecialchars($tent['whatsapp_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <i data-lucide="message-square"></i> WhatsApp Group <i data-lucide="external-link"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="my-tent-layout">
            <section class="my-tent-hero-card">
                <div class="tent-banner-preview" style="--tent-color: <?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (!empty($tent['banner'])): ?>
                        <div class="banner-saved-indicator"><i data-lucide="image"></i> Banner uploaded</div>
                    <?php else: ?>
                        <div class="banner-saved-indicator"><i data-lucide="palette"></i> Brand color</div>
                    <?php endif; ?>
                </div>

                <dl class="my-tent-details">
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
                        <dd>
                            <span class="color-swatch" style="background: <?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>"></span>
                            <?= htmlspecialchars($tent['color'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?>
                        </dd>
                    </div>
                </dl>
            </section>

            <aside class="profile-stat-stack">
                <article class="profile-stat-card">
                    <span>Tent Status</span>
                    <strong><?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small>Current v2 tent state</small>
                </article>
                <article class="profile-stat-card">
                    <span>Local Tools</span>
                    <strong>Ready</strong>
                    <small>Members and attendance are available from the sidebar</small>
                </article>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
