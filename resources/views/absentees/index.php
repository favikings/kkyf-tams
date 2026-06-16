<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$levelClass = static function (string $level): string {
    return match ($level) {
        'Early Warning' => 'is-early-warning',
        'Follow-Up Required' => 'is-follow-up-required',
        'Critical' => 'is-critical',
        default => 'is-inactive',
    };
};
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
?>

<section class="content-panel absentee-directory" aria-labelledby="absentee-title">
    <div class="report-header">
        <div>
            <h1 id="absentee-title">Absentee Alerts</h1>
            <p class="lede">Identify members missing consecutive Sundays and resolve follow-up risks at the right time.</p>
        </div>
        <div class="dashboard-actions">
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="calendar-range"></i> Attendance History</a>
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members"><i data-lucide="users"></i> Member Directory</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="history-summary-grid">
        <article class="history-summary-card">
            <span>Open Alerts</span>
            <strong><?= number_format((int) ($summary['open_total'] ?? 0)) ?></strong>
            <small>Members currently requiring follow-up</small>
        </article>
        <article class="history-summary-card">
            <span>Critical</span>
            <strong><?= number_format((int) ($summary['critical_total'] ?? 0)) ?></strong>
            <small>4 or more missed Sundays</small>
        </article>
        <article class="history-summary-card">
            <span>Follow-Up Queue</span>
            <strong><?= number_format((int) (($summary['follow_up_total'] ?? 0) + ($summary['early_warning_total'] ?? 0))) ?></strong>
            <small>Early warning and follow-up required</small>
        </article>
    </div>

    <form class="history-filter-card" method="GET" action="absentees">
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label>
                <span>Tent</span>
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
            <span>Alert Level</span>
            <select name="level">
                <option value="">All Levels</option>
                <?php foreach (['Early Warning', 'Follow-Up Required', 'Critical'] as $level): ?>
                    <option value="<?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedLevel === $level ? 'selected' : '' ?>>
                        <?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>State</span>
            <select name="resolved">
                <option value="open" <?= $selectedResolved === 'open' ? 'selected' : '' ?>>Open Alerts</option>
                <option value="resolved" <?= $selectedResolved === 'resolved' ? 'selected' : '' ?>>Resolved Alerts</option>
            </select>
        </label>
        <button type="submit"><i data-lucide="search"></i> Filter</button>
    </form>

    <div class="history-table-card">
        <div class="card-heading">
            <h2>Current Absentee Queue</h2>
            <span class="soft-filter"><?= $selectedResolved === 'resolved' ? 'Resolved' : 'Open' ?></span>
        </div>

        <?php if ($alerts === []): ?>
            <div class="empty-state">No absentee alerts match this view right now.</div>
        <?php else: ?>
            <div class="history-table-wrap">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Tent</th>
                            <th>Alert Level</th>
                            <th>Missed Sundays</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td data-label="Member">
                                    <strong><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($alert['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td data-label="Tent"><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Alert Level">
                                    <span class="status-pill <?= $levelClass((string) $alert['alert_level']) ?>">
                                        <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td data-label="Missed Sundays"><?= (int) $alert['missed_count'] ?> wks</td>
                                <td data-label="Status">
                                    <span class="source-pill"><?= (int) $alert['resolved'] === 1 ? 'Resolved' : 'Open' ?></span>
                                </td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <a class="icon-button" href="members/show?id=<?= (int) $alert['member_id'] ?>" aria-label="Open member profile">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if ((int) $alert['resolved'] === 0): ?>
                                            <form method="POST" action="absentees/resolve">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                                <button class="icon-button" type="submit" aria-label="Resolve alert">
                                                    <i data-lucide="check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
