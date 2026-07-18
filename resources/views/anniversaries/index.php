<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$todayTotal = (int) ($summary['today_total'] ?? 0);
$nextSevenTotal = (int) ($summary['next_7_days_total'] ?? 0);
$nextThirtyTotal = (int) ($summary['next_30_days_total'] ?? 0);
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="anniversary-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Milestones</div>
            <h1 id="anniversary-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Upcoming Anniversaries</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">See who is marking another year in KKYF and jump straight into a thoughtful celebration message while the moment is still fresh.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/birthdays">
                <i data-lucide="cake"></i>
                Birthdays
            </a>
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                <i data-lucide="layout-dashboard"></i>
                Dashboard
            </a>
        </div>
    </div>

    <?php if ($todayTotal > 0): ?>
        <div class="rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            <div class="flex items-center gap-3">
                <span class="inline-grid h-10 w-10 place-items-center rounded-full bg-white text-amber-700 shadow-sm"><i data-lucide="party-popper"></i></span>
                <strong><?= $todayTotal === 1 ? '1 member is marking an anniversary today.' : number_format($todayTotal) . ' members are marking anniversaries today.' ?></strong>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-5 grid gap-4 md:grid-cols-3">
        <article class="rounded-[24px] border <?= $todayTotal > 0 ? 'border-amber-200 bg-gradient-to-br from-amber-50 to-white' : 'border-slate-200 bg-white' ?> p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Today</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($todayTotal) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Anniversaries happening today</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Next 7 Days</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($nextSevenTotal) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Immediate milestone window</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Next 30 Days</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($nextThirtyTotal) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Extended planning view</small>
        </article>
    </div>

    <form class="mt-5 grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(220px,0.9fr)_minmax(220px,1fr)_auto]" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/anniversaries">
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Anniversary Window</span>
            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="days">
                <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Next 7 days</option>
                <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Next 30 days</option>
            </select>
        </label>
        <div class="rounded-[20px] bg-slate-50 px-4 py-3">
            <strong class="block text-sm font-bold text-slate-800"><?= $days === 7 ? 'Short window' : 'Extended window' ?></strong>
            <small class="mt-1 block text-sm leading-6 text-slate-500"><?= $days === 7 ? 'Best for quick recognition.' : 'Useful for planning monthly milestone outreach.' ?></small>
        </div>
        <div class="flex items-end">
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                <i data-lucide="calendar-range"></i>
                Update View
            </button>
        </div>
    </form>

    <section class="mt-5 rounded-[24px] border border-slate-200 bg-white shadow-sm" aria-labelledby="anniversary-results-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 id="anniversary-results-title" class="text-xl font-extrabold text-slate-900">Anniversary Reminders</h2>
                <p class="mt-1 text-sm text-slate-500">Only active members with join dates inside your visible scope are shown.</p>
            </div>
            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= count($anniversaries) ?> reminder<?= count($anniversaries) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($anniversaries === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">
                No active anniversaries fall inside this window.
                <a class="ml-1 font-bold text-emerald-700 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">Review join dates</a>
            </div>
        <?php else: ?>
            <div class="hidden overflow-x-auto xl:block">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Anniversary</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Milestone</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anniversaries as $member): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            $message = 'Congratulations, ' . trim((string) $member['full_name']) . '! Happy ' . (int) $member['celebrating_years'] . ' year anniversary in KKYF. We celebrate your journey and growth with us.';
                            ?>
                            <tr class="border-b border-slate-100 last:border-b-0 <?= !empty($member['is_today_anniversary']) ? 'bg-amber-50/60' : '' ?>">
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-4">
                                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="min-w-0">
                                            <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="text-sm text-slate-500">KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <strong class="block text-sm font-bold text-slate-800"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <strong class="block text-sm font-bold text-slate-800"><?= htmlspecialchars((string) $member['anniversary_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500">Joined <?= htmlspecialchars((string) $member['join_date'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= !empty($member['is_today_anniversary']) ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= !empty($member['is_today_anniversary']) ? 'Today' : 'In ' . (int) $member['days_until_anniversary'] . ' day' . ((int) $member['days_until_anniversary'] === 1 ? '' : 's') ?>
                                    </span>
                                    <small class="mt-2 block text-sm text-slate-500"><?= (int) $member['celebrating_years'] ?> year<?= (int) $member['celebrating_years'] === 1 ? '' : 's' ?> in KKYF</small>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if (!empty($member['phone'])): ?>
                                            <a class="inline-grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=member&amp;member_id=<?= (int) $member['id'] ?>&amp;message=<?= urlencode($message) ?>" aria-label="Send anniversary SMS to <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="messages-square"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-slate-100 text-slate-300" title="No phone saved for anniversary SMS" aria-hidden="true">
                                                <i data-lucide="phone-off"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 p-3 xl:hidden">
                <?php foreach ($anniversaries as $member): ?>
                    <?php
                    $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                    $message = 'Congratulations, ' . trim((string) $member['full_name']) . '! Happy ' . (int) $member['celebrating_years'] . ' year anniversary in KKYF. We celebrate your journey and growth with us.';
                    ?>
                    <article class="rounded-[20px] border <?= !empty($member['is_today_anniversary']) ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200 bg-white' ?> p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= !empty($member['is_today_anniversary']) ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?>">
                                <?= !empty($member['is_today_anniversary']) ? 'Today' : 'In ' . (int) $member['days_until_anniversary'] . ' day' . ((int) $member['days_until_anniversary'] === 1 ? '' : 's') ?>
                            </span>
                        </div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Anniversary</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars((string) $member['anniversary_label'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Milestone</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= (int) $member['celebrating_years'] ?> year<?= (int) $member['celebrating_years'] === 1 ? '' : 's' ?></div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $member['id'] ?>">
                                <i data-lucide="eye"></i>
                                View
                            </a>
                            <?php if (!empty($member['phone'])): ?>
                                <a class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-[#013f26] px-4 text-sm font-bold text-white no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=member&amp;member_id=<?= (int) $member['id'] ?>&amp;message=<?= urlencode($message) ?>">
                                    <i data-lucide="messages-square"></i>
                                    SMS
                                </a>
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
