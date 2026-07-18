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
$serviceDays = (int) ($summary['service_days'] ?? 0);
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="reports-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Phase 12</div>
            <h1 id="reports-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Reports & Exports</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">Review attendance performance by window, filter by tent, and export to Excel or a print-ready PDF layout.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports/export?type=<?= urlencode($selectedType) ?>&amp;tent_id=<?= $selectedTentId ?>&amp;date_from=<?= urlencode($dateFrom) ?>&amp;date_to=<?= urlencode($dateTo) ?>&amp;format=excel">
                <i data-lucide="sheet"></i>
                Export Excel
            </a>
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports/print?type=<?= urlencode($selectedType) ?>&amp;tent_id=<?= $selectedTentId ?>&amp;date_from=<?= urlencode($dateFrom) ?>&amp;date_to=<?= urlencode($dateTo) ?>" target="_blank" rel="noreferrer">
                <i data-lucide="printer"></i>
                Print / Save PDF
            </a>
        </div>
    </div>

    <div class="rounded-[24px] border border-emerald-100 bg-emerald-50 px-5 py-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <strong class="text-sm font-extrabold text-emerald-800">Branded PDF path enabled</strong>
            <small class="text-sm text-emerald-700">Open the print layout, then use your browser's Print or Save as PDF action for the polished report design.</small>
        </div>
    </div>

    <form class="mt-5 grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(180px,0.75fr)_minmax(180px,0.7fr)_minmax(180px,0.7fr)_minmax(180px,0.8fr)_auto]" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/reports">
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Report Type</span>
            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="type">
                <option value="weekly" <?= $selectedType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $selectedType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $selectedType === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                <option value="sunday" <?= $selectedType === 'sunday' ? 'selected' : '' ?>>Sunday Summary</option>
            </select>
        </label>
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Date From</span>
            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Date To</span>
            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" <?= $isSunday ? 'readonly' : '' ?>>
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label class="grid gap-2">
                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id">
                    <option value="">All tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <div class="flex items-end">
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                <i data-lucide="search"></i>
                Run Report
            </button>
        </div>
    </form>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Total Check-ins</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['total_checkins'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Attendance entries in this window</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Unique Members</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['unique_members'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Distinct members represented</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Tents Reached</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['tents_reached'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Scoped tents with attendance</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Service Days</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($serviceDays) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Recorded service dates</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:col-span-1">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Average Daily</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((float) ($summary['average_daily_attendance'] ?? 0), 1) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Average attendance per recorded day</small>
        </article>
    </div>

    <section class="mt-5 rounded-[24px] border border-slate-200 bg-white shadow-sm" aria-labelledby="report-results-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 id="report-results-title" class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars((string) ($report['title'] ?? 'Report'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mt-1 text-sm text-slate-500">Window: <?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($rows === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">No records match the current report filters.</div>
        <?php else: ?>
            <div class="hidden overflow-x-auto xl:block">
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
                            <?php if ($isSunday): ?>
                                <tr class="border-b border-slate-100 last:border-b-0">
                                    <td class="px-5 py-4 align-top">
                                        <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) ($row['phone'] ?: 'No phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) ($row['checked_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="inline-flex min-h-9 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= htmlspecialchars(ucfirst((string) ($row['source'] ?? 'web')), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr class="border-b border-slate-100 last:border-b-0">
                                    <td class="px-5 py-4 align-top">
                                        <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['attendance_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= number_format((int) ($row['total_checkins'] ?? 0)) ?></td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= number_format((int) ($row['unique_members'] ?? 0)) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-2 p-3 xl:hidden">
                <?php foreach ($rows as $row): ?>
                    <?php if ($isSunday): ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= htmlspecialchars(ucfirst((string) ($row['source'] ?? 'web')), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Phone</span>
                                    <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars((string) ($row['phone'] ?: 'No phone'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Checked By</span>
                                    <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars((string) ($row['checked_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </article>
                    <?php else: ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($row['attendance_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars((string) ($row['tent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Check-ins</span>
                                    <div class="mt-1 font-semibold text-slate-800"><?= number_format((int) ($row['total_checkins'] ?? 0)) ?></div>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Unique Members</span>
                                    <div class="mt-1 font-semibold text-slate-800"><?= number_format((int) ($row['unique_members'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col gap-2 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <span>Showing <?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></span>
                <span>Exports follow the exact filters currently selected</span>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
