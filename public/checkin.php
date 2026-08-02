<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$tentId = $scopeTentId ?? (int) ($_GET['tent_id'] ?? 0);
$sundayDate = currentSunday();

$tent = null;
if ($tentId > 0) {
    $tentStmt = db()->prepare('SELECT id, name FROM tents WHERE id = ?');
    $tentStmt->execute([$tentId]);
    $tentRow = $tentStmt->fetch();

    if ($tentRow === false) {
        flash('error', 'Tent not found.');
        redirect('checkin.php');
    }

    $tent = $tentRow;
}

$pickerTents = [];
if ($isSuper && $tentId === 0) {
    $pickerStmt = db()->prepare(
        "SELECT t.id, t.name, t.color_hex,
                (SELECT COUNT(*) FROM members m WHERE m.tent_id = t.id AND m.status = 'active') AS member_count,
                (SELECT COUNT(*) FROM attendance a WHERE a.tent_id = t.id AND a.sunday_date = ?) AS checkin_count
         FROM tents t
         WHERE t.is_active = 1
         ORDER BY t.name ASC"
    );
    $pickerStmt->execute([$sundayDate]);
    $pickerTents = $pickerStmt->fetchAll();
}

$members = [];
if ($tentId > 0) {
    $membersStmt = db()->prepare(
        "SELECT m.id, m.full_name, m.phone, m.occupation, m.is_first_timer, a.checked_in_at
         FROM members m
         LEFT JOIN attendance a ON a.member_id = m.id AND a.sunday_date = ?
         WHERE m.tent_id = ? AND m.status = 'active'
         ORDER BY m.full_name ASC"
    );
    $membersStmt->execute([$sundayDate, $tentId]);
    $members = $membersStmt->fetchAll();
}

$membersForJs = array_map(static function (array $m): array {
    return [
        'id' => (int) $m['id'],
        'full_name' => (string) $m['full_name'],
        'phone' => (string) $m['phone'],
        'is_first_timer' => (int) $m['is_first_timer'] === 1,
        'checked_in' => $m['checked_in_at'] !== null,
        'checked_in_at' => $m['checked_in_at'] !== null ? (string) $m['checked_in_at'] : null,
    ];
}, $members);

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$pageTitle = 'Check In';
$serviceDateLabel = (new DateTimeImmutable($sundayDate))->format('l, F j');

