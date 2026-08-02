<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$tentId = $scopeTentId ?? (int) ($_GET['tent_id'] ?? 0);

$currentSunday = currentSunday();

// Sunday-only picker: the last 12 service Sundays (including the current one), most recent first.
$sundays = [];
for ($i = 0; $i < 12; $i++) {
    $sundays[] = (new DateTimeImmutable($currentSunday))->modify("-{$i} week")->format('Y-m-d');
}

$selectedSunday = (string) ($_GET['sunday'] ?? '');
if (!in_array($selectedSunday, $sundays, true)) {
    $selectedSunday = $currentSunday;
}

$tent = null;
if ($tentId > 0) {
    $tentStmt = db()->prepare('SELECT id, name FROM tents WHERE id = ?');
    $tentStmt->execute([$tentId]);
    $tentRow = $tentStmt->fetch();

    if ($tentRow === false) {
        flash('error', 'Tent not found.');
        redirect('attendance-history.php');
    }

    $tent = $tentRow;
}

$tents = [];
if ($isSuper) {
    $tentsStmt = db()->prepare('SELECT id, name FROM tents ORDER BY name ASC');
    $tentsStmt->execute();
    $tents = $tentsStmt->fetchAll();
}

$historyRows = [];
$summary = ['total' => 0, 'retroactive' => 0, 'first_timers' => 0];
$membersForJs = [];

