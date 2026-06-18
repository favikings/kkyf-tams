<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$summary = (array) ($report['summary'] ?? []);
$trendPoints = (array) ($report['trend_points'] ?? []);
$tentPerformance = (array) ($report['tent_performance'] ?? []);
$demographics = (array) ($report['demographics'] ?? []);
$rows = (array) ($report['rows'] ?? []);
$reportTitle = (string) ($report['title'] ?? 'Organization Performance Report');
$reportType = (string) ($report['type'] ?? 'weekly');
$periodLabel = (string) ($report['period_label'] ?? '');
$generatedOn = (string) ($report['generated_on'] ?? date('M j, Y'));
$tentLabel = (string) ($report['selected_tent_name'] ?? 'All Tents');
$maxTrend = 1;

foreach ($trendPoints as $point) {
    $maxTrend = max($maxTrend, (int) ($point['value'] ?? 0));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css?v=<?= htmlspecialchars((string) filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="report-print-body">
    <div class="report-print-actions" data-print-controls>
        <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports?type=<?= urlencode($reportType) ?>&amp;tent_id=<?= (int) ($report['selected_tent_id'] ?? 0) ?>&amp;date_from=<?= urlencode((string) ($report['date_from'] ?? '')) ?>&amp;date_to=<?= urlencode((string) ($report['date_to'] ?? '')) ?>">
            Back to Reports
        </a>
        <button type="button" class="btn" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="report-print-shell">
        <section class="report-print-sheet">
            <header class="report-print-header">
                <div class="report-print-brand">
                    <div class="report-print-logo">K</div>
                    <div class="report-print-brand-copy">
                        <span>KKYF Portal</span>
                        <small><?= htmlspecialchars($tentLabel, ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
                <div class="report-print-generated">
                    <span>Generated On</span>
                    <strong><?= htmlspecialchars($generatedOn, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </header>

            <section class="report-print-hero">
                <div>
                    <h1><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p>Reporting Period: <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </section>

            <section class="report-print-section">
                <h2>Executive Summary</h2>
                <div class="report-stat-grid">
                    <article class="report-stat-card">
                        <span>Avg. Attendance</span>
                        <strong><?= number_format((float) ($summary['average_daily_attendance'] ?? 0), 1) ?></strong>
                    </article>
                    <article class="report-stat-card">
                        <span>New Members</span>
                        <strong><?= number_format((int) ($summary['new_members'] ?? 0)) ?></strong>
                    </article>
                    <article class="report-stat-card">
                        <span>Retention Rate</span>
                        <strong><?= number_format((int) ($summary['retention_rate'] ?? 0)) ?>%</strong>
                    </article>
                    <article class="report-stat-card">
                        <span>Total SMS Sent</span>
                        <strong><?= number_format((int) ($summary['sms_sent_total'] ?? 0)) ?></strong>
                    </article>
                </div>
            </section>

            <section class="report-print-section">
                <h2>Attendance Trends</h2>
                <div class="report-trend-card">
                    <div class="report-trend-bars">
                        <?php foreach ($trendPoints as $point): ?>
                            <?php
                            $value = (int) ($point['value'] ?? 0);
                            $height = max(14, (int) round(($value / $maxTrend) * 100));
                            ?>
                            <div class="report-trend-column">
                                <div class="report-trend-value"><?= number_format($value) ?></div>
                                <div class="report-trend-bar-wrap">
                                    <div class="report-trend-bar" style="height: <?= $height ?>%;"></div>
                                </div>
                                <span><?= htmlspecialchars((string) ($point['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="report-print-grid">
                <div class="report-print-panel">
                    <h2>Tent Performance</h2>
                    <div class="report-print-table-wrap">
                        <table class="report-print-table">
                            <thead>
                                <tr>
                                    <th>Tent Name</th>
                                    <th>Leader</th>
                                    <th>Total</th>
                                    <th>Avg. Att.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tentPerformance as $tent): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($tent['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($tent['leader_name'] ?? 'Not assigned'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) ($tent['total_checkins'] ?? 0)) ?></td>
                                        <td><?= number_format((int) ($tent['average_attendance_rate'] ?? 0)) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="report-print-panel report-demographics-panel">
                    <h2>Demographics</h2>
                    <div class="report-demographics-list">
                        <?php foreach ($demographics as $item): ?>
                            <div class="report-demographic-item">
                                <div class="report-demographic-row">
                                    <span><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= number_format((int) ($item['percentage'] ?? 0)) ?>%</strong>
                                </div>
                                <div class="report-demographic-track">
                                    <div class="report-demographic-fill" style="width: <?= max(0, min(100, (int) ($item['percentage'] ?? 0))) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </aside>
            </section>

            <section class="report-print-section">
                <h2>Detailed Breakdown</h2>
                <div class="report-print-table-wrap">
                    <table class="report-print-table report-print-table-detail">
                        <thead>
                            <tr>
                                <?php foreach ((array) ($report['columns'] ?? []) as $column): ?>
                                    <th><?= htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <?php if ($reportType === 'sunday'): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : 'No phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['checked_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(ucfirst((string) ($row['source'] ?? 'web')), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($row['attendance_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) ($row['total_checkins'] ?? 0)) ?></td>
                                        <td><?= number_format((int) ($row['unique_members'] ?? 0)) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="report-print-footer">
                <span>Confidential - Ken Katas Youth Foundation</span>
                <span>Page 1 of 1</span>
            </footer>
        </section>
    </main>
</body>
</html>
