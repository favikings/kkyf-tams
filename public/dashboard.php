<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$tentId = scopedTentId();
$isSuper = isSuperAdmin();
$currentSunday = currentSunday();

$user = currentUser();
$nameParts = preg_split('/\s+/', trim((string) ($user['name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$firstName = $nameParts[0] ?? '';

if ($isSuper) {
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM members WHERE status = 'active'");
    $stmt->execute();
    $totalMembers = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM attendance WHERE sunday_date = ?');
    $stmt->execute([$currentSunday]);
    $todayCheckins = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM members WHERE first_seen_sunday = ?');
    $stmt->execute([$currentSunday]);
    $firstTimersToday = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM users WHERE role = 'tent_admin' AND status = 'pending'");
    $stmt->execute();
    $pendingApprovals = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare(
        "SELECT t.id, t.name, t.color_hex, t.is_active,
                (SELECT COUNT(*) FROM members m WHERE m.tent_id = t.id AND m.status = 'active') AS member_count,
                (SELECT COUNT(*) FROM attendance a WHERE a.tent_id = t.id AND a.sunday_date = ?) AS checkin_count
         FROM tents t
         ORDER BY t.is_active DESC, t.name ASC"
    );
    $stmt->execute([$currentSunday]);
    $tents = $stmt->fetchAll();

    $activeTents = count(array_filter($tents, static fn (array $t): bool => (int) $t['is_active'] === 1));

    $birthdayStmt = db()->prepare(
        "SELECT full_name, birth_month, birth_day
         FROM members
         WHERE status = 'active' AND birth_month IS NOT NULL AND birth_day IS NOT NULL
         ORDER BY birth_month ASC, birth_day ASC"
    );
    $birthdayStmt->execute();
    $birthdayRows = $birthdayStmt->fetchAll();
} else {
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM members WHERE tent_id = ? AND status = 'active'");
    $stmt->execute([$tentId]);
    $totalMembers = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM attendance WHERE tent_id = ? AND sunday_date = ?');
    $stmt->execute([$tentId, $currentSunday]);
    $todayCheckins = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare('SELECT COUNT(*) AS c FROM members WHERE tent_id = ? AND first_seen_sunday = ?');
    $stmt->execute([$tentId, $currentSunday]);
    $firstTimersToday = (int) $stmt->fetch()['c'];

    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM first_timer_followups WHERE tent_id = ? AND status = 'pending'");
    $stmt->execute([$tentId]);
    $pendingFollowups = (int) $stmt->fetch()['c'];

    $birthdayStmt = db()->prepare(
        "SELECT full_name, birth_month, birth_day
         FROM members
         WHERE tent_id = ? AND status = 'active' AND birth_month IS NOT NULL AND birth_day IS NOT NULL
         ORDER BY birth_month ASC, birth_day ASC"
    );
    $birthdayStmt->execute([$tentId]);
    $birthdayRows = $birthdayStmt->fetchAll();
}

$attendancePercent = $totalMembers > 0 ? min(100, (int) round($todayCheckins / $totalMembers * 100)) : 0;

$birthdays = [];
$today = new DateTimeImmutable('today');
$thisYear = (int) $today->format('Y');

foreach ($birthdayRows as $row) {
    $month = (int) $row['birth_month'];
    $day = (int) $row['birth_day'];
    $lastDay = (int) (new DateTime($thisYear . '-' . $month . '-01'))->modify('last day of this month')->format('j');
    $day = min($day, $lastDay);
    $candidate = new DateTimeImmutable($thisYear . '-' . $month . '-' . $day);
    $days = (int) $today->diff($candidate, false)->format('%r%a');

    if ($days < 0) {
        $nextYear = $thisYear + 1;
        $nextLastDay = (int) (new DateTime($nextYear . '-' . $month . '-01'))->modify('last day of this month')->format('j');
        $nextDay = min($day, $nextLastDay);
        $candidate = new DateTimeImmutable($nextYear . '-' . $month . '-' . $nextDay);
        $days = (int) $today->diff($candidate)->format('%a');
    }

    if ($days <= 7) {
        $birthdays[] = [
            'full_name' => (string) $row['full_name'],
            'days_until' => $days,
            'month_label' => $candidate->format('M'),
            'day_label' => $candidate->format('j'),
        ];
    }
}

usort($birthdays, static fn (array $a, array $b): int => $a['days_until'] <=> $b['days_until']);
$birthdaysThisWeek = array_slice($birthdays, 0, 8);

if ($isSuper) {
    $recentStmt = db()->prepare(
        "SELECT a.checked_in_at, m.id AS member_id, m.full_name, m.is_first_timer, m.first_seen_sunday
         FROM attendance a
         JOIN members m ON m.id = a.member_id
         WHERE a.sunday_date = ?
         ORDER BY a.checked_in_at DESC
         LIMIT 5"
    );
    $recentStmt->execute([$currentSunday]);
    $recentCheckins = $recentStmt->fetchAll();
} else {
    $recentStmt = db()->prepare(
        "SELECT a.checked_in_at, m.id AS member_id, m.full_name, m.is_first_timer, m.first_seen_sunday
         FROM attendance a
         JOIN members m ON m.id = a.member_id
         WHERE a.sunday_date = ? AND a.tent_id = ?
         ORDER BY a.checked_in_at DESC
         LIMIT 5"
    );
    $recentStmt->execute([$currentSunday, $tentId]);
    $recentCheckins = $recentStmt->fetchAll();
}

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-5xl">

  <section class="relative mb-6 overflow-hidden rounded-xl shadow-card md:mb-8">
    <img src="assets/images/dashboard-hero.jpg" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/80 to-transparent"></div>
    <div class="relative flex min-h-[220px] flex-col justify-end gap-3 p-6">
      <p class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary/80">
        Welcome back, <?= e($firstName) ?>
      </p>
      <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-primary md:text-[32px] md:leading-10">
        Ready for Service?
      </h1>
      <a href="checkin.php" class="<?= $primaryButton ?> w-fit">
        <i data-lucide="check-circle" class="h-5 w-5"></i>
        Start Sunday Check-in
      </a>
    </div>
  </section>

  <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="bg-surface-lowest rounded-xl p-5 shadow-card">
      <i data-lucide="clipboard-check" class="h-5 w-5 text-primary"></i>
      <div class="mt-3 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Today's Attendance</div>
      <div class="mt-1 font-display text-[48px] leading-[56px] font-bold tracking-[-0.02em] text-on-surface"><?= number_format($todayCheckins) ?> / <?= number_format($totalMembers) ?></div>
      <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-surface-container-high">
        <div class="h-full rounded-full bg-primary" style="width: <?= $attendancePercent ?>%"></div>
      </div>
    </div>

    <div class="bg-surface-lowest rounded-xl p-5 shadow-card">
      <i data-lucide="user-plus" class="h-5 w-5 text-primary"></i>
      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">First-Timers</span>
        <?php if ($firstTimersToday > 0): ?>
          <span class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">NEW</span>
        <?php endif; ?>
      </div>
      <div class="mt-1 font-display text-[48px] leading-[56px] font-bold tracking-[-0.02em] text-on-surface"><?= number_format($firstTimersToday) ?></div>
    </div>

    <?php if ($isSuper): ?>
      <div class="bg-surface-lowest rounded-xl p-5 shadow-card">
        <i data-lucide="tent" class="h-5 w-5 text-primary"></i>
        <div class="mt-3 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Active Tents</div>
        <div class="mt-1 font-display text-[48px] leading-[56px] font-bold tracking-[-0.02em] text-on-surface"><?= number_format($activeTents) ?></div>
        <div class="mt-2 text-[14px] leading-5 text-on-surface-variant">online</div>
      </div>
    <?php else: ?>
      <div class="bg-surface-lowest rounded-xl p-5 shadow-card">
        <i data-lucide="phone" class="h-5 w-5 text-primary"></i>
        <div class="mt-3 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Pending Follow-Ups</div>
        <div class="mt-1 font-display text-[48px] leading-[56px] font-bold tracking-[-0.02em] text-on-surface"><?= number_format($pendingFollowups) ?></div>
        <div class="mt-2 text-[14px] leading-5 text-on-surface-variant">need contact</div>
      </div>
    <?php endif; ?>
  </section>

  <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-5">
    <div class="bg-surface-lowest rounded-lg p-4 shadow-card md:col-span-3">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <i data-lucide="users" class="h-5 w-5 text-primary"></i>
          <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Recent Check-ins</h2>
        </div>
        <a href="attendance-history.php" class="inline-flex items-center gap-1 font-display text-[14px] leading-5 font-semibold tracking-[0.02em] text-primary">
          View All <i data-lucide="chevron-right" class="h-4 w-4"></i>
        </a>
      </div>

      <?php if ($recentCheckins === []): ?>
        <div class="py-12 text-center">
          <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
          <p class="text-[16px] leading-6 text-on-surface-variant">No check-ins yet today.</p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-outline-variant">
          <?php foreach ($recentCheckins as $i => $row):
              $parts = preg_split('/\s+/', trim((string) $row['full_name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
              $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
              $avatarBg = $i % 2 === 0 ? 'bg-primary-container text-on-primary-container' : 'bg-tertiary-container text-on-tertiary-container';
              $isNew = (int) $row['is_first_timer'] === 1 && $row['first_seen_sunday'] === $currentSunday;
              $memberCode = 'ID: KKYF-' . str_pad((string) $row['member_id'], 6, '0', STR_PAD_LEFT);
              $time = date('g:i A', strtotime((string) $row['checked_in_at']));
          ?>
            <div class="flex items-center gap-3 py-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full font-display text-[14px] leading-5 font-semibold <?= $avatarBg ?>"><?= e($initials) ?></div>
              <div class="min-w-0 flex-1">
                <div class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($row['full_name']) ?></div>
                <div class="text-[14px] leading-5 text-on-surface-variant"><?= e($memberCode) ?></div>
              </div>
              <div class="shrink-0 text-right">
                <div class="text-[14px] leading-5 text-on-surface-variant"><?= e($time) ?></div>
                <?php if ($isNew): ?>
                  <span class="mt-1 inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">Registered</span>
                <?php else: ?>
                  <span class="mt-1 inline-flex items-center rounded-full bg-primary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary-container">Checked In</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="bg-surface-lowest rounded-lg p-4 shadow-card md:col-span-2">
      <div class="mb-4 flex items-center gap-2">
        <i data-lucide="cake" class="h-5 w-5 text-primary"></i>
        <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Birthdays This Week</h2>
      </div>

      <?php if ($birthdaysThisWeek === []): ?>
        <div class="py-12 text-center">
          <i data-lucide="cake" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
          <p class="text-[16px] leading-6 text-on-surface-variant">No birthdays in the next 7 days.</p>
        </div>
      <?php else: ?>
        <div class="divide-y divide-outline-variant">
          <?php foreach ($birthdaysThisWeek as $b): ?>
            <div class="flex items-center gap-3 py-3">
              <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-md bg-surface-container-low">
                <span class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant"><?= e($b['month_label']) ?></span>
                <span class="font-display text-[20px] leading-7 font-semibold text-on-surface"><?= (int) $b['day_label'] ?></span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($b['full_name']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <button type="button" disabled
        class="<?= $secondaryButton ?> mt-4 w-full opacity-50 pointer-events-none">
        Send Greetings
      </button>
    </div>
  </section>

  <?php if ($isSuper): ?>
    <section class="mt-10">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Tents</h2>
        <?php if ($pendingApprovals > 0): ?>
          <a href="tent-admins.php"
             class="inline-flex items-center gap-2 rounded-full bg-error-container px-3 py-1.5 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-error-container hover:bg-surface-high">
            <?= number_format($pendingApprovals) ?> pending approval<?= $pendingApprovals === 1 ? '' : 's' ?>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($tents === []): ?>
        <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
          <i data-lucide="tent" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
          <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No tents yet. Create the first one to start adding members.</p>
          <a href="tents.php" class="<?= $primaryButton ?>">Add Tent</a>
        </div>
      <?php else: ?>
        <div class="grid gap-4 md:grid-cols-2">
          <?php foreach ($tents as $tent):
              $tentColor = (string) ($tent['color_hex'] ?? '');
              if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tentColor)) {
                  $tentColor = '#2E86C1';
              }
          ?>
            <a href="members.php?tent_id=<?= (int) $tent['id'] ?>" class="bg-surface-lowest rounded-lg p-4 shadow-card flex items-center gap-3 hover:bg-surface-high">
              <div class="h-11 w-11 shrink-0 rounded-lg" style="background-color: <?= e($tentColor) ?>"></div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($tent['name']) ?></span>
                  <?php if ((int) $tent['is_active'] !== 1): ?>
                    <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant">Inactive</span>
                  <?php endif; ?>
                </div>
                <div class="text-[14px] leading-5 text-on-surface-variant">
                  <?= (int) $tent['member_count'] ?> members &middot; <?= (int) $tent['checkin_count'] ?> checked in
                </div>
              </div>
              <i data-lucide="chevron-right" class="h-5 w-5 shrink-0 text-on-surface-variant"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
