<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$summary = (array) ($report['summary'] ?? []);
$selectedType = (string) ($report['type'] ?? 'weekly');
$selectedTentId = (int) ($report['selected_tent_id'] ?? 0);
$dateFrom = (string) ($report['date_from'] ?? '');
$dateTo = (string) ($report['date_to'] ?? '');
$rows = (array) ($report['rows'] ?? []);
$isSunday = $selectedType === 'sunday';
?>

<section class="content-panel reports-hub" aria-labelledby="reports-title">
    <div class="directory-header">
        <div>
            <div class="eyebrow">Phase 12</div>
            <h1 id="reports-title">Reports & Exports</h1>
            <p class="lede">Review attendance performance by window, filter by tent, and export the current report to Excel or PDF.</p>
        </div>
        <div class="directory-actions">
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports/export?type=<?= urlencode($selectedType) ?>&amp;tent_id=<?= $selectedTentId ?>&amp;date_from=<?= urlencode($dateFrom) ?>&amp;date_to=<?= urlencode($dateTo) ?>&amp;format=excel">
                <i data-lucide="sheet"></i> Export Excel
            </a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports/export?type=<?= urlencode($selectedType) ?>&amp;tent_id=<?= $selectedTentId ?>&amp;date_from=<?= urlencode($dateFrom) ?>&amp;date_to=<?= urlencode($dateTo) ?>&amp;format=pdf">
                <i data-lucide="file-text"></i> Export PDF
            </a>
        </div>
    </div>

    <form class="directory-filter-card report-filter-card" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports">
        <label>
            <span>Report Type</span>
            <select name="type">
                <option value="weekly" <?= $selectedType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $selectedType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $selectedType === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                <option value="sunday" <?= $selectedType === 'sunday' ? 'selected' : '' ?>>Sunday Summary</option>
            </select>
        </label>
        <label>
            <span>Date From</span>
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
            <span>Date To</span>
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" <?= $isSunday ? 'readonly' : '' ?>>
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label>
                <span>Tent</span>
                <select name="tent_id">
                    <option value="">All tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit"><i data-lucide="search"></i> Run Report</button>
    </form>

    <div class="birthday-summary-grid report-summary-grid">
        <article class="birthday-summary-card">
            <span>Total Check-ins</span>
            <strong><?= number_format((int) ($summary['total_checkins'] ?? 0)) ?></strong>
            <small>Attendance entries in this window</small>
        </article>
        <article class="birthday-summary-card">
            <span>Unique Members</span>
            <strong><?= number_format((int) ($summary['unique_members'] ?? 0)) ?></strong>
            <small>Distinct members represented</small>
        </article>
        <article class="birthday-summary-card">
            <span>Tents Reached</span>
            <strong><?= number_format((int) ($summary['tents_reached'] ?? 0)) ?></strong>
            <small>Scoped tents with attendance</small>
        </article>
        <article class="birthday-summary-card">
            <span>Average Daily</span>
            <strong><?= number_format((float) ($summary['average_daily_attendance'] ?? 0), 1) ?></strong>
            <small>Average attendance per recorded day</small>
        </article>
    </div>

    <div class="member-table-card">
        <div class="card-heading report-card-heading">
            <h2><?= htmlspecialchars((string) ($report['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?></h2>
            <span class="soft-filter"><?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <?php if ($rows === []): ?>
            <div class="empty-state">No records match the current report filters.</div>
        <?php else: ?>
            <div class="member-table-scroll">
                <table class="member-table report-table">
                    <thead>
                        <tr>
                            <?php foreach ((array) ($report['columns'] ?? []) as $column): ?>
                                <th><?= htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php if ($isSunday): ?>
                                <tr>
                                    <td data-label="Member">
                                        <strong class="table-primary-text"><?= htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td data-label="Tent"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Phone"><?= htmlspecialchars((string) ($row['phone'] ?: 'No phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Checked By"><?= htmlspecialchars((string) ($row['checked_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Source"><?= htmlspecialchars(ucfirst((string) ($row['source'] ?? 'web')), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td data-label="Date">
                                        <strong class="table-primary-text"><?= htmlspecialchars((string) ($row['attendance_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td data-label="Tent"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Check-ins"><?= number_format((int) ($row['total_checkins'] ?? 0)) ?></td>
                                    <td data-label="Unique Members"><?= number_format((int) ($row['unique_members'] ?? 0)) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="member-table-footer">
                <span>Showing <?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></span>
                <span class="muted">Exports follow the exact filters currently selected</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
