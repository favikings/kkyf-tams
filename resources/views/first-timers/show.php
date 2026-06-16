<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
$initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
$statusClass = match ((string) ($record['status'] ?? '')) {
    'Pending' => 'is-pending',
    'Called' => 'is-called',
    'Converted' => 'is-converted',
    'Not Returning' => 'is-not-returning',
    default => 'is-inactive',
};
?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="content-panel member-profile-v2 first-timer-profile-v2" aria-labelledby="first-timer-title">
    <nav class="breadcrumb-line" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/first-timers">First Timers</a>
        <span>›</span>
        <strong>Follow-up Profile</strong>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="profile-layout-v2">
        <section class="profile-hero-card" aria-labelledby="first-timer-title">
            <div class="profile-photo-v2 profile-photo-placeholder">
                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="profile-hero-main">
                <div class="profile-title-row">
                    <div>
                        <h1 id="first-timer-title"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p><i data-lucide="badge"></i> FT-<?= str_pad((string) (int) $record['id'], 4, '0', STR_PAD_LEFT) ?></p>
                    </div>
                    <span class="status-pill <?= $statusClass ?>">
                        <?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <dl class="profile-summary-grid">
                    <div>
                        <dt>Tent</dt>
                        <dd><i data-lucide="network"></i> <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>First Visit</dt>
                        <dd><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Contact</dt>
                        <dd><?= htmlspecialchars($record['phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>
            </div>
        </section>

        <aside class="profile-stat-stack">
            <article class="profile-stat-card">
                <span>Current Status</span>
                <strong><?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Follow-up workflow in progress</small>
            </article>
            <article class="profile-stat-card">
                <span>Conversion</span>
                <strong><?= !empty($record['converted_member_id']) ? 'Done' : 'Open' ?></strong>
                <small><?= !empty($record['converted_member_name']) ? 'Linked to member profile' : 'Ready when visitor is confirmed' ?></small>
            </article>
        </aside>

        <section class="profile-info-card" aria-labelledby="first-timer-info-title">
            <h2 id="first-timer-info-title">Visitor Details</h2>
            <dl class="profile-info-list">
                <div><dt>Phone</dt><dd><?= htmlspecialchars($record['phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>First Visit Date</dt><dd><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Assigned Tent</dt><dd><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Converted Member</dt><dd><?= htmlspecialchars($record['converted_member_name'] ?? 'Not converted yet', ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
        </section>

        <section class="profile-info-card profile-notes-card" aria-labelledby="followup-title">
            <div class="card-heading">
                <h2 id="followup-title">Follow-up Notes</h2>
                <span class="soft-filter">Current</span>
            </div>
            <div class="notes-surface">
                <?= nl2br(htmlspecialchars($record['followup_notes'] ?: 'No notes recorded for this visitor yet.', ENT_QUOTES, 'UTF-8')) ?>
            </div>
        </section>

        <section class="profile-info-card profile-edit-card" aria-labelledby="edit-first-timer-title">
            <div class="card-heading">
                <h2 id="edit-first-timer-title">Update Follow-up</h2>
                <span class="soft-filter">Visitor Record</span>
            </div>

            <form class="management-form profile-edit-form" method="POST" action="../first-timers/update">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                <div class="form-grid">
                    <label>
                        <span>Full Name</span>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($record['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>First Visit Date</span>
                        <input type="date" name="first_visit_date" value="<?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status" <?= ($record['status'] ?? '') === 'Converted' ? 'disabled' : '' ?>>
                            <?php foreach (['Pending', 'Called', 'Not Returning'] as $status): ?>
                                <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $record['status'] === $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (($record['status'] ?? '') === 'Converted'): ?>
                                <option value="Converted" selected>Converted</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label class="span-2">
                        <span>Tent</span>
                        <select name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                            <?php foreach ($tents as $tent): ?>
                                <option value="<?= (int) $tent['id'] ?>" <?= (int) $record['tent_id'] === (int) $tent['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="span-2">
                        <span>Follow-up Notes</span>
                        <textarea name="followup_notes" rows="4"><?= htmlspecialchars($record['followup_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
                <button type="submit"><i data-lucide="save"></i> Save Follow-up</button>
            </form>
        </section>

        <section class="profile-info-card profile-edit-card first-timer-convert-card" aria-labelledby="convert-first-timer-title">
            <div class="card-heading">
                <h2 id="convert-first-timer-title">Convert to Member</h2>
                <span class="soft-filter"><?= !empty($record['converted_member_id']) ? 'Completed' : 'Ready' ?></span>
            </div>

            <?php if (!empty($record['converted_member_id'])): ?>
                <div class="empty-state">
                    This first-timer has already been converted.
                    <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $record['converted_member_id'] ?>">Open member profile</a>
                </div>
            <?php else: ?>
                <form class="management-form profile-edit-form" id="convert-first-timer" method="POST" action="../first-timers/convert">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                    <div class="form-grid">
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
                            <span>Join Date</span>
                            <input type="date" name="join_date" value="<?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="span-2">
                            <span>Member Notes</span>
                            <textarea name="notes" rows="4" placeholder="Optional notes to carry into the new member profile."></textarea>
                        </label>
                    </div>
                    <button type="submit"><i data-lucide="refresh-cw"></i> Convert to Member</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
