<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$tentId = $scopeTentId ?? (int) ($_GET['tent_id'] ?? 0);

if ($tentId > 0) {
    $tentStmt = db()->prepare('SELECT id, name FROM tents WHERE id = ?');
    $tentStmt->execute([$tentId]);
    if ($tentStmt->fetch() === false) {
        flash('error', 'Tent not found.');
        redirect('followups.php');
    }
}

$tents = [];
if ($isSuper) {
    $tentsStmt = db()->prepare('SELECT id, name FROM tents ORDER BY name ASC');
    $tentsStmt->execute();
    $tents = $tentsStmt->fetchAll();
}

$where = '';
$params = [];
if ($tentId > 0) {
    $where = 'WHERE f.tent_id = ?';
    $params[] = $tentId;
}

$rowsStmt = db()->prepare(
    "SELECT f.id, f.tent_id, f.member_id, f.first_visit, f.status, f.assigned_to, f.notes,
            f.updated_by, f.updated_at,
            m.full_name AS member_name,
            t.name AS tent_name,
            a.name AS assigned_name,
            u.name AS updated_by_name
     FROM first_timer_followups f
     JOIN members m ON m.id = f.member_id
     JOIN tents t ON t.id = f.tent_id
     LEFT JOIN users a ON a.id = f.assigned_to
     LEFT JOIN users u ON u.id = f.updated_by
     $where
     ORDER BY (f.status = 'pending') DESC, f.first_visit ASC, m.full_name ASC"
);
$rowsStmt->execute($params);
$followupRows = $rowsStmt->fetchAll();

$adminsByTent = [];
if ($followupRows !== []) {
    $tentIds = array_values(array_unique(array_map(
        static fn (array $r): int => (int) $r['tent_id'],
        $followupRows
    )));
    $placeholders = implode(',', array_fill(0, count($tentIds), '?'));
    $adminsStmt = db()->prepare(
        "SELECT id, name, tent_id FROM users
         WHERE role = 'tent_admin' AND status = 'approved' AND is_active = 1 AND tent_id IN ($placeholders)
         ORDER BY name ASC"
    );
    $adminsStmt->execute($tentIds);
    foreach ($adminsStmt->fetchAll() as $admin) {
        $adminsByTent[(int) $admin['tent_id']][] = [
            'id' => (int) $admin['id'],
            'name' => (string) $admin['name'],
        ];
    }
}

$rowsForJs = array_map(static function (array $r) use ($adminsByTent): array {
    return [
        'id' => (int) $r['id'],
        'member_name' => (string) $r['member_name'],
        'tent_id' => (int) $r['tent_id'],
        'tent_name' => (string) $r['tent_name'],
        'first_visit_label' => date('M j, Y', strtotime((string) $r['first_visit'])),
        'status' => (string) $r['status'],
        'assigned_to' => $r['assigned_to'] !== null ? (int) $r['assigned_to'] : '',
        'notes' => $r['notes'] !== null ? (string) $r['notes'] : '',
        'updated_by_name' => $r['updated_by_name'] !== null ? (string) $r['updated_by_name'] : null,
        'updated_at' => $r['updated_at'] !== null ? (string) $r['updated_at'] : null,
        'admins' => $adminsByTent[(int) $r['tent_id']] ?? [],
    ];
}, $followupRows);

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card disabled:opacity-50 disabled:pointer-events-none';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';
$textareaClass = 'mt-1 w-full rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60 resize-y';

