<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$recordCount = count($records);
$uniqueDates = count(array_unique(array_column($records, 'attendance_date')));
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="history-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Attendance Review</div>
            <h1 id="history-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Attendance History</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">
                Review service check-ins, filter by date or tent, and validate migrated attendance records in one place.
            </p>
        </div>
        <a class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance">
            <i data-lucide="clipboard-check"></i>
            Back to Check-in
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Current Sunday</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) $summary['total']) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500"><?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?></small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Filtered Records</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($recordCount) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Current report result</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Service Dates</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($uniqueDates) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Represented in this view</small>
        </article>
    </div>

    <form class="mt-5 grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(190px,0.8fr)_minmax(190px,0.8fr)_auto]" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Date</span>
            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="date" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label class="grid gap-2">
                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id">
                    <option value="">All tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <div class="flex items-end">
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                <i data-lucide="filter"></i>
                Filter
            </button>
        </div>
    </form>

    <section class="mt-5 rounded-[24px] border border-slate-200 bg-white shadow-sm" aria-labelledby="history-table-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 id="history-table-title" class="text-xl font-extrabold text-slate-900">Check-in Records</h2>
                <p class="mt-1 text-sm text-slate-500">Filtered attendance records across service dates and tents.</p>
            </div>
            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= number_format($recordCount) ?> rows</span>
        </div>

        <?php if ($records === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">No attendance records match this view.</div>
        <?php endif; ?>

        <?php if ($records !== []): ?>
            <div class="hidden overflow-x-auto xl:block">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Date</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Service</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Checked By</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            ?>
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="min-w-0">
                                            <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="text-sm text-slate-500"><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-9 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700"><?= htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($record['checked_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-9 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= htmlspecialchars($record['source'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-2 overflow-hidden p-3 xl:hidden">
                <?php foreach ($records as $record): ?>
                    <?php
                    $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                    ?>
                    <article class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <span class="inline-flex max-w-full min-h-8 shrink items-center break-words rounded-full bg-slate-100 px-3 text-center text-xs font-bold text-slate-600"><?= htmlspecialchars($record['source'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Date</span>
                                <div class="mt-1 break-words font-semibold text-slate-800"><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                                <div class="mt-1 break-words font-semibold text-slate-800"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Service</span>
                                <div class="mt-1 inline-flex max-w-full min-h-8 items-center break-words rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700"><?= htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Checked By</span>
                                <div class="mt-1 break-words font-semibold text-slate-800"><?= htmlspecialchars($record['checked_by_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
