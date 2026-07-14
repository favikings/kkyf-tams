<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$overview = $overview ?? [];
$badgeClass = static function (array $tent): string {
    if (($tent['status'] ?? 'inactive') !== 'active') {
        return 'is-inactive';
    }

    $memberCount = (int) ($tent['member_count'] ?? 0);
    $monthAttendance = (int) ($tent['month_attendance_count'] ?? 0);
    $attendanceRate = $memberCount > 0 ? (int) round(($monthAttendance / $memberCount) * 100) : 0;

    if ($memberCount > 0 && $attendanceRate < 40) {
        return 'is-critical';
    }

    return 'is-active';
};
?>

<section class="content-panel tents-admin-v2" aria-labelledby="tents-title">
        <div class="dashboard-v2-header">
            <div>
                <div class="eyebrow">Super Admin</div>
                <h1 id="tents-title">Tent Management</h1>
                <p class="lede">Oversee and manage all regional tents, leaders, and performance.</p>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="as-link" data-modal-open="create-tent-modal"><i data-lucide="plus"></i> Create New Tent</button>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="tent-summary-grid">
            <article class="tent-summary-card">
                <div class="tent-summary-head">
                    <span>Total Active Tents</span>
                    <i data-lucide="network"></i>
                </div>
                <strong><?= number_format((int) ($overview['active_tents'] ?? 0)) ?></strong>
                <small>+<?= number_format((int) ($overview['created_this_year'] ?? 0)) ?> this year</small>
            </article>
            <article class="tent-summary-card">
                <div class="tent-summary-head">
                    <span>Average Tent Size</span>
                    <i data-lucide="users"></i>
                </div>
                <strong><?= number_format((int) ($overview['average_tent_size'] ?? 0)) ?></strong>
                <small>members</small>
            </article>
            <article class="tent-summary-card">
                <div class="tent-summary-head">
                    <span>Top Performing</span>
                    <i data-lucide="trending-up"></i>
                </div>
                <strong><?= htmlspecialchars((string) ($overview['top_performing_name'] ?? 'No data yet'), ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= number_format((int) ($overview['top_performing_rate'] ?? 0)) ?>% att.</small>
            </article>
            <article class="tent-summary-card tent-summary-card-alert">
                <div class="tent-summary-head">
                    <span>Needs Support</span>
                    <i data-lucide="triangle-alert"></i>
                </div>
                <strong><?= number_format((int) ($overview['needs_support_count'] ?? 0)) ?></strong>
                <small>tents under 40% att.</small>
            </article>
        </div>

        <div class="tent-grid-v2">
            <?php if ($tents === []): ?>
                <div class="empty-state">No v2 tents have been created yet.</div>
            <?php endif; ?>

            <?php foreach ($tents as $tent): ?>
                <?php
                $memberCount = (int) ($tent['member_count'] ?? 0);
                $monthAttendance = (int) ($tent['month_attendance_count'] ?? 0);
                $attendanceRate = $memberCount > 0 ? min(100, (int) round(($monthAttendance / $memberCount) * 100)) : 0;
                $leaderAssigned = !empty($tent['leader_name']);
                $adminAssigned = !empty($tent['admin_name']);
                $adminCount = (int) ($tent['admin_count'] ?? 0);
                $adminSummary = trim((string) ($tent['admin_names'] ?? ''));
                ?>
                <article class="tent-admin-card tent-admin-card-modern">
                    <div class="tent-card-band" style="background: <?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="tent-admin-header">
                        <div>
                            <h2><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="tent-status-line">
                                <span class="status-dot <?= $badgeClass($tent) ?>"></span>
                                <span><?= htmlspecialchars(ucfirst((string) $tent['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <button type="button" class="icon-button" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>" aria-label="Edit <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <i data-lucide="ellipsis-vertical"></i>
                        </button>
                    </div>

                    <div class="tent-leader-strip">
                        <div class="tent-avatar-chip">
                            <i data-lucide="<?= $leaderAssigned ? 'user-round' : 'user-round-x' ?>"></i>
                        </div>
                        <div>
                            <strong><?= htmlspecialchars($leaderAssigned ? (string) $tent['leader_name'] : 'Unassigned', ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($leaderAssigned ? 'Tent Leader' : 'Needs leader', ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>

                    <div class="tent-admin-meta tent-admin-meta-modern">
                        <div>
                            <span>Members</span>
                            <strong><?= number_format($memberCount) ?></strong>
                        </div>
                        <div>
                            <span>Attendance (Mo)</span>
                            <strong><?= $memberCount > 0 ? number_format($attendanceRate) . '%' : '--' ?></strong>
                        </div>
                    </div>

                    <div class="tent-actions-v2">
                        <button type="button" class="secondary-button" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                            <i data-lucide="pencil"></i> Edit
                        </button>
                        <?php if (!empty($tent['whatsapp_link'])): ?>
                            <a class="secondary-button" href="<?= htmlspecialchars((string) $tent['whatsapp_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <i data-lucide="link"></i> WhatsApp
                            </a>
                        <?php elseif (!$adminAssigned): ?>
                            <button type="button" class="as-link" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                                <i data-lucide="user-plus"></i> Assign
                            </button>
                        <?php else: ?>
                            <button type="button" class="secondary-button" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                                <i data-lucide="settings-2"></i> Manage
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="tent-card-meta-footer">
                        <span>
                            <?= htmlspecialchars(
                                $adminAssigned
                                    ? ($adminCount > 1 ? $adminCount . ' tent admins assigned' : (string) $tent['admin_name'])
                                    : 'No tent admin assigned',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                        <span><?= htmlspecialchars(!empty($tent['banner']) ? 'Banner saved' : 'No banner yet', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </article>

                <div class="modal-backdrop" data-modal="edit-tent-modal-<?= (int) $tent['id'] ?>" aria-hidden="true">
                    <div class="modal-panel tent-modal-panel" role="dialog" aria-modal="true" aria-labelledby="edit-tent-title-<?= (int) $tent['id'] ?>">
                        <div class="modal-header">
                            <div>
                                <div class="eyebrow">Tent Control</div>
                                <h2 id="edit-tent-title-<?= (int) $tent['id'] ?>"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <button class="icon-button" type="button" data-modal-close aria-label="Close tent editor">
                                <i data-lucide="x"></i>
                            </button>
                        </div>

                        <form class="management-form modal-form tent-form-v2" method="POST" action="tents/update" enctype="multipart/form-data">
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
                            <div class="modal-actions">
                                <button type="button" class="secondary-button" data-modal-close>Close</button>
                                <button type="submit"><i data-lucide="save"></i> Save Changes</button>
                            </div>
                        </form>

                        <form class="management-form modal-form tent-form-v2 tent-assign-form" method="POST" action="tents/assign-admin">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="tent_id" value="<?= (int) $tent['id'] ?>">
                            <div class="card-heading">
                                <h3>Assign Tent Admin</h3>
                                <?php if ($adminAssigned): ?>
                                    <p class="compact-copy">
                                        Current: <?= htmlspecialchars($adminSummary, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <label>
                                <span>Tent Admin</span>
                                <select name="user_id" required>
                                    <option value=""><?= htmlspecialchars($adminAssigned ? 'Add another admin' : 'Assign admin', ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php foreach ($tentAdmins as $admin): ?>
                                        <option value="<?= (int) $admin['id'] ?>">
                                            <?= htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit"><i data-lucide="user-check"></i> Assign Admin</button>
                        </form>

                        <form method="POST" action="<?= $tent['status'] === 'active' ? 'tents/deactivate' : 'tents/update' ?>">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $tent['id'] ?>">
                            <?php if ($tent['status'] !== 'active'): ?>
                                <input type="hidden" name="name" value="<?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="existing_banner" value="<?= htmlspecialchars($tent['banner'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="color" value="<?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="leader_name" value="<?= htmlspecialchars($tent['leader_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="leader_phone" value="<?= htmlspecialchars($tent['leader_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="whatsapp_link" value="<?= htmlspecialchars($tent['whatsapp_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="active">
                            <?php endif; ?>
                            <button class="<?= $tent['status'] === 'active' ? 'danger-button' : 'secondary-button' ?>" type="submit">
                                <i data-lucide="<?= $tent['status'] === 'active' ? 'ban' : 'rotate-ccw' ?>"></i>
                                <?= $tent['status'] === 'active' ? 'Deactivate Tent' : 'Reactivate Tent' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</section>

<div class="modal-backdrop" data-modal="create-tent-modal" aria-hidden="true">
    <div class="modal-panel tent-modal-panel" role="dialog" aria-modal="true" aria-labelledby="create-tent-title">
        <div class="modal-header">
            <div>
                <div class="eyebrow">New Tent</div>
                <h2 id="create-tent-title">Create New Tent</h2>
            </div>
            <button class="icon-button" type="button" data-modal-close aria-label="Close create tent form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="management-form modal-form tent-form-v2" method="POST" action="tents/create" enctype="multipart/form-data">
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
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-modal-close>Cancel</button>
                <button type="submit"><i data-lucide="plus"></i> Create New Tent</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
