<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$isSuperAdmin = ($user['role'] ?? null) === 'Super Admin';
$checkedInTotal = (int) ($summary['total'] ?? 0);
$attendanceDate = (string) ($summary['attendance_date'] ?? '');
$assignedTent = $assignedTent ?? null;
$checkedInRecords = $checkedInRecords ?? [];
$checkedInByTent = $checkedInByTent ?? [];
$tentOverview = $tentOverview ?? [];
$visibleCount = count($members);
$checkedVisible = count(array_filter($members, static fn (array $member): bool => !empty($member['attendance_id'])));
$pendingVisible = max(0, $visibleCount - $checkedVisible);
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="attendance-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Sunday Attendance</div>
            <h1 id="attendance-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                <?= $isSuperAdmin ? 'Attendance Overview' : 'Tent Check-in' ?>
            </h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">
                <?= $isSuperAdmin
                    ? 'Review each active tent for Sunday, see who has been marked present, and monitor coverage across the fellowship.'
                    : 'Quickly check in your members for this Sunday and keep track of who has already been marked present.' ?>
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200">
                    <i data-lucide="calendar-days"></i>
                    <?= htmlspecialchars($attendanceDate, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700 ring-1 ring-emerald-100">
                    <i data-lucide="badge-check"></i>
                    Sunday Service
                </span>
                <?php if (!$isSuperAdmin && $assignedTent !== null): ?>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200">
                        <i data-lucide="map-pin"></i>
                        <?= htmlspecialchars((string) ($assignedTent['name'] ?? 'Assigned Tent'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <a class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">
            <i data-lucide="history"></i>
            View History
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($isSuperAdmin): ?>
        <?php $activeTentCount = count($tentOverview); ?>
        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Attendance Date</span>
                <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= htmlspecialchars($attendanceDate, ENT_QUOTES, 'UTF-8') ?></strong>
                <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Current Sunday service</small>
            </article>
            <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Total Checked In</span>
                <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($checkedInTotal) ?></strong>
                <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Across all active tents</small>
            </article>
            <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Active Tents</span>
                <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($activeTentCount) ?></strong>
                <small class="mt-3 inline-block text-sm font-semibold text-slate-500">Visible in today&apos;s overview</small>
            </article>
        </div>

        <section class="mt-5 rounded-[24px] border border-slate-200 bg-white shadow-sm" aria-labelledby="tent-overview-title">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="tent-overview-title" class="text-xl font-extrabold text-slate-900">Tent Attendance Overview</h2>
                <p class="mt-1 text-sm text-slate-500">Each tent shows Sunday attendance count and the members already checked in.</p>
            </div>

            <div class="grid gap-5 p-5 lg:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($tentOverview as $tent): ?>
                    <?php
                    $tentId = (int) ($tent['id'] ?? 0);
                    $memberCount = (int) ($tent['member_count'] ?? 0);
                    $checkedInCount = (int) ($tent['checked_in_count'] ?? 0);
                    $presentMembers = [];
                    foreach ($checkedInByTent as $group) {
                        if ((int) ($group['tent_id'] ?? 0) === $tentId) {
                            $presentMembers = (array) ($group['members'] ?? []);
                            break;
                        }
                    }
                    $coverage = $memberCount > 0 ? min(100, (int) round(($checkedInCount / $memberCount) * 100)) : 0;
                    ?>
                    <article class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                        <div class="h-2 w-full" style="background: <?= htmlspecialchars((string) ($tent['color'] ?: '#00bd06'), ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="truncate text-2xl font-extrabold text-slate-900"><?= htmlspecialchars((string) ($tent['name'] ?? 'Tent'), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="mt-1 text-sm text-slate-500"><?= $checkedInCount > 0 ? number_format($checkedInCount) . ' checked in this Sunday' : 'No check-ins yet this Sunday' ?></p>
                                </div>
                                <span class="inline-flex min-h-9 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700"><?= $coverage ?>%</span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <article class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Checked In</span>
                                    <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= number_format($checkedInCount) ?></strong>
                                </article>
                                <article class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Active Members</span>
                                    <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= number_format($memberCount) ?></strong>
                                </article>
                            </div>

                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm text-slate-500">
                                    <span>Sunday coverage</span>
                                    <strong class="font-bold text-slate-900"><?= number_format($checkedInCount) ?> / <?= number_format($memberCount) ?></strong>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <span class="block h-full rounded-full bg-[#013f26]" style="width: <?= $coverage ?>%"></span>
                                </div>
                            </div>

                            <div class="mt-5 rounded-[22px] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-extrabold uppercase tracking-[0.12em] text-slate-500">Checked-in Members</h4>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= count($presentMembers) ?></span>
                                </div>
                                <?php if ($presentMembers === []): ?>
                                    <p class="mt-3 text-sm text-slate-500">No members have been checked in for this tent yet.</p>
                                <?php else: ?>
                                    <details class="mt-3 rounded-xl border border-slate-200 bg-white group">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-bold text-emerald-700">
                                            <span class="group-open:hidden">View all <?= count($presentMembers) ?> checked-in members</span>
                                            <span class="hidden group-open:inline">Collapse checked-in members</span>
                                            <i data-lucide="chevron-down" class="transition duration-200 group-open:rotate-180"></i>
                                        </summary>
                                        <div class="grid gap-2 border-t border-slate-200 px-3 py-3">
                                            <?php foreach ($presentMembers as $record): ?>
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                                    <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($record['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <small class="mt-1 block text-sm text-slate-500"><?= htmlspecialchars((string) (($record['phone'] ?? '') !== '' ? $record['phone'] : 'No phone'), ENT_QUOTES, 'UTF-8') ?><?php if (!empty($record['checked_by_name'])): ?> · by <?= htmlspecialchars((string) $record['checked_by_name'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.9fr)]">
            <div class="grid gap-5">
                <section class="overflow-hidden rounded-[28px] border border-white/70 bg-[linear-gradient(140deg,rgba(245,251,246,0.96),rgba(255,255,255,0.98))] shadow-[0_18px_55px_rgba(15,23,42,0.08)]" aria-labelledby="tent-checkin-title">
                    <div class="grid gap-5 p-5 sm:p-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(260px,0.85fr)]">
                        <div class="min-w-0">
                            <div class="flex items-start gap-3 rounded-[22px] bg-emerald-50 px-4 py-4">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-emerald-700 shadow-sm">
                                    <i data-lucide="clipboard-check"></i>
                                </span>
                                <div>
                                    <h2 id="tent-checkin-title" class="text-lg font-extrabold text-slate-900">Easy member check-in</h2>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Search your tent members, mark them present, and keep Sunday attendance moving without extra clutter.</p>
                                </div>
                            </div>

                            <form class="mt-5 grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_auto]" method="GET" action="attendance">
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Search Members</span>
                                    <div class="relative">
                                        <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" data-lucide="search"></i>
                                        <input class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 pl-12 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-500" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search members by name or phone">
                                    </div>
                                </label>
                                <div class="flex items-end">
                                    <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                                        <i data-lucide="search"></i>
                                        Search
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-3 self-start">
                            <article class="rounded-[24px] border border-white/80 bg-white/90 px-5 py-5 shadow-sm">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Assigned Tent</div>
                                <strong class="mt-2 block text-2xl font-extrabold text-slate-900"><?= htmlspecialchars((string) ($assignedTent['name'] ?? 'My Tent'), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small class="mt-2 block text-sm text-slate-600">Your Sunday attendance workspace</small>
                            </article>
                            <article class="rounded-[24px] border border-slate-200 bg-[#102017] px-5 py-5 text-white shadow-sm">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-white/65">Checked In Today</div>
                                <strong class="mt-2 block text-4xl font-extrabold text-white"><?= number_format($checkedInTotal) ?></strong>
                                <small class="mt-2 block text-sm text-white/70">Total members already marked present this Sunday.</small>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="rounded-[24px] border border-slate-200 bg-white shadow-sm" aria-labelledby="roster-title">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 id="roster-title" class="text-xl font-extrabold text-slate-900">Tent Member Roster</h2>
                            <p class="mt-1 text-sm text-slate-500">Mark attendance directly from your tent member list.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700"><?= number_format($checkedVisible) ?> checked</span>
                            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= number_format($pendingVisible) ?> pending</span>
                        </div>
                    </div>

                    <?php if ($members === []): ?>
                        <div class="px-5 py-10 text-center text-sm text-slate-500">No active members match this search in your tent.</div>
                    <?php else: ?>
                        <div class="hidden overflow-x-auto xl:block">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50/80">
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Phone</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</th>
                                        <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <?php
                                        $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                                        $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                        ?>
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="px-5 py-4 align-top">
                                                <div class="flex items-center gap-3">
                                                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <div class="min-w-0">
                                                        <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars((string) $member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <small class="text-sm text-slate-500">KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars((string) ($member['phone'] ?: 'No phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-5 py-4 align-top">
                                                <?php if (!empty($member['attendance_id'])): ?>
                                                    <span class="inline-flex min-h-9 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700">Checked In</span>
                                                <?php else: ?>
                                                    <span class="inline-flex min-h-9 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-4 align-top">
                                                <?php if (!empty($member['attendance_id'])): ?>
                                                    <span class="inline-flex min-h-10 items-center gap-2 rounded-full bg-emerald-50 px-4 text-sm font-bold text-emerald-700">
                                                        <i data-lucide="check-circle"></i>
                                                        Checked In
                                                    </span>
                                                <?php else: ?>
                                                    <form method="POST" action="attendance/check-in">
                                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                                        <button class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">Check In</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-2 p-3 xl:hidden">
                            <?php foreach ($members as $member): ?>
                                <?php
                                $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                                $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                ?>
                                <article class="rounded-xl border border-slate-200 bg-white p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                            <div class="min-w-0">
                                                <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars((string) $member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small class="text-sm text-slate-500"><?= htmlspecialchars((string) ($member['phone'] ?: 'No phone'), ENT_QUOTES, 'UTF-8') ?></small>
                                            </div>
                                        </div>
                                        <?php if (!empty($member['attendance_id'])): ?>
                                            <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700">Checked</span>
                                        <?php else: ?>
                                            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3">
                                        <?php if (!empty($member['attendance_id'])): ?>
                                            <span class="inline-flex min-h-10 items-center gap-2 rounded-full bg-emerald-50 px-4 text-sm font-bold text-emerald-700">
                                                <i data-lucide="check-circle"></i>
                                                Checked In
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" action="attendance/check-in">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                                <button class="inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">Check In</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="grid gap-5 self-start xl:sticky xl:top-24">
                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tent-summary-title">
                    <div class="mb-4">
                        <h2 id="tent-summary-title" class="text-xl font-extrabold text-slate-900">Tent Summary</h2>
                        <p class="mt-1 text-sm text-slate-500">Today&apos;s attendance picture for your tent.</p>
                    </div>
                    <div class="grid gap-3">
                        <article class="rounded-2xl bg-emerald-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700">Checked In</div>
                            <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= number_format($checkedVisible) ?></strong>
                            <small class="mt-2 block text-sm text-slate-600">Members already marked present in your tent.</small>
                        </article>
                        <article class="rounded-2xl bg-slate-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Pending</div>
                            <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= number_format($pendingVisible) ?></strong>
                            <small class="mt-2 block text-sm text-slate-600">Members in your current search view not yet checked in.</small>
                        </article>
                    </div>
                </section>

                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="checked-in-list-title">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 id="checked-in-list-title" class="text-xl font-extrabold text-slate-900">Checked-in Members</h2>
                            <p class="mt-1 text-sm text-slate-500">Everyone already marked present this Sunday.</p>
                        </div>
                        <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= count($checkedInRecords) ?></span>
                    </div>

                    <div class="grid gap-3">
                        <?php if ($checkedInRecords === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No members have been checked in yet for this Sunday.</div>
                        <?php else: ?>
                            <?php foreach ($checkedInRecords as $record): ?>
                                <?php
                                $nameParts = preg_split('/\s+/', trim((string) ($record['full_name'] ?? ''))) ?: [];
                                $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                ?>
                                <article class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-white text-sm font-bold text-slate-700 shadow-sm"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <div class="min-w-0">
                                        <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($record['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small class="text-sm text-slate-500"><?= htmlspecialchars((string) (($record['phone'] ?? '') !== '' ? $record['phone'] : 'No phone'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
