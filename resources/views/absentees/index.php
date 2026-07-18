<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$levelClass = static function (string $level): string {
    return match ($level) {
        'Early Warning' => 'bg-amber-50 text-amber-700',
        'Follow-Up Required' => 'bg-sky-50 text-sky-700',
        'Critical' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
};
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="absentee-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <h1 id="absentee-title" class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Absentee Alerts</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">Identify members missing consecutive Sundays and resolve follow-up risks at the right time.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">
                <i data-lucide="calendar-range"></i>
                Attendance History
            </a>
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">
                <i data-lucide="users"></i>
                Member Directory
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Open Alerts</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['open_total'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Members currently requiring follow-up</small>
        </article>
        <article class="rounded-[24px] border border-rose-100 bg-rose-50 p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-rose-700">Critical</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($summary['critical_total'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-rose-700">4 or more missed Sundays</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Follow-Up Queue</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) (($summary['follow_up_total'] ?? 0) + ($summary['early_warning_total'] ?? 0))) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Early warning and follow-up required</small>
        </article>
    </div>

    <form class="mt-5 grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(180px,0.85fr)_minmax(180px,0.8fr)_minmax(180px,0.75fr)_auto]" method="GET" action="absentees">
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label class="grid gap-2">
                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id">
                    <option value="">All Tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Alert Level</span>
            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="level">
                <option value="">All Levels</option>
                <?php foreach (['Early Warning', 'Follow-Up Required', 'Critical'] as $level): ?>
                    <option value="<?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedLevel === $level ? 'selected' : '' ?>>
                        <?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">State</span>
            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="resolved">
                <option value="open" <?= $selectedResolved === 'open' ? 'selected' : '' ?>>Open Alerts</option>
                <option value="resolved" <?= $selectedResolved === 'resolved' ? 'selected' : '' ?>>Resolved Alerts</option>
            </select>
        </label>
        <div class="flex items-end">
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                <i data-lucide="search"></i>
                Filter
            </button>
        </div>
    </form>

    <section class="mt-5 rounded-[24px] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Current Absentee Queue</h2>
                <p class="mt-1 text-sm text-slate-500">Members flagged by missed-Sunday thresholds and follow-up state.</p>
            </div>
            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= $selectedResolved === 'resolved' ? 'Resolved' : 'Open' ?></span>
        </div>

        <?php if ($alerts === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">No absentee alerts match this view right now.</div>
        <?php else: ?>
            <div class="hidden overflow-x-auto xl:block">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Alert Level</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Missed Sundays</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="px-5 py-4 align-top">
                                    <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars($alert['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $levelClass((string) $alert['alert_level']) ?>">
                                        <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= (int) $alert['missed_count'] ?> wks</td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= (int) $alert['resolved'] === 1 ? 'Resolved' : 'Open' ?></span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/show?id=<?= (int) $alert['member_id'] ?>" aria-label="Open member profile">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if ((int) $alert['resolved'] === 0): ?>
                                            <form method="POST" action="absentees/resolve">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                                <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" type="submit" aria-label="Resolve alert">
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

            <div class="grid gap-2 p-3 xl:hidden">
                <?php foreach ($alerts as $alert): ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small class="text-sm text-slate-500"><?= htmlspecialchars($alert['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $levelClass((string) $alert['alert_level']) ?>">
                                <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Missed Sundays</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= (int) $alert['missed_count'] ?> wks</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5 sm:col-span-2">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</span>
                                <div class="mt-1 inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= (int) $alert['resolved'] === 1 ? 'Resolved' : 'Open' ?></div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/show?id=<?= (int) $alert['member_id'] ?>" aria-label="Open member profile">
                                <i data-lucide="eye"></i>
                            </a>
                            <?php if ((int) $alert['resolved'] === 0): ?>
                                <form method="POST" action="absentees/resolve">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $alert['id'] ?>">
                                    <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" type="submit" aria-label="Resolve alert">
                                        <i data-lucide="check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
