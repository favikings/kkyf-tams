<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<section class="content-panel" aria-labelledby="attendance-title">
    <div class="eyebrow">Phase 4</div>
    <h1 id="attendance-title">Sunday Attendance</h1>
    <p class="lede">
        Check in active members for <?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?>.
        Duplicate Sunday check-ins are blocked.
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="status-grid">
        <div class="status-item">
            <i data-lucide="calendar-check"></i>
            Sunday total<br>
            <strong><?= (int) $summary['total'] ?></strong>
        </div>
        <div class="status-item">
            <i data-lucide="clock"></i>
            Service<br>
            <strong>Sunday Service</strong>
        </div>
    </div>

    <form class="filter-bar" method="GET" action="attendance">
        <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search member by name or phone">
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

    <div class="record-list">
        <?php if ($members === []): ?>
            <div class="empty-state">No active members match this attendance search.</div>
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
                    <?php if (!empty($member['attendance_id'])): ?>
                        <span class="status-pill is-active">Checked in</span>
                    <?php else: ?>
                        <form method="POST" action="attendance/check-in">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                            <button type="submit"><i data-lucide="check"></i> Check In</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="nav-links">
        <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="history"></i> View history</a>
    </p>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