require_once __DIR__ . '/../app/includes/header.php';
?>
<?php if ($isSuper && $tentId === 0): ?>
  <div class="mx-auto max-w-3xl">
    <header class="mb-6 md:mb-8">
      <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Check In</h1>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Choose a tent for <?= e($serviceDateLabel) ?>.</p>
    </header>

    <?php if ($pickerTents === []): ?>
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="tent" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No active tents yet.</p>
        <a href="tents.php" class="<?= $primaryButton ?>">Add Tent</a>
      </div>
    <?php else: ?>
      <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($pickerTents as $t):
            $tentColor = (string) ($t['color_hex'] ?? '');
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tentColor)) {
                $tentColor = '#2E86C1';
            }
        ?>
          <a href="checkin.php?tent_id=<?= (int) $t['id'] ?>" class="flex items-center gap-3 rounded-lg bg-surface-lowest p-4 shadow-card hover:bg-surface-high">
            <div class="h-11 w-11 shrink-0 rounded-lg" style="background-color: <?= e($tentColor) ?>"></div>
            <div class="min-w-0 flex-1">
              <div class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($t['name']) ?></div>
              <div class="text-[14px] leading-5 text-on-surface-variant"><?= (int) $t['member_count'] ?> members &middot; <?= (int) $t['checkin_count'] ?> checked in</div>
            </div>
            <i data-lucide="chevron-right" class="h-5 w-5 shrink-0 text-on-surface-variant"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="mx-auto max-w-3xl" x-data="checkinPage({
    members: <?= e(json_encode($membersForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>,
    csrf: '<?= e(csrfToken()) ?>',
    tentId: <?= (int) $tentId ?>,
  })">
    <header class="mb-6 flex items-center justify-between gap-4 md:mb-8">
      <div>
        <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Check In</h1>
        <p class="mt-1 text-[14px] leading-5 text-on-surface-variant"><?= e($tent['name']) ?> &middot; <?= e($serviceDateLabel) ?></p>
      </div>
      <?php if ($isSuper): ?>
        <a href="checkin.php" class="<?= $secondaryButton ?>">
          <i data-lucide="repeat" class="h-5 w-5"></i>
          Switch Tent
        </a>
      <?php endif; ?>
    </header>

    <div class="mb-6">
      <label for="checkin-search" class="sr-only">Search members</label>
      <div class="relative">
        <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant"></i>
        <input type="search" id="checkin-search" x-model="search" placeholder="Search by name or phone" autofocus
               class="w-full min-h-[56px] rounded-md border border-outline-variant bg-surface-container-low pl-11 pr-3.5 py-2.5 text-[18px] leading-7 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
      </div>
    </div>

    <template x-if="showEmptyRoster">
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No members yet in this tent.</p>
        <button type="button" @click="openAddModal()" class="<?= $primaryButton ?>">Add Member</button>
      </div>
    </template>

    <template x-if="showAllCheckedIn">
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="check-circle-2" class="mx-auto mb-3 h-8 w-8 text-primary"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">Everyone is checked in!</p>
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

    <template x-if="hasNoMatches">
      <div class="mt-4 rounded-lg bg-surface-lowest p-5 text-center shadow-card">
        <p class="text-[16px] leading-6 text-on-surface-variant">Can't find "<span x-text="search"></span>"? Add as a new member.</p>
        <button type="button" @click="openAddModal()" class="<?= $primaryButton ?> mt-4">
          <i data-lucide="user-plus" class="h-5 w-5"></i>
          Add as a new member
        </button>
      </div>
    </template>

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

    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-inverse-surface/40 backdrop-blur-md md:items-center">
      <div class="w-full bg-surface-lowest rounded-t-xl p-5 shadow-elevated md:max-w-md md:rounded-xl md:p-6" @click.outside="showAddModal = false">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Add New Member</h2>
          <button type="button" @click="showAddModal = false" aria-label="Close"
                  class="flex h-11 w-11 items-center justify-center rounded-full hover:bg-surface-container">
            <i data-lucide="x" class="h-5 w-5 text-on-surface-variant"></i>
          </button>
        </div>

        <form @submit.prevent="submitAdd()" class="space-y-4">
          <div>
            <label for="qa-full-name" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Full Name</label>
            <input type="text" id="qa-full-name" x-model="form.full_name" required maxlength="200" class="<?= $inputClass ?>">
          </div>

          <div>
            <label for="qa-phone" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Phone</label>
            <input type="tel" id="qa-phone" x-model="form.phone" maxlength="20" placeholder="Optional — e.g. 08012345678" class="<?= $inputClass ?>">
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label for="qa-birth-month" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Birth Month</label>
              <select id="qa-birth-month" x-model="form.birth_month" class="<?= $inputClass ?>">
                <option value="">Not set</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                  <option value="<?= $m ?>"><?= DateTimeImmutable::createFromFormat('!n', (string) $m)->format('F') ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div>
              <label for="qa-birth-day" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Birth Day</label>
              <select id="qa-birth-day" x-model="form.birth_day" class="<?= $inputClass ?>">
                <option value="">Not set</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                  <option value="<?= $d ?>"><?= $d ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <div>
            <label for="qa-occupation" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Occupation</label>
            <select id="qa-occupation" x-model="form.occupation" class="<?= $inputClass ?>">
              <option value="worker">Worker</option>
              <option value="student">Student</option>
            </select>
          </div>

          <div x-show="form.occupation === 'student'" x-cloak>
            <label for="qa-school" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">School Name</label>
            <input type="text" id="qa-school" x-model="form.school_name" maxlength="200" placeholder="Optional" class="<?= $inputClass ?>">
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" @click="showAddModal = false" class="<?= $secondaryButton ?>">Cancel</button>
            <button type="submit" :disabled="saving" class="<?= $primaryButton ?>">
              <i data-lucide="check" class="h-5 w-5"></i>
              <span x-text="saving ? 'Adding…' : 'Add & Check In'"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function checkinPage(initial) {
      return {
        members: initial.members,
        csrf: initial.csrf,
        tentId: initial.tentId,
        search: '',
        showAddModal: false,
        saving: false,
        form: { full_name: '', phone: '', birth_month: '', birth_day: '', occupation: 'worker', school_name: '' },

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
        get hasNoMatches() {
          return this.search.trim() !== '' && this.filteredMembers.length === 0;
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
              body: JSON.stringify({ member_id: member.id, csrf: this.csrf }),
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

        openAddModal() {
          this.form = {
            full_name: this.search.trim(),
            phone: '',
            birth_month: '',
            birth_day: '',
            occupation: 'worker',
            school_name: '',
          };
          this.showAddModal = true;
        },

        async submitAdd() {
          if (this.saving) {
            return;
          }
          if (this.form.full_name.trim() === '') {
            window.notyf.error('Full name is required.');
            return;
          }

          this.saving = true;
          try {
            const res = await fetch('api/member-quick-add.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ ...this.form, tent_id: this.tentId, csrf: this.csrf }),
            });
            let json = null;
            try {
              json = await res.json();
            } catch (parseError) {
              json = null;
            }
            if (json && json.success) {
              this.members.push({
                id: json.data.member_id,
                full_name: json.data.full_name,
                phone: json.data.phone,
                is_first_timer: true,
                checked_in: true,
                checked_in_at: null,
              });
              this.showAddModal = false;
              this.search = '';
              window.notyf.success(json.data.full_name + ' added and checked in.');
            } else {
              window.notyf.error((json && json.error) || 'Could not add member.');
            }
          } catch (networkError) {
            window.notyf.error('Network error — try again.');
          } finally {
            this.saving = false;
          }
        },
      };
    }
  </script>
<?php endif; ?>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
