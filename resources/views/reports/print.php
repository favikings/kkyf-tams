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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            corePlugins: {
                preflight: false
            },
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'Segoe UI', 'sans-serif'],
                        display: ['Cormorant Garamond', 'Georgia', 'serif']
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            [data-print-controls] {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            main {
                padding: 0 !important;
            }

            .print-sheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-[#f3eee3] font-sans text-slate-900">
    <div class="sticky top-0 z-20 border-b border-black/5 bg-white/90 px-4 py-4 backdrop-blur" data-print-controls>
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 no-underline transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports?type=<?= urlencode($reportType) ?>&amp;tent_id=<?= (int) ($report['selected_tent_id'] ?? 0) ?>&amp;date_from=<?= urlencode((string) ($report['date_from'] ?? '')) ?>&amp;date_to=<?= urlencode((string) ($report['date_to'] ?? '')) ?>">
                Back to Reports
            </a>
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-[#013f26] px-5 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" onclick="window.print()">
                Print / Save PDF
            </button>
        </div>
    </div>

    <main class="px-4 py-6 sm:px-6 lg:px-8">
        <section class="print-sheet mx-auto max-w-6xl overflow-hidden rounded-[36px] border border-white/70 bg-white shadow-[0_30px_90px_rgba(16,32,23,0.12)]">
            <header class="bg-[linear-gradient(135deg,#102017_0%,#173824_50%,#1b8a4b_100%)] px-6 py-8 text-white sm:px-8 sm:py-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-4">
                            <div class="grid h-14 w-14 place-items-center rounded-[20px] bg-white/12 font-display text-3xl font-bold text-white">K</div>
                            <div class="min-w-0">
                                <span class="block text-xs font-extrabold uppercase tracking-[0.22em] text-white/72">KKYF Portal</span>
                                <strong class="block truncate font-display text-[1.9rem] tracking-[-0.04em]"><?= htmlspecialchars($tentLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        </div>
                        <h1 class="mt-8 font-display text-[clamp(2.7rem,5vw,4.2rem)] leading-[0.9] tracking-[-0.06em]"><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-white/78">Reporting Period: <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="grid gap-3 rounded-[28px] border border-white/12 bg-white/10 px-5 py-4 text-sm">
                        <div>
                            <span class="block text-[11px] font-extrabold uppercase tracking-[0.14em] text-white/65">Generated On</span>
                            <strong class="mt-1 block text-base text-white"><?= htmlspecialchars($generatedOn, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <span class="block text-[11px] font-extrabold uppercase tracking-[0.14em] text-white/65">Report Type</span>
                            <strong class="mt-1 block text-base text-white"><?= htmlspecialchars(ucfirst($reportType), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>
            </header>

            <div class="px-6 py-6 sm:px-8 sm:py-8">
                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-xl font-extrabold text-slate-900">Executive Summary</h2>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-800">Snapshot</span>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Avg. Attendance</span>
                            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((float) ($summary['average_daily_attendance'] ?? 0), 1) ?></strong>
                        </article>
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">New Members</span>
                            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['new_members'] ?? 0)) ?></strong>
                        </article>
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Retention Rate</span>
                            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['retention_rate'] ?? 0)) ?>%</strong>
                        </article>
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Total SMS Sent</span>
                            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['sms_sent_total'] ?? 0)) ?></strong>
                        </article>
                    </div>
                </section>

                <section class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Attendance Trends</h2>
                    <div class="mt-4 rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                        <div class="grid min-h-[250px] grid-cols-<?= max(1, count($trendPoints)) ?> gap-3 items-end">
                            <?php foreach ($trendPoints as $point): ?>
                                <?php
                                $value = (int) ($point['value'] ?? 0);
                                $height = max(14, (int) round(($value / $maxTrend) * 100));
                                ?>
                                <div class="flex min-w-0 flex-col items-center justify-end gap-3">
                                    <span class="text-xs font-bold text-slate-500"><?= number_format($value) ?></span>
                                    <div class="flex h-44 w-full items-end justify-center rounded-[24px] bg-white px-2 py-3">
                                        <div class="w-full rounded-[18px] bg-[linear-gradient(180deg,#5ccf8c_0%,#1b8a4b_100%)]" style="height: <?= $height ?>%;"></div>
                                    </div>
                                    <span class="text-center text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500"><?= htmlspecialchars((string) ($point['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="mt-8 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="rounded-[28px] border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-xl font-extrabold text-slate-900">Tent Performance</h2>
                        </div>
                        <div class="overflow-hidden">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50/80">
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent Name</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Total</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Avg. Att.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tentPerformance as $tent): ?>
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="px-5 py-4 text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($tent['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars((string) ($tent['leader_name'] ?? 'Not assigned'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-700"><?= number_format((int) ($tent['total_checkins'] ?? 0)) ?></td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-700"><?= number_format((int) ($tent['average_attendance_rate'] ?? 0)) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                        <h2 class="text-xl font-extrabold text-slate-900">Demographics</h2>
                        <div class="mt-5 grid gap-4">
                            <?php foreach ($demographics as $item): ?>
                                <div class="rounded-[22px] border border-white bg-white px-4 py-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong class="text-sm font-extrabold text-slate-900"><?= number_format((int) ($item['percentage'] ?? 0)) ?>%</strong>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-[linear-gradient(90deg,#102017_0%,#1b8a4b_100%)]" style="width: <?= max(0, min(100, (int) ($item['percentage'] ?? 0))) ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                </section>

                <section class="mt-8 rounded-[28px] border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-xl font-extrabold text-slate-900">Detailed Breakdown</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/80">
                                    <?php foreach ((array) ($report['columns'] ?? []) as $column): ?>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500"><?= htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <?php if ($reportType === 'sunday'): ?>
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="px-5 py-4 text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars((string) (($row['phone'] ?? '') !== '' ? $row['phone'] : 'No phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars((string) ($row['checked_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars(ucfirst((string) ($row['source'] ?? 'web')), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="px-5 py-4 text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['attendance_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= number_format((int) ($row['total_checkins'] ?? 0)) ?></td>
                                            <td class="px-5 py-4 text-sm text-slate-600"><?= number_format((int) ($row['unique_members'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <span>Confidential - Ken Katas Youth Foundation</span>
                <span>Page 1 of 1</span>
            </footer>
        </section>
    </main>
</body>
</html>