if ($tent !== null) {
    $historyStmt = db()->prepare(
        "SELECT m.id, m.full_name, m.phone, m.is_first_timer, a.checked_in_at, a.is_retroactive
         FROM attendance a
         JOIN members m ON m.id = a.member_id
         WHERE a.tent_id = ? AND a.sunday_date = ?
         ORDER BY a.checked_in_at ASC, m.full_name ASC"
    );
    $historyStmt->execute([(int) $tent['id'], $selectedSunday]);
    $historyRows = $historyStmt->fetchAll();

    $summary['total'] = count($historyRows);
    foreach ($historyRows as $row) {
        if ((int) $row['is_retroactive'] === 1) {
            $summary['retroactive']++;
        }
        if ((int) $row['is_first_timer'] === 1) {
            $summary['first_timers']++;
        }
    }

    // Roster for the tap-to-check-in "mark this date" mode (active members + that date's status).
    $rosterStmt = db()->prepare(
        "SELECT m.id, m.full_name, m.phone, m.is_first_timer, a.checked_in_at
         FROM members m
         LEFT JOIN attendance a ON a.member_id = m.id AND a.sunday_date = ?
         WHERE m.tent_id = ? AND m.status = 'active'
         ORDER BY m.full_name ASC"
    );
    $rosterStmt->execute([$selectedSunday, (int) $tent['id']]);
    $markMembers = $rosterStmt->fetchAll();

    $membersForJs = array_map(static function (array $m): array {
        return [
            'id' => (int) $m['id'],
            'full_name' => (string) $m['full_name'],
            'phone' => (string) $m['phone'],
            'is_first_timer' => (int) $m['is_first_timer'] === 1,
            'checked_in' => $m['checked_in_at'] !== null,
            'checked_in_at' => $m['checked_in_at'] !== null ? (string) $m['checked_in_at'] : null,
        ];
    }, $markMembers);
}

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$pageTitle = 'Attendance History';
$serviceDateLabel = (new DateTimeImmutable($selectedSunday))->format('l, F j, Y');

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-3xl">
  <header class="mb-6 md:mb-8">
    <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Attendance History</h1>
    <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Review who attended a past Sunday, or backfill check-ins.</p>
  </header>

  <form method="get" action="attendance-history.php" class="mb-6 rounded-lg bg-surface-lowest p-5 shadow-card">
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="sunday-filter" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Service Sunday</label>
        <select id="sunday-filter" name="sunday" class="<?= $inputClass ?>">
          <?php foreach ($sundays as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $selectedSunday ? 'selected' : '' ?>><?= e((new DateTimeImmutable($s))->format('D, M j, Y')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($isSuper): ?>
        <div>
          <label for="tent-filter" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
          <select id="tent-filter" name="tent_id" class="<?= $inputClass ?>">
            <option value="">Choose a tent</option>
            <?php foreach ($tents as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= $tent !== null && (int) $t['id'] === (int) $tent['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>
    <button type="submit" class="<?= $primaryButton ?> mt-4">
      <i data-lucide="calendar-days" class="h-5 w-5"></i>
      View Attendance
    </button>
  </form>

  <?php if ($tent === null): ?>
    <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
      <i data-lucide="calendar-days" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
      <p class="text-[16px] leading-6 text-on-surface-variant">Choose a tent to view attendance.</p>
    </div>
  <?php else: ?>
    <div x-data="attendanceHistoryPage({
      members: <?= e(json_encode($membersForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>,
      csrf: '<?= e(csrfToken()) ?>',
      tentId: <?= (int) $tent['id'] ?>,
      sundayDate: '<?= e($selectedSunday) ?>',
    })">
      <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface"><?= e($tent['name']) ?></h2>
          <p class="mt-0.5 text-[14px] leading-5 text-on-surface-variant"><?= e($serviceDateLabel) ?></p>
        </div>
        <button type="button" x-show="mode === 'history'" @click="mode = 'mark'" class="<?= $primaryButton ?>">
          <i data-lucide="clipboard-check" class="h-5 w-5"></i>
          Mark attendance for this date
        </button>
        <button type="button" x-show="mode === 'mark'" @click="window.location.reload()" class="<?= $secondaryButton ?>">
          <i data-lucide="arrow-left" class="h-5 w-5"></i>
          Back to history
        </button>
      </header>

      <div x-show="mode === 'history'">
        <div class="mb-6 grid grid-cols-3 gap-4">
          <div class="rounded-xl bg-primary-container p-5 shadow-card">
            <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary-container">Checked In</div>
            <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-primary"><?= $summary['total'] ?></div>
          </div>
          <div class="rounded-xl bg-tertiary-container p-5 shadow-card">
            <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-tertiary-container">Retroactive</div>
            <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-tertiary"><?= $summary['retroactive'] ?></div>
          </div>
          <div class="rounded-xl bg-secondary-container p-5 shadow-card">
            <div class="mb-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-secondary-container">First-Timers</div>
            <div class="font-display text-[32px] leading-9 font-bold tracking-[-0.02em] text-secondary"><?= $summary['first_timers'] ?></div>
          </div>
        </div>

        <?php if ($historyRows === []): ?>
          <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
            <i data-lucide="calendar-x-2" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
            <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No one checked in for <?= e($serviceDateLabel) ?>.</p>
            <button type="button" @click="mode = 'mark'" class="<?= $primaryButton ?>">
              <i data-lucide="clipboard-check" class="h-5 w-5"></i>
              Mark attendance for this date
            </button>
          </div>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($historyRows as $row):
                $parts = preg_split('/\s+/', trim((string) $row['full_name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $rowInitials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
            ?>
              <div class="flex items-center gap-3 rounded-lg bg-surface-lowest p-4 shadow-card">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-container font-display text-[14px] leading-5 font-semibold text-on-primary-container"><?= e($rowInitials) ?></span>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($row['full_name']) ?></span>
                    <?php if ((int) $row['is_first_timer'] === 1): ?>
                      <span class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">First-Timer</span>
                    <?php endif; ?>
                    <?php if ((int) $row['is_retroactive'] === 1): ?>
                      <span class="inline-flex items-center rounded-full bg-tertiary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-tertiary-container">Retroactive</span>
                    <?php endif; ?>
                  </div>
                  <div class="mt-0.5 text-[14px] leading-5 text-on-surface-variant">Checked in at <?= e(date('g:ia', strtotime((string) $row['checked_in_at']))) ?></div>
                </div>
                <i data-lucide="check-circle-2" class="h-6 w-6 shrink-0 text-primary"></i>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div x-show="mode === 'mark'" x-cloak>
        <p class="mb-4 text-[14px] leading-5 text-on-surface-variant">Tap a member to check them in for <?= e($serviceDateLabel) ?>. Entries are stored as retroactive.</p>

        <div class="mb-6">
          <label for="history-search" class="sr-only">Search members</label>
          <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant"></i>
            <input type="search" id="history-search" x-model="search" placeholder="Search by name or phone" autofocus
                   class="w-full min-h-[56px] rounded-md border border-outline-variant bg-surface-container-low pl-11 pr-3.5 py-2.5 text-[18px] leading-7 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
          </div>
        </div>

        <template x-if="showEmptyRoster">
          <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
            <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
            <p class="text-[16px] leading-6 text-on-surface-variant">No members yet in this tent.</p>
          </div>
        </template>

        <template x-if="showAllCheckedIn">
          <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
            <i data-lucide="check-circle-2" class="mx-auto mb-3 h-8 w-8 text-primary"></i>
            <p class="text-[16px] leading-6 text-on-surface-variant">Everyone is checked in for this date.</p>
          </div>
        </template>

        <div class="space-y-3" x-show="pendingMembers.length > 0">
          <template x-for="member in pendingMembers" :key="member.id">
            <button type="button" @click="checkIn(member)"
                    class="flex w-full items-center gap-3 rounded-lg bg-surface-lowest p-4 text-left shadow-card active:scale-[0.98] motion-safe:transition-transform">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-container font-display text-[14px] leading-5 font-semibold text-on-primary-container" x-text="initials(member.full_name)"></span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface" x-text="member.full_name"></span>
                  <span x-show="member.is_first_timer" x-cloak class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">First-Timer</span>
                </div>
                <div class="mt-0.5 text-[14px] leading-5 text-on-surface-variant" x-text="member.phone || 'No phone on file'"></div>
              </div>
              <i data-lucide="circle" class="h-6 w-6 shrink-0 text-on-surface-variant"></i>
            </button>
          </template>
        </div>

        <section class="mt-8" x-show="checkedInMembers.length > 0">
          <h2 class="mb-3 font-display text-[12px] leading-4 font-medium uppercase tracking-[0.04em] text-on-surface-variant">Checked In (<span x-text="checkedInMembers.length"></span>)</h2>
          <div class="space-y-2">
            <template x-for="member in checkedInMembers" :key="member.id">
              <div class="flex items-center gap-3 rounded-lg bg-surface-container-low p-4 opacity-70">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-surface-container-high font-display text-[14px] leading-5 font-semibold text-on-surface-variant" x-text="initials(member.full_name)"></span>
                <div class="min-w-0 flex-1">
                  <div class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface" x-text="member.full_name"></div>
                  <div class="text-[14px] leading-5 text-on-surface-variant" x-text="member.phone || 'No phone on file'"></div>
                </div>
                <i data-lucide="check-circle-2" class="h-6 w-6 shrink-0 text-primary"></i>
              </div>
            </template>
          </div>
        </section>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  function attendanceHistoryPage(initial) {
    return {
      mode: 'history',
      members: initial.members,
      csrf: initial.csrf,
      tentId: initial.tentId,
      sundayDate: initial.sundayDate,
      search: '',

      get filteredMembers() {
        const q = this.search.trim().toLowerCase();
        if (q === '') {
          return this.members;
        }
        return this.members.filter((m) => m.full_name.toLowerCase().includes(q) || m.phone.toLowerCase().includes(q));
      },
      get pendingMembers() {
        return this.filteredMembers.filter((m) => !m.checked_in);
      },
      get checkedInMembers() {
        return this.filteredMembers.filter((m) => m.checked_in);
      },
      get showEmptyRoster() {
        return this.members.length === 0;
      },
      get showAllCheckedIn() {
        return this.members.length > 0 && this.search.trim() === '' && this.pendingMembers.length === 0;
      },

      initials(name) {
        const parts = (name || '').trim().split(/\s+/).filter(Boolean);
        return ((parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '')).toUpperCase();
      },

      async checkIn(member) {
        if (member.checked_in) {
          return;
        }
        member.checked_in = true;
        try {
          const res = await fetch('api/checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_id: member.id, sunday_date: this.sundayDate, csrf: this.csrf }),
          });
          let json = null;
          try {
            json = await res.json();
          } catch (parseError) {
            json = null;
          }
          if (json && json.success) {
            member.checked_in_at = json.data.checked_in_at;
            window.notyf.success(member.full_name + ' checked in.');
            return;
          }
          if (res.status === 409) {
            window.notyf.error('Already checked in.');
            return;
          }
          member.checked_in = false;
          window.notyf.error((json && json.error) || 'Something went wrong.');
        } catch (networkError) {
          member.checked_in = false;
          window.notyf.error('Network error — try again.');
        }
      },
    };
  }
</script>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
