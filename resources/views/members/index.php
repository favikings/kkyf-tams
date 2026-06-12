<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel" aria-labelledby="members-title">
        <div class="eyebrow">Phase 3</div>
        <h1 id="members-title">Member Management</h1>
        <p class="lede">Add, search, filter, edit, and deactivate v2 member records.</p>

        <?php if (!empty($error)): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form class="filter-bar" method="GET" action="members">
            <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by name or phone">
            <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
                <select name="tent_id">
                    <option value="">All tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="submit"><i data-lucide="search"></i> Search</button>
        </form>

        <div class="cta-card">
            <div>
                <h2>Add a member</h2>
                <p class="muted">Create a new profile without leaving the member list.</p>
            </div>
            <button type="button" data-modal-open="add-member-modal"><i data-lucide="user-plus"></i> Add Member</button>
        </div>

        <div class="record-list">
            <?php if ($members === []): ?>
                <div class="empty-state">No v2 members match this view.</div>
            <?php endif; ?>

            <?php foreach ($members as $member): ?>
                <article class="record-card">
                    <div>
                        <h2><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="muted">
                            <?= htmlspecialchars($member['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?>
                            · <?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="record-actions">
                        <span class="status-pill <?= $member['active_status'] === 'active' ? 'is-active' : 'is-inactive' ?>">
                            <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <a class="link-button as-link" href="members/show?id=<?= (int) $member['id'] ?>"><i data-lucide="eye"></i> View Profile</a>
                        <form method="POST" action="members/deactivate">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                            <button class="danger-button" type="submit"><i data-lucide="ban"></i> Deactivate</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
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
