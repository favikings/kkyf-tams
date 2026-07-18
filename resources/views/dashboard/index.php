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
$upcomingBirthdays = $upcomingBirthdays ?? [];
$upcomingAnniversaries = $upcomingAnniversaries ?? [];
$trendBars = $metrics['attendance_trend'] ?? [];
$attendancePercent = 0;
$activeMembers = (int) ($cards[1]['value'] ?? 0);
$totalMembers = (int) ($cards[0]['value'] ?? 0);
$activePercent = $totalMembers > 0 ? min(100, (int) round(($activeMembers / $totalMembers) * 100)) : 0;
$monthlyAttendance = (int) ($cards[4]['value'] ?? 0);
$chartLabels = array_map(static fn (array $bar): string => (string) ($bar['label'] ?? ''), $trendBars);
$chartValues = array_map(static fn (array $bar): int => (int) ($bar['value'] ?? 0), $trendBars);

if (!empty($cards[1]['value'])) {
    $attendancePercent = min(100, (int) round(((int) ($cards[3]['value'] ?? 0) / max(1, (int) $cards[1]['value'])) * 100));
}
?>

<section class="mx-auto w-full max-w-[1280px] py-5" aria-labelledby="dashboard-title">
    <div class="flex flex-col gap-4 pb-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h1 class="font-sans text-3xl font-extrabold leading-tight text-portal-ink md:text-4xl" id="dashboard-title">Welcome back, <?= htmlspecialchars($user['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</h1>
            <p class="mt-1 text-sm text-portal-muted">
                <?= $isSuperAdmin
                    ? "Here's what's happening across KKYF Fellowship today."
                    : "Here's what's happening in your tent today." ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-portal-ink no-underline shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="history"></i> History</a>
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white no-underline shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Mark Attendance</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <span class="text-xs font-extrabold uppercase text-slate-500"><?= htmlspecialchars($primaryCard['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="inline-grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="<?= htmlspecialchars($primaryCard['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) $primaryCard['value']) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-emerald-700"><i data-lucide="trending-up"></i> Live portal snapshot</small>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <span class="text-xs font-extrabold uppercase text-slate-500"><?= htmlspecialchars($cards[1]['label'] ?? 'Active Members', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="inline-grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-600"><i data-lucide="<?= htmlspecialchars($cards[1]['icon'] ?? 'user-check', ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= $activePercent ?>%</strong>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <span class="block h-full rounded-full bg-[#013f26]" style="width: <?= $activePercent ?>%"></span>
            </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <span class="text-xs font-extrabold uppercase text-slate-500"><?= htmlspecialchars($cards[2]['label'] ?? 'Tents Managed', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="inline-grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="<?= htmlspecialchars($cards[2]['icon'] ?? 'tent', ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($cards[2]['value'] ?? 0)) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Operational tents</small>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <span class="text-xs font-extrabold uppercase text-slate-500"><?= htmlspecialchars($cards[3]['label'] ?? 'Attendance Today', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="inline-grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="<?= htmlspecialchars($cards[3]['icon'] ?? 'calendar-check', ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($cards[3]['value'] ?? 0)) ?></strong>
            <small class="mt-3 inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Month: <?= number_format($monthlyAttendance) ?></small>
        </article>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-[2fr_0.9fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="attendance-trend-title">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900" id="attendance-trend-title">Attendance Tracker</h2>
                    <p class="mt-1 text-sm text-portal-muted">Visualizing monthly engagement levels for the current year.</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-bold text-slate-500">
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#27ae60]"></span> Attended</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-200"></span> Baseline</span>
                </div>
            </div>
            <div class="h-[280px]">
                <canvas
                    id="dashboardAttendanceChart"
                    aria-label="Attendance trend chart"
                    role="img"
                    data-labels="<?= htmlspecialchars(json_encode($chartLabels, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8') ?>"
                    data-values="<?= htmlspecialchars(json_encode($chartValues, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8') ?>"
                ></canvas>
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="member-stats-title">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900" id="member-stats-title">Member Stats</h2>
                    <p class="mt-1 text-sm text-portal-muted">Today checked in vs active members.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Weekly</span>
            </div>
            <div class="mt-8 grid place-items-center">
                <div class="relative grid h-44 w-44 place-items-center">
                    <svg class="h-44 w-44 -rotate-90" viewBox="0 0 160 160" aria-hidden="true">
                        <circle cx="80" cy="80" r="62" fill="none" stroke="#e5e7eb" stroke-width="18" stroke-linecap="round" />
                        <circle cx="80" cy="80" r="62" fill="none" stroke="#27ae60" stroke-width="18" stroke-linecap="round" stroke-dasharray="<?= (int) round($attendancePercent * 3.9) ?> 390" />
                    </svg>
                    <div class="absolute text-center">
                        <strong class="block text-4xl font-extrabold text-slate-900"><?= number_format((int) ($cards[3]['value'] ?? 0)) ?></strong>
                        <span class="text-xs font-extrabold uppercase text-slate-500">Today checked in</span>
                    </div>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 text-xs font-bold text-slate-600">
                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#27ae60]"></span> Active (<?= number_format($activeMembers) ?>)</span>
                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Total (<?= number_format($totalMembers) ?>)</span>
                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Alerts (<?= number_format((int) ($absenteeSummary['open_total'] ?? 0)) ?>)</span>
                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-300"></span> <?= $attendancePercent ?>% today</span>
            </div>
        </aside>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tents-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900" id="tents-title">Tents Distribution</h2>
                <i class="text-slate-400" data-lucide="circle"></i>
            </div>
            <div class="grid gap-4">
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3 text-sm font-bold text-slate-700">
                        <span><?= $isSuperAdmin ? 'All Tents' : 'Assigned Tent' ?></span>
                        <span><?= number_format((int) ($cards[2]['value'] ?? 0)) ?> managed</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <span class="block h-full rounded-full bg-[#27ae60]" style="width: <?= $isSuperAdmin ? '85' : '72' ?>%"></span>
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3 text-sm font-bold text-slate-700">
                        <span>Active Coverage</span>
                        <span><?= $activePercent ?>%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <span class="block h-full rounded-full bg-[#013f26]" style="width: <?= $activePercent ?>%"></span>
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="dashboard-alerts-title">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="inline-flex items-center gap-2 text-lg font-extrabold text-slate-900" id="dashboard-alerts-title"><i data-lucide="triangle-alert"></i> Pending Alerts & Notifications</h2>
                    <a class="text-xs font-extrabold text-emerald-700 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/absentees">View All</a>
                </div>
                <p class="mt-3 text-sm leading-7 text-portal-muted">
                    <?= $isSuperAdmin
                        ? number_format((int) ($absenteeSummary['critical_total'] ?? 0)) . ' critical and ' . number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' open alerts across KKYF.'
                        : number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' active alert(s) inside your tent follow-up queue.' ?>
                </p>
            </div>

            <div class="mt-5 grid gap-3">
                <?php if ($absenteeAlerts === []): ?>
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-portal-muted">No absentee alerts are active right now.</div>
                <?php endif; ?>

                <?php foreach ($absenteeAlerts as $alert): ?>
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <span class="inline-flex min-h-8 items-center rounded-full bg-red-50 px-3 text-xs font-bold text-red-700"><?= (int) $alert['missed_count'] ?> wks</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="recent-members-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900" id="recent-members-title">Recent Members</h2>
                <a class="text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">View all</a>
            </div>

            <div class="grid gap-3">
                <?php if (($metrics['recent_members'] ?? []) === []): ?>
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-portal-muted">No recent members yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_members'] ?? []) as $member): ?>
                    <?php
                    $memberParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $memberInitials = strtoupper(substr($memberParts[0] ?? 'M', 0, 1) . substr($memberParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($memberInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="recent-attendance-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900" id="recent-attendance-title">Recent Attendance</h2>
                <a class="text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">History</a>
            </div>

            <div class="grid gap-3">
                <?php if (($metrics['recent_attendance'] ?? []) === []): ?>
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-portal-muted">No attendance records yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_attendance'] ?? []) as $record): ?>
                    <?php
                    $attendanceParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $attendanceInitials = strtoupper(substr($attendanceParts[0] ?? 'M', 0, 1) . substr($attendanceParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-100 text-sm font-bold text-portal-ink"><?= htmlspecialchars($attendanceInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="upcoming-birthdays-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900" id="upcoming-birthdays-title">Upcoming Birthdays</h2>
                <span class="inline-flex min-h-7 items-center rounded-full bg-emerald-50 px-3 text-xs font-extrabold uppercase text-emerald-800">
                    <?= count(array_filter($upcomingBirthdays, static fn (array $birthday): bool => !empty($birthday['is_today_birthday']))) ?> today
                </span>
            </div>

            <div class="grid gap-3">
                <?php if ($upcomingBirthdays === []): ?>
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-portal-muted">No birthdays are coming up in the next 7 days.</div>
                <?php endif; ?>

                <?php foreach (array_slice($upcomingBirthdays, 0, 4) as $birthday): ?>
                    <?php
                    $birthdayParts = preg_split('/\s+/', trim($birthday['full_name'])) ?: [];
                    $birthdayInitials = strtoupper(substr($birthdayParts[0] ?? 'M', 0, 1) . substr($birthdayParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($birthdayInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($birthday['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted">
                                <?= htmlspecialchars((string) $birthday['birthday_label'], ENT_QUOTES, 'UTF-8') ?>
                                ·
                                <?= !empty($birthday['is_today_birthday']) ? 'Today' : 'In ' . (int) $birthday['days_until_birthday'] . ' day' . ((int) $birthday['days_until_birthday'] === 1 ? '' : 's') ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="mt-5 inline-flex text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/birthdays">View all birthdays</a>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="upcoming-anniversaries-title">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-slate-900" id="upcoming-anniversaries-title">Upcoming Anniversaries</h2>
                <span class="inline-flex min-h-7 items-center rounded-full bg-emerald-50 px-3 text-xs font-extrabold uppercase text-emerald-800">
                    <?= count(array_filter($upcomingAnniversaries, static fn (array $item): bool => !empty($item['is_today_anniversary']))) ?> today
                </span>
            </div>

            <div class="grid gap-3">
                <?php if ($upcomingAnniversaries === []): ?>
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-portal-muted">No anniversaries are coming up in the next 7 days.</div>
                <?php endif; ?>

                <?php foreach (array_slice($upcomingAnniversaries, 0, 4) as $anniversary): ?>
                    <?php
                    $anniversaryParts = preg_split('/\s+/', trim($anniversary['full_name'])) ?: [];
                    $anniversaryInitials = strtoupper(substr($anniversaryParts[0] ?? 'M', 0, 1) . substr($anniversaryParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-100 text-sm font-bold text-portal-ink"><?= htmlspecialchars($anniversaryInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($anniversary['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted">
                                <?= htmlspecialchars((string) $anniversary['anniversary_label'], ENT_QUOTES, 'UTF-8') ?>
                                ·
                                <?= !empty($anniversary['is_today_anniversary']) ? 'Today' : 'In ' . (int) $anniversary['days_until_anniversary'] . ' day' . ((int) $anniversary['days_until_anniversary'] === 1 ? '' : 's') ?>
                                ·
                                <?= (int) $anniversary['celebrating_years'] ?> yr<?= (int) $anniversary['celebrating_years'] === 1 ? '' : 's' ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="mt-5 inline-flex text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/anniversaries">View all anniversaries</a>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
