<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$recordCount = count($records);
$uniqueDates = count(array_unique(array_column($records, 'attendance_date')));
?>

<section class="content-panel attendance-history-v2" aria-labelledby="history-title">
    <div class="report-header">
        <div>
            <div class="eyebrow">Attendance Review</div>
            <h1 id="history-title">Attendance History</h1>
            <p class="lede">
                Review service check-ins, filter by date or tent, and validate migrated attendance records in one place.
            </p>
        </div>
        <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Back to Check-in</a>
    </div>

    <div class="history-summary-grid">
        <article class="history-summary-card">
            <span>Current Sunday</span>
            <strong><?= number_format((int) $summary['total']) ?></strong>
            <small><?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?></small>
        </article>
        <article class="history-summary-card">
            <span>Filtered Records</span>
            <strong><?= number_format($recordCount) ?></strong>
            <small>Current report result</small>
        </article>
        <article class="history-summary-card">
            <span>Service Dates</span>
            <strong><?= number_format($uniqueDates) ?></strong>
            <small>Represented in this view</small>
        </article>
    </div>

    <form class="history-filter-card" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">
        <label>
            <span>Date</span>
            <input type="date" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label>
                <span>Tent</span>
                <select name="tent_id">
                    <option value="">All tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit"><i data-lucide="filter"></i> Filter</button>
    </form>

    <section class="history-table-card" aria-labelledby="history-table-title">
        <div class="card-heading">
            <h2 id="history-table-title">Check-in Records</h2>
            <span class="soft-filter"><?= number_format($recordCount) ?> rows</span>
        </div>

        <?php if ($records === []): ?>
            <div class="empty-state">No attendance records match this view.</div>
        <?php endif; ?>

        <?php if ($records !== []): ?>
            <div class="history-table-wrap">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Date</th>
                            <th>Tent</th>
                            <th>Service</th>
                            <th>Checked By</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td data-label="Member">
                                    <div class="member-identity">
                                        <span class="member-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div>
                                            <strong><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Date"><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Tent"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Service">
                                    <span class="status-pill is-active"><?= htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Checked By"><?= htmlspecialchars($record['checked_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Source">
                                    <span class="source-pill"><?= htmlspecialchars($record['source'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
