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

if (!empty($cards[1]['value'])) {
    $attendancePercent = min(100, (int) round(((int) ($cards[3]['value'] ?? 0) / max(1, (int) $cards[1]['value'])) * 100));
}
?>

<section class="relative mx-auto w-full max-w-[1280px] overflow-hidden rounded-[34px] border border-white/70 bg-white/58 px-5 py-6 shadow-panel backdrop-blur xl:px-8 xl:py-8" aria-labelledby="dashboard-title">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-r from-emerald-100/70 via-white/10 to-amber-100/60"></div>
    <div class="relative flex flex-col gap-6 border-b border-black/6 pb-7 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0">
            <div class="mb-3 text-[0.74rem] font-extrabold uppercase tracking-[0.18em] text-emerald-800"><?= $isSuperAdmin ? 'Global Command Center' : 'Localized Command Center' ?></div>
            <h1 class="font-display text-[clamp(2.6rem,5vw,4.8rem)] leading-[0.92] tracking-[-0.05em] text-portal-ink" id="dashboard-title"><?= $isSuperAdmin ? 'Overview' : 'Tent Overview' ?></h1>
            <p class="mt-4 max-w-3xl text-[1rem] leading-8 text-portal-muted">
                <?= $isSuperAdmin
                    ? 'Track members, tents, and Sunday attendance across the full KKYF portal.'
                    : 'Monitor member activity, attendance, and follow-up work for your assigned tent.' ?>
            </p>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-[#d7dfd6] bg-white/82 px-5 py-3 text-sm font-bold text-portal-ink no-underline shadow-soft transition hover:bg-white" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="history"></i> Attendance History</a>
            <a class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-3 text-sm font-bold text-white no-underline shadow-soft transition hover:-translate-y-[1px]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Take Attendance</a>
        </div>
    </div>

    <div class="relative mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-[1.25fr_repeat(3,minmax(0,1fr))]">
        <article class="relative overflow-hidden rounded-[28px] border border-emerald-900/10 bg-gradient-to-br from-emerald-600 via-emerald-700 to-[#114a2a] p-6 text-white shadow-panel">
            <div class="mb-7 flex items-start justify-between gap-4">
                <span class="text-[0.72rem] font-extrabold uppercase tracking-[0.18em] text-white/76"><?= htmlspecialchars($primaryCard['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="inline-grid h-11 w-11 place-items-center rounded-2xl bg-white/12"><i data-lucide="<?= htmlspecialchars($primaryCard['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
            </div>
            <strong class="block text-[clamp(2.4rem,4vw,3.4rem)] font-extrabold leading-none tracking-[-0.05em]"><?= number_format((int) $primaryCard['value']) ?></strong>
            <small class="mt-5 inline-flex items-center gap-2 text-sm text-white/78"><i data-lucide="trending-up"></i> Live portal snapshot</small>
        </article>

        <?php foreach ($secondaryCards as $card): ?>
            <article class="rounded-[28px] border border-[#d7dfd6] bg-white/90 p-6 shadow-soft">
                <div class="mb-7 flex items-start justify-between gap-4">
                    <span class="text-[0.72rem] font-extrabold uppercase tracking-[0.18em] text-portal-muted"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="inline-grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-800"><i data-lucide="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                </div>
                <strong class="block text-[clamp(2rem,4vw,2.6rem)] font-extrabold leading-none tracking-[-0.05em] text-portal-ink"><?= number_format((int) $card['value']) ?></strong>
                <small class="mt-5 inline-block text-sm text-portal-muted">Updated from current records</small>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="relative mt-7 grid gap-4 xl:grid-cols-[1.1fr_0.95fr_0.95fr]">
        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft xl:col-span-2" aria-labelledby="attendance-trend-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="attendance-trend-title">Attendance Trends</h2>
                <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-4 text-[0.72rem] font-extrabold uppercase tracking-[0.14em] text-emerald-800">Last 6 Months</span>
            </div>
            <div class="overflow-x-auto pb-1">
                <div class="grid min-w-[520px] min-h-[300px] grid-cols-6 items-end gap-4 rounded-[22px] bg-gradient-to-b from-[#f6f1e8] to-white p-5" aria-label="Attendance trend chart">
                    <?php foreach ($trendBars as $bar): ?>
                        <div class="grid items-end justify-items-center gap-3">
                            <span class="block w-full rounded-t-[16px] rounded-b-[8px] bg-gradient-to-b from-emerald-500 to-emerald-800 shadow-soft" style="height: <?= (int) ($bar['height'] ?? 14) ?>%" title="<?= number_format((int) ($bar['value'] ?? 0)) ?> attendance record(s)"></span>
                            <small class="text-[0.78rem] font-bold text-portal-muted"><?= htmlspecialchars((string) ($bar['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <aside class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="dashboard-alerts-title">
            <div>
                <h2 class="inline-flex items-center gap-2 font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="dashboard-alerts-title"><i data-lucide="triangle-alert"></i> Absentee Alerts</h2>
                <p class="mt-3 text-sm leading-7 text-portal-muted">
                    <?= $isSuperAdmin
                        ? number_format((int) ($absenteeSummary['critical_total'] ?? 0)) . ' critical and ' . number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' open alerts across KKYF.'
                        : number_format((int) ($absenteeSummary['open_total'] ?? 0)) . ' active alert(s) inside your tent follow-up queue.' ?>
                </p>
            </div>

            <div class="mt-5 grid gap-3">
                <?php if ($absenteeAlerts === []): ?>
                    <div class="rounded-3xl border border-dashed border-[#d7dfd6] bg-[#faf7f1] px-5 py-6 text-sm leading-7 text-portal-muted">No absentee alerts are active right now.</div>
                <?php endif; ?>

                <?php foreach ($absenteeAlerts as $alert): ?>
                    <div class="flex items-center justify-between gap-4 rounded-3xl border border-[#d7dfd6] bg-white px-4 py-4">
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($alert['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($alert['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($alert['alert_level'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <span class="inline-flex min-h-9 items-center rounded-full bg-emerald-50 px-3 text-sm font-bold text-emerald-800"><?= (int) $alert['missed_count'] ?> wks</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <a class="mt-5 inline-flex text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/absentees">Open absentee queue</a>
        </aside>

        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="recent-members-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="recent-members-title">Recent Members</h2>
                <a class="text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">View all</a>
            </div>

            <div class="grid gap-3">
                <?php if (($metrics['recent_members'] ?? []) === []): ?>
                    <div class="rounded-3xl border border-dashed border-[#d7dfd6] bg-[#faf7f1] px-5 py-6 text-sm leading-7 text-portal-muted">No recent members yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_members'] ?? []) as $member): ?>
                    <?php
                    $memberParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $memberInitials = strtoupper(substr($memberParts[0] ?? 'M', 0, 1) . substr($memberParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-3xl border border-[#d7dfd6] bg-white px-4 py-4">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($memberInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="recent-attendance-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="recent-attendance-title">Recent Attendance</h2>
                <a class="text-sm font-bold text-emerald-800 no-underline" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">History</a>
            </div>

            <div class="grid gap-3">
                <?php if (($metrics['recent_attendance'] ?? []) === []): ?>
                    <div class="rounded-3xl border border-dashed border-[#d7dfd6] bg-[#faf7f1] px-5 py-6 text-sm leading-7 text-portal-muted">No attendance records yet.</div>
                <?php endif; ?>

                <?php foreach (($metrics['recent_attendance'] ?? []) as $record): ?>
                    <?php
                    $attendanceParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $attendanceInitials = strtoupper(substr($attendanceParts[0] ?? 'M', 0, 1) . substr($attendanceParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-3xl border border-[#d7dfd6] bg-white px-4 py-4">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-[#f3efe6] text-sm font-bold text-portal-ink"><?= htmlspecialchars($attendanceInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-portal-ink"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-portal-muted"><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="upcoming-birthdays-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="upcoming-birthdays-title">Upcoming Birthdays</h2>
                <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-4 text-[0.72rem] font-extrabold uppercase tracking-[0.14em] text-emerald-800">
                    <?= count(array_filter($upcomingBirthdays, static fn (array $birthday): bool => !empty($birthday['is_today_birthday']))) ?> today
                </span>
            </div>

            <div class="grid gap-3">
                <?php if ($upcomingBirthdays === []): ?>
                    <div class="rounded-3xl border border-dashed border-[#d7dfd6] bg-[#faf7f1] px-5 py-6 text-sm leading-7 text-portal-muted">No birthdays are coming up in the next 7 days.</div>
                <?php endif; ?>

                <?php foreach (array_slice($upcomingBirthdays, 0, 4) as $birthday): ?>
                    <?php
                    $birthdayParts = preg_split('/\s+/', trim($birthday['full_name'])) ?: [];
                    $birthdayInitials = strtoupper(substr($birthdayParts[0] ?? 'M', 0, 1) . substr($birthdayParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-3xl border border-[#d7dfd6] bg-white px-4 py-4">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($birthdayInitials, ENT_QUOTES, 'UTF-8') ?></span>
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

        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="upcoming-anniversaries-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="upcoming-anniversaries-title">Upcoming Anniversaries</h2>
                <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-4 text-[0.72rem] font-extrabold uppercase tracking-[0.14em] text-emerald-800">
                    <?= count(array_filter($upcomingAnniversaries, static fn (array $item): bool => !empty($item['is_today_anniversary']))) ?> today
                </span>
            </div>

            <div class="grid gap-3">
                <?php if ($upcomingAnniversaries === []): ?>
                    <div class="rounded-3xl border border-dashed border-[#d7dfd6] bg-[#faf7f1] px-5 py-6 text-sm leading-7 text-portal-muted">No anniversaries are coming up in the next 7 days.</div>
                <?php endif; ?>

                <?php foreach (array_slice($upcomingAnniversaries, 0, 4) as $anniversary): ?>
                    <?php
                    $anniversaryParts = preg_split('/\s+/', trim($anniversary['full_name'])) ?: [];
                    $anniversaryInitials = strtoupper(substr($anniversaryParts[0] ?? 'M', 0, 1) . substr($anniversaryParts[1] ?? '', 0, 1));
                    ?>
                    <div class="flex items-center gap-4 rounded-3xl border border-[#d7dfd6] bg-white px-4 py-4">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-[#f3efe6] text-sm font-bold text-portal-ink"><?= htmlspecialchars($anniversaryInitials, ENT_QUOTES, 'UTF-8') ?></span>
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

        <section class="rounded-[28px] border border-[#d7dfd6] bg-white/88 p-6 shadow-soft" aria-labelledby="capacity-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-display text-[2rem] tracking-[-0.04em] text-portal-ink" id="capacity-title"><?= $isSuperAdmin ? 'Attendance Pulse' : 'Session Summary' ?></h2>
                <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-4 text-[0.72rem] font-extrabold uppercase tracking-[0.14em] text-emerald-800"><?= $attendancePercent ?>%</span>
            </div>
            <div class="h-4 overflow-hidden rounded-full bg-[#dfe7dc]">
                <span class="block h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-800" style="width: <?= (int) $attendancePercent ?>%"></span>
            </div>
            <div class="mt-4 flex items-center justify-between gap-4">
                <span class="text-sm text-portal-muted">Today vs active members</span>
                <strong class="text-base font-bold text-portal-ink"><?= (int) ($cards[3]['value'] ?? 0) ?> checked in</strong>
            </div>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
