<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$isSuperAdmin = ($user['role'] ?? null) === 'Super Admin';
$cards = $metrics['cards'] ?? [];
$primaryCard = $cards[0] ?? ['label' => 'Members', 'value' => 0, 'icon' => 'users'];
$secondaryCards = array_slice($cards, 1, 3);
$absenteeSummary = $metrics['absentee_summary'] ?? ['open_total' => 0, 'critical_total' => 0];
$absenteeAlerts = $metrics['absentee_alerts'] ?? [];
$attendancePercent = 0;

if (!empty($cards[1]['value'])) {
    $attendancePercent = min(100, (int) round(((int) ($cards[3]['value'] ?? 0) / max(1, (int) $cards[1]['value'])) * 100));
}

$trendBars = [34, 46, 42, 54, 66, 74];
?>

<section class="content-panel dashboard-panel dashboard-v2" aria-labelledby="dashboard-title">
    <div class="dashboard-v2-header">
        <div>
            <div class="eyebrow"><?= $isSuperAdmin ? 'Global Command Center' : 'Localized Command Center' ?></div>
            <h1 id="dashboard-title"><?= $isSuperAdmin ? 'Overview' : 'Tent Overview' ?></h1>
            <p class="lede">
                <?= $isSuperAdmin
                    ? 'Track members, tents, and Sunday attendance across the full KKYF portal.'
                    : 'Monitor member activity, attendance, and follow-up work for your assigned tent.' ?>
            </p>
        </div>

        <div class="dashboard-actions">
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="download"></i> Export Reports</a>
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Take Attendance</a>
        </div>
    </div>

    <div class="metric-grid metric-grid-v2">
        <article class="metric-card kpi-card kpi-card-feature">
            <div class="metric-card-top">
                <span><?= htmlspecialchars($primaryCard['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="kpi-icon"><i data-lucide="<?= htmlspecialchars($primaryCard['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong><?= number_format((int) $primaryCard['value']) ?></strong>
            <small><i data-lucide="trending-up"></i> Current v2 records</small>
        </article>

        <?php foreach ($secondaryCards as $card): ?>
            <article class="metric-card kpi-card">
                <div class="metric-card-top">
                    <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="kpi-icon"><i data-lucide="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                </div>
                <strong><?= number_format((int) $card['value']) ?></strong>
                <small>Updated from migrated portal data</small>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-layout-v2">
        <section class="dashboard-card trend-card" aria-labelledby="attendance-trend-title">
            <div class="card-heading">
                <h2 id="attendance-trend-title">Attendance Trends</h2>
                <span class="soft-filter">Last 6 Months</span>
            </div>
            <div class="bar-chart" aria-label="Attendance trend chart">
                <?php foreach ($trendBars as $index => $height): ?>
                    <div class="bar-column">
                        <span style="height: <?= (int) $height ?>%"></span>
                        <small><?= htmlspecialchars(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][$index], ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="dashboard-card alert-rail-card" aria-labelledby="dashboard-alerts-title">
            <div>
                <h2 id="dashboard-alerts-title"><i data-lucide="triangle-alert"></i> Absentee Alerts</h2>
                <p class="muted">
                    <?= $isSuperAdmin
                        ? number_format((int) ($absenteeSummary['critical_total'] ?? 0)) . ' critical and ' . number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' open alerts across KKYF.'
                        : number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' active alert(s) inside your tent follow-up queue.' ?>
                </p>
            </div>

            <div class="stack-list">
                <?php if ($absenteeAlerts === []): ?>
                    <div class="empty-state">No absentee alerts are active right now.</div>
                <?php endif; ?>

                <?php foreach ($absenteeAlerts as $alert): ?>
                    <div class="alert-member-row">
                        <div>
                            <strong><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <span><?= (int) $alert['missed_count'] ?> wks</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <a class="text-link alert-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/absentees">Open absentee queue</a>
        </aside>

        <section class="dashboard-card mini-report-card" aria-labelledby="recent-members-title">
            <div class="card-heading">
                <h2 id="recent-members-title">Recent Members</h2>
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">View all</a>
            </div>

            <div class="stack-list">
                <?php if (($metrics['recent_members'] ?? []) === []): ?>
                    <div class="empty-state">No recent members yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_members'] ?? []) as $member): ?>
                    <?php
                    $memberParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $memberInitials = strtoupper(substr($memberParts[0] ?? 'M', 0, 1) . substr($memberParts[1] ?? '', 0, 1));
                    ?>
                    <div class="mini-row">
                        <span class="mini-icon mini-avatar"><?= htmlspecialchars($memberInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card mini-report-card" aria-labelledby="recent-attendance-title">
            <div class="card-heading">
                <h2 id="recent-attendance-title">Recent Attendance</h2>
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">History</a>
            </div>

            <div class="stack-list">
                <?php if (($metrics['recent_attendance'] ?? []) === []): ?>
                    <div class="empty-state">No attendance records yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_attendance'] ?? []) as $record): ?>
                    <?php
                    $attendanceParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $attendanceInitials = strtoupper(substr($attendanceParts[0] ?? 'M', 0, 1) . substr($attendanceParts[1] ?? '', 0, 1));
                    ?>
                    <div class="mini-row">
                        <span class="mini-icon mini-avatar"><?= htmlspecialchars($attendanceInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <strong><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card progress-card" aria-labelledby="capacity-title">
            <div class="card-heading">
                <h2 id="capacity-title"><?= $isSuperAdmin ? 'Attendance Pulse' : 'Session Summary' ?></h2>
                <span class="soft-filter"><?= $attendancePercent ?>%</span>
            </div>
            <div class="progress-meter">
                <span style="width: <?= (int) $attendancePercent ?>%"></span>
            </div>
            <div class="progress-details">
                <span>Today vs active members</span>
                <strong><?= (int) ($cards[3]['value'] ?? 0) ?> checked in</strong>
            </div>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
