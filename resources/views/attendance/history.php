<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<section class="content-panel" aria-labelledby="history-title">
    <div class="eyebrow">Attendance</div>
    <h1 id="history-title">Attendance History</h1>
    <p class="lede">
        Sunday report total for <?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?>:
        <?= (int) $summary['total'] ?> check-ins.
    </p>

    <form class="filter-bar" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">
        <input type="date" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
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
        <button type="submit"><i data-lucide="filter"></i> Filter</button>
    </form>

    <div class="record-list">
        <?php if ($records === []): ?>
            <div class="empty-state">No attendance records match this view.</div>
        <?php endif; ?>

        <?php foreach ($records as $record): ?>
            <article class="record-card">
                <div>
                    <h2><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="muted">
                        <?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?>
                        · <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                        · Checked by <?= htmlspecialchars($record['checked_by_name'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="record-actions">
                    <span class="status-pill is-active"><?= htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="status-pill"><?= htmlspecialchars($record['source'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="nav-links">
        <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Back to check-in</a>
    </p>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
