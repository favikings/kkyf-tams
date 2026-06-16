<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel tents-admin-v2" aria-labelledby="tents-title">
        <div class="directory-header">
            <div>
                <div class="eyebrow">Super Admin</div>
                <h1 id="tents-title">Tent Management</h1>
                <p class="lede">Create, update, deactivate, and assign Tent Admins to every KKYF tent.</p>
            </div>
            <div class="tent-count-pill">
                <i data-lucide="network"></i>
                <?= number_format(count($tents)) ?> tents
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="tent-create-card" aria-labelledby="create-tent-title">
            <div class="card-heading">
                <div>
                    <h2 id="create-tent-title">Add Tent</h2>
                    <p class="muted">Create a new tent location and optional display details.</p>
                </div>
                <span class="soft-filter">New</span>
            </div>

            <form class="management-form tent-form-v2" method="POST" action="tents/create" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-grid">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" required>
                    </label>
                    <label>
                        <span>Color</span>
                        <input class="color-input" type="color" name="color" value="#00bd06">
                    </label>
                    <label>
                        <span>Leader Name</span>
                        <input type="text" name="leader_name">
                    </label>
                    <label>
                        <span>Leader Phone</span>
                        <input type="text" name="leader_phone">
                    </label>
                    <label class="span-2">
                        <span>Banner</span>
                        <input type="file" name="banner" accept="image/png,image/jpeg,image/webp,image/gif">
                    </label>
                    <label class="span-2">
                        <span>WhatsApp Link</span>
                        <input type="url" name="whatsapp_link" placeholder="https://chat.whatsapp.com/...">
                    </label>
                </div>
                <button type="submit"><i data-lucide="plus"></i> Create Tent</button>
            </form>
        </section>

        <div class="tent-grid-v2">
            <?php if ($tents === []): ?>
                <div class="empty-state">No v2 tents have been created yet.</div>
            <?php endif; ?>

            <?php foreach ($tents as $tent): ?>
                <article class="tent-admin-card">
                    <div class="tent-admin-header">
                        <div class="tent-color-mark" style="background: <?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div>
                            <h2><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="muted">
                                <?= htmlspecialchars($tent['leader_name'] ?: 'No leader set', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($tent['leader_phone'])): ?>
                                    · <?= htmlspecialchars($tent['leader_phone'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="status-pill <?= $tent['status'] === 'active' ? 'is-active' : 'is-inactive' ?>">
                            <?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="tent-admin-meta">
                        <div>
                            <span>Tent Admin</span>
                            <strong><?= htmlspecialchars($tent['admin_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <span>WhatsApp</span>
                            <strong><?= htmlspecialchars($tent['whatsapp_link'] ? 'Configured' : 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <span>Banner</span>
                            <strong><?= htmlspecialchars($tent['banner'] ? 'Uploaded' : 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <form class="management-form compact-form tent-form-v2" method="POST" action="tents/update" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $tent['id'] ?>">
                        <input type="hidden" name="existing_banner" value="<?= htmlspecialchars($tent['banner'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-grid">
                            <label>
                                <span>Name</span>
                                <input type="text" name="name" value="<?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label>
                                <span>Status</span>
                                <select name="status">
                                    <option value="active" <?= $tent['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $tent['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </label>
                            <label>
                                <span>Color</span>
                                <input class="color-input" type="color" name="color" value="<?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label>
                                <span>Leader Phone</span>
                                <input type="text" name="leader_phone" value="<?= htmlspecialchars($tent['leader_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label>
                                <span>Leader Name</span>
                                <input type="text" name="leader_name" value="<?= htmlspecialchars($tent['leader_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label>
                                <span>Banner</span>
                                <input type="file" name="banner" accept="image/png,image/jpeg,image/webp,image/gif">
                                <?php if (!empty($tent['banner'])): ?>
                                    <span class="file-note">Current banner saved</span>
                                <?php endif; ?>
                            </label>
                            <label class="span-2">
                                <span>WhatsApp Link</span>
                                <input type="url" name="whatsapp_link" value="<?= htmlspecialchars($tent['whatsapp_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                        </div>
                        <button type="submit"><i data-lucide="save"></i> Save Changes</button>
                    </form>

                    <div class="tent-actions-v2">
                        <form method="POST" action="tents/assign-admin">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="tent_id" value="<?= (int) $tent['id'] ?>">
                            <label>
                                <span>Tent Admin</span>
                                <select name="user_id" required>
                                    <option value=""><?= htmlspecialchars($tent['admin_name'] ?? 'Assign admin', ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php foreach ($tentAdmins as $admin): ?>
                                        <option value="<?= (int) $admin['id'] ?>" <?= (int) ($tent['admin_id'] ?? 0) === (int) $admin['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit"><i data-lucide="user-check"></i> Assign</button>
                        </form>

                        <form method="POST" action="tents/deactivate">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $tent['id'] ?>">
                            <button class="danger-button" type="submit"><i data-lucide="ban"></i> Deactivate</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
