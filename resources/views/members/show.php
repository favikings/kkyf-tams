<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php
$birthday = 'Not set';
if (!empty($member['date_of_birth']) && preg_match('/^\d{2}-\d{2}$/', $member['date_of_birth'])) {
    [$month, $day] = explode('-', $member['date_of_birth']);
    $birthday = date('F j', mktime(0, 0, 0, (int) $month, (int) $day));
}

$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
$initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
[$birthMonth, $birthDay] = array_pad(explode('-', $member['date_of_birth'] ?? ''), 2, '');
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
?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel member-profile-v2" aria-labelledby="member-title">
    <nav class="breadcrumb-line" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">Members</a>
        <span>›</span>
        <strong>Member Profile</strong>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="profile-layout-v2">
        <section class="profile-hero-card" aria-labelledby="member-title">
            <?php if (!empty($member['profile_photo'])): ?>
                <img
                    class="profile-photo-v2"
                    src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/photo?id=<?= (int) $member['id'] ?>"
                    alt="<?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?> profile photo"
                >
            <?php else: ?>
                <div class="profile-photo-v2 profile-photo-placeholder">
                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="profile-hero-main">
                <div class="profile-title-row">
                    <div>
                        <h1 id="member-title"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p><i data-lucide="badge"></i> KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></p>
                    </div>
                    <span class="status-pill <?= $member['active_status'] === 'active' ? 'is-active' : 'is-inactive' ?>">
                        <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <dl class="profile-summary-grid">
                    <div>
                        <dt>Tent</dt>
                        <dd><i data-lucide="network"></i> <?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Join Date</dt>
                        <dd><?= htmlspecialchars($member['join_date'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Category</dt>
                        <dd><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <aside class="profile-stat-stack">
            <article class="profile-stat-card">
                <span>Current Streak</span>
                <strong><?= (int) ($member['current_streak'] ?? 0) ?> wks</strong>
                <small><?= !empty($member['last_attendance_date']) ? 'Last attendance: ' . htmlspecialchars($member['last_attendance_date'], ENT_QUOTES, 'UTF-8') : 'No Sunday attendance yet' ?></small>
            </article>
            <article class="profile-stat-card">
                <span>Total Attendance</span>
                <strong><?= number_format((int) ($member['total_attendance'] ?? 0)) ?></strong>
                <small>Longest streak: <?= (int) ($member['longest_streak'] ?? 0) ?> weeks</small>
            </article>
        </aside>

        <section class="profile-info-card" aria-labelledby="personal-info-title">
            <h2 id="personal-info-title">Personal Information</h2>
            <dl class="profile-info-list">
                <div><dt>Phone</dt><dd><?= htmlspecialchars($member['phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>D.O.B</dt><dd><?= htmlspecialchars($birthday, ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Occupation</dt><dd><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>School/Institution</dt><dd><?= htmlspecialchars($member['school_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </section>

        <section class="profile-info-card profile-notes-card" aria-labelledby="badge-vault-title">
            <div class="card-heading">
                <h2 id="badge-vault-title">Badge Vault</h2>
                <span class="soft-filter"><?= count($member['badges'] ?? []) ?> earned</span>
            </div>
            <?php if (!empty($member['badges'])): ?>
                <div class="profile-badge-grid">
                    <?php foreach ($member['badges'] as $badge): ?>
                        <div class="profile-badge-card">
                            <span class="profile-badge-icon">
                                <i data-lucide="<?= $badgeIcon((string) $badge) ?>"></i>
                            </span>
                            <strong><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No badges earned yet. Consistent Sunday attendance will unlock them.</div>
            <?php endif; ?>
        </section>

        <section class="profile-info-card profile-notes-card" aria-labelledby="notes-title">
            <div class="card-heading">
                <h2 id="notes-title">Follow-up Notes</h2>
                <span class="soft-filter">Current</span>
            </div>
            <div class="notes-surface">
                <?= nl2br(htmlspecialchars($member['notes'] ?: 'No notes recorded for this member yet.', ENT_QUOTES, 'UTF-8')) ?>
            </div>
        </section>

        <section class="profile-info-card profile-edit-card" aria-labelledby="edit-member-title">
            <div class="card-heading">
                <h2 id="edit-member-title">Edit Member</h2>
                <span class="soft-filter">Profile Details</span>
            </div>

            <form class="management-form profile-edit-form" method="POST" action="../members/update" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                <input type="hidden" name="existing_profile_photo" value="<?= htmlspecialchars($member['profile_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-grid">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Birth Month</span>
                        <select name="birth_month">
                            <option value="">Month</option>
                            <?php for ($month = 1; $month <= 12; $month++): ?>
                                <option value="<?= $month ?>" <?= (int) $birthMonth === $month ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $month, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>
                        <span>Birth Day</span>
                        <select name="birth_day">
                            <option value="">Day</option>
                            <?php for ($day = 1; $day <= 31; $day++): ?>
                                <option value="<?= $day ?>" <?= (int) $birthDay === $day ? 'selected' : '' ?>><?= $day ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>
                        <span>Occupation</span>
                        <select name="occupation">
                            <?php foreach (['Student', 'Worker', 'Alumni'] as $occupation): ?>
                                <option value="<?= $occupation ?>" <?= $member['occupation'] === $occupation ? 'selected' : '' ?>><?= $occupation ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>School Name</span>
                        <input type="text" name="school_name" value="<?= htmlspecialchars($member['school_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Join Date</span>
                        <input type="date" name="join_date" value="<?= htmlspecialchars($member['join_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Tent</span>
                        <select name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                            <?php foreach ($tents as $tent): ?>
                                <option value="<?= (int) $tent['id'] ?>" <?= (int) $member['tent_id'] === (int) $tent['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="active_status">
                            <option value="active" <?= $member['active_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $member['active_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </label>
                    <label>
                        <span>Profile Photo</span>
                        <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                        <?php if (!empty($member['profile_photo'])): ?>
                            <span class="file-note">Current photo saved</span>
                        <?php endif; ?>
                    </label>
                    <label class="span-2">
                        <span>Notes</span>
                        <textarea name="notes" rows="4"><?= htmlspecialchars($member['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
                <button type="submit"><i data-lucide="save"></i> Save Member</button>
            </form>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
