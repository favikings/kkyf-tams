<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel members-directory" aria-labelledby="members-title">
        <?php
        $badgeIcon = static function (string $badge): string {
            return match ($badge) {
                'Unstoppable' => 'flame',
                'Faithful' => 'shield-check',
                'On Fire' => 'zap',
                'First Step' => 'sparkles',
                '1-Year Member' => 'award',
                '6-Month Member' => 'medal',
                '3-Month Member' => 'star',
                default => 'badge-check',
            };
        };
        $callHref = static function (?string $phone): ?string {
            $normalized = preg_replace('/(?!^\+)[^\d]/', '', trim((string) $phone)) ?? '';

            return $normalized !== '' ? 'tel:' . $normalized : null;
        };
        ?>
        <div class="directory-header">
            <div>
                <h1 id="members-title">Member Directory</h1>
                <p class="lede">Manage active constituents, track attendance, and oversee Tent assignments.</p>
            </div>
            <div class="directory-actions">
                <button type="button" data-modal-open="add-member-modal"><i data-lucide="user-plus"></i> Add Member</button>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="directory-filter-card" method="GET" action="members">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search members, phones...">
            </label>
            <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
                <label>
                    <span>Tent Location</span>
                    <select name="tent_id">
                        <option value="">All Tents</option>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($selectedStatus ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($selectedStatus ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </label>
            <button type="submit"><i data-lucide="search"></i> Search</button>
        </form>

        <div class="member-table-card">
            <?php if ($members === []): ?>
                <div class="empty-state">No v2 members match this view.</div>
            <?php endif; ?>

            <?php if ($members !== []): ?>
                <div class="member-table-scroll">
                    <table class="member-table">
                        <thead>
                            <tr>
                                <th>Member Name & ID</th>
                                <th>Contact Info</th>
                                <th>Tent Assignment</th>
                                <th>Status</th>
                                <th>Streak</th>
                                <th>Badges</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <?php
                                $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                                $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                ?>
                                <tr>
                                    <td data-label="Member">
                                        <div class="member-identity">
                                            <span class="member-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                            <div>
                                                <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small>KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="member-mobile-detail" data-label="Contact Info">
                                        <strong class="table-primary-text"><?= htmlspecialchars($member['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td class="member-mobile-detail" data-label="Tent Assignment"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="member-mobile-detail" data-label="Status">
                                        <span class="status-pill <?= $member['active_status'] === 'active' ? 'is-active' : 'is-inactive' ?>">
                                            <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="member-mobile-detail" data-label="Streak">
                                        <span class="member-streak-cell">
                                            <i data-lucide="<?= (int) ($member['current_streak'] ?? 0) > 0 ? 'flame' : 'minus' ?>"></i>
                                            <?= (int) ($member['current_streak'] ?? 0) ?> wks
                                        </span>
                                    </td>
                                    <td class="member-mobile-detail" data-label="Badges">
                                        <?php if (!empty($member['badges'])): ?>
                                            <div class="member-badge-row">
                                                <?php foreach (array_slice($member['badges'], 0, 3) as $badge): ?>
                                                    <span class="member-badge-icon" title="<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>">
                                                        <i data-lucide="<?= $badgeIcon((string) $badge) ?>"></i>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if (count($member['badges']) > 3): ?>
                                                    <span class="member-badge-more">+<?= count($member['badges']) - 3 ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <?php $memberCallHref = $callHref((string) ($member['phone'] ?? '')); ?>
                                        <div class="table-actions">
                                            <a class="icon-button" href="members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="eye"></i>
                                            </a>
                                            <?php if ($memberCallHref !== null): ?>
                                                <a class="icon-button" href="<?= htmlspecialchars($memberCallHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="Call <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="phone"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="icon-button is-disabled-inline" title="No phone saved for call" aria-hidden="true">
                                                    <i data-lucide="phone"></i>
                                                </span>
                                            <?php endif; ?>
                                            <form method="POST" action="members/deactivate">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                                <button class="icon-button danger-icon" type="submit" aria-label="Deactivate <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="ban"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="member-table-footer">
                    <span>Showing <?= count($members) ?> members</span>
                    <span class="muted">Limited to the current v2 directory view</span>
                </div>
            <?php endif; ?>
        </div>
</section>

<div class="modal-backdrop" data-modal="add-member-modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="add-member-title">
        <div class="modal-header">
            <div>
                <div class="eyebrow">New Profile</div>
                <h2 id="add-member-title">Add a member</h2>
            </div>
            <button class="icon-button" type="button" data-modal-close aria-label="Close add member form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="management-form modal-form" method="POST" action="members/create" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="full_name" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone">
                </label>
                <label>
                    <span>Birth Month</span>
                    <select name="birth_month">
                        <option value="">Month</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="<?= $month ?>"><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>
                    <span>Birth Day</span>
                    <select name="birth_day">
                        <option value="">Day</option>
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>
                    <span>Occupation</span>
                    <select name="occupation">
                        <option>Student</option>
                        <option>Worker</option>
                        <option>Alumni</option>
                    </select>
                </label>
                <label>
                    <span>School Name</span>
                    <input type="text" name="school_name">
                </label>
                <label>
                    <span>Join Date</span>
                    <input type="date" name="join_date">
                </label>
                <label>
                    <span>Tent</span>
                    <select name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>">
                                <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Profile Photo</span>
                    <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                </label>
                <label class="span-2">
                    <span>Notes</span>
                    <textarea name="notes" rows="3"></textarea>
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-modal-close>Cancel</button>
                <button type="submit"><i data-lucide="user-plus"></i> Add Member</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