$pageTitle = 'Follow-Ups';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-3xl">
  <header class="mb-6 md:mb-8">
    <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Follow-Ups</h1>
    <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Reach every first-time guest so no one falls through the cracks.</p>
  </header>

  <?php if ($isSuper): ?>
    <form method="get" action="followups.php" class="mb-6 rounded-lg bg-surface-lowest p-5 shadow-card">
      <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
        <div>
          <label for="tent-filter" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
          <select id="tent-filter" name="tent_id" class="<?= $inputClass ?>">
            <option value="">All tents</option>
            <?php foreach ($tents as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= $tentId > 0 && (int) $t['id'] === $tentId ? 'selected' : '' ?>><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="<?= $primaryButton ?>">
          Filter Follow-Ups
        </button>
      </div>
    </form>
  <?php endif; ?>

  <?php if ($followupRows === []): ?>
    <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
      <i data-lucide="phone-call" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
      <p class="text-[16px] leading-6 text-on-surface-variant"><?= $tentId > 0 ? 'No follow-ups for this tent yet.' : 'No follow-ups yet. First-timers from check-in are listed here.' ?></p>
    </div>
  <?php else: ?>
    <div x-data="followupsPage({
      rows: <?= e(json_encode($rowsForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>,
      csrf: '<?= e(csrfToken()) ?>',
    })" class="space-y-3">
      <template x-for="row in rows" :key="row.id">
        <div class="rounded-lg bg-surface-lowest p-5 shadow-card">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-container font-display text-[14px] leading-5 font-semibold text-on-primary-container" x-text="initials(row.member_name)"></span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface" x-text="row.member_name"></span>
                <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant" x-text="row.tent_name"></span>
              </div>
              <p class="mt-0.5 text-[14px] leading-5 text-on-surface-variant">First visit <span x-text="row.first_visit_label"></span></p>
            </div>
            <span x-show="row.status === 'pending'" x-cloak class="inline-flex items-center rounded-full bg-tertiary px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-tertiary">Pending</span>
            <span x-show="row.status === 'called'" x-cloak class="inline-flex items-center rounded-full bg-secondary px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary">Called</span>
            <span x-show="row.status === 'converted'" x-cloak class="inline-flex items-center rounded-full bg-primary px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary">Converted</span>
            <span x-show="row.status === 'not_returning'" x-cloak class="inline-flex items-center rounded-full bg-error px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-error">Not Returning</span>
          </div>

          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Status</label>
              <select x-model="row.status" class="<?= $inputClass ?>">
                <option value="pending">Pending</option>
                <option value="called">Called</option>
                <option value="converted">Converted</option>
                <option value="not_returning">Not Returning</option>
              </select>
            </div>
            <div>
              <label class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Assigned to</label>
              <select x-model="row.assigned_to" class="<?= $inputClass ?>">
                <option value="">Unassigned</option>
                <template x-for="admin in row.admins" :key="admin.id">
                  <option :value="admin.id" x-text="admin.name"></option>
                </template>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <label class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Notes</label>
            <textarea x-model="row.notes" rows="2" class="<?= $textareaClass ?>" placeholder="What happened on the call?"></textarea>
          </div>

          <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p x-show="row.updated_by_name" x-cloak class="text-[12px] leading-4 text-on-surface-variant" x-text="updatedLabel(row)"></p>
            <button type="button" @click="save(row)" :disabled="savingId === row.id" class="<?= $primaryButton ?>">
              <span x-text="savingId === row.id ? 'Saving…' : 'Save changes'"></span>
            </button>
          </div>
        </div>
      </template>
    </div>
  <?php endif; ?>
</div>

<script>
  function followupsPage(initial) {
    return {
      rows: initial.rows,
      csrf: initial.csrf,
      savingId: null,

      initials(name) {
        const parts = (name || '').trim().split(/\s+/).filter(Boolean);
        return ((parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '')).toUpperCase();
      },

      formatDate(value) {
        if (!value) {
          return '';
        }
        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) {
          return '';
        }
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
          + ' ' + d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
      },

      updatedLabel(row) {
        if (!row.updated_by_name) {
          return '';
        }
        let label = 'Last updated by ' + row.updated_by_name;
        if (row.updated_at) {
          label += ' · ' + this.formatDate(row.updated_at);
        }
        return label;
      },

      async save(row) {
        if (this.savingId !== null) {
          return;
        }
        this.savingId = row.id;
        try {
          const res = await fetch('api/followup-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              followup_id: row.id,
              status: row.status,
              assigned_to: row.assigned_to === '' ? null : row.assigned_to,
              notes: row.notes,
              csrf: this.csrf,
            }),
          });
          let json = null;
          try {
            json = await res.json();
          } catch (parseError) {
            json = null;
          }
          if (json && json.success) {
            row.updated_by_name = 'You';
            row.updated_at = json.data.updated_at;
            window.notyf.success('Follow-up updated.');
            return;
          }
          window.notyf.error((json && json.error) || 'Something went wrong.');
        } catch (networkError) {
          window.notyf.error('Network error — try again.');
        } finally {
          this.savingId = null;
        }
      },
    };
  }
</script>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
