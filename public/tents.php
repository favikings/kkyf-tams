<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireSuperAdmin();

$me = currentUser();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $action = (string) ($_POST['action'] ?? '');
    $tentId = (int) ($_POST['tent_id'] ?? 0);

    if ($action === 'add' || $action === 'update') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $colorHex = strtoupper(trim((string) ($_POST['color_hex'] ?? '')));
        $leaderName = trim((string) ($_POST['leader_name'] ?? ''));
        $leaderPhone = trim((string) ($_POST['leader_phone'] ?? ''));
        $whatsappLink = trim((string) ($_POST['whatsapp_link'] ?? ''));

        $errors = [];

        if ($name === '') {
            $errors[] = 'Tent name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Tent name must be 100 characters or fewer.';
        }

        if ($colorHex === '') {
            $colorHex = '#2E86C1';
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $colorHex)) {
            $errors[] = 'Color must be a valid hex value, like #2E86C1.';
        }

        if (mb_strlen($leaderName) > 150) {
            $errors[] = 'Leader name must be 150 characters or fewer.';
        }
        if (mb_strlen($leaderPhone) > 20) {
            $errors[] = 'Leader phone must be 20 characters or fewer.';
        }
        if (mb_strlen($whatsappLink) > 512) {
            $errors[] = 'WhatsApp link must be 512 characters or fewer.';
        }

        if ($errors === []) {
            $dupStmt = db()->prepare('SELECT id FROM tents WHERE name = ? AND id != ?');
            $dupStmt->execute([$name, $tentId]);
            if ($dupStmt->fetch() !== false) {
                $errors[] = 'A tent with that name already exists.';
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
            redirect('tents.php');
        }

        $leaderName = $leaderName === '' ? null : $leaderName;
        $leaderPhone = $leaderPhone === '' ? null : $leaderPhone;
        $whatsappLink = $whatsappLink === '' ? null : $whatsappLink;

        if ($action === 'add') {
            $stmt = db()->prepare(
                'INSERT INTO tents (name, color_hex, leader_name, leader_phone, whatsapp_link)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $colorHex, $leaderName, $leaderPhone, $whatsappLink]);
            flash('success', 'Tent created.');
        } else {
            $stmt = db()->prepare(
                'UPDATE tents
                 SET name = ?, color_hex = ?, leader_name = ?, leader_phone = ?, whatsapp_link = ?
                 WHERE id = ?'
            );
            $stmt->execute([$name, $colorHex, $leaderName, $leaderPhone, $whatsappLink, $tentId]);
            flash('success', 'Tent updated.');
        }
    } elseif ($action === 'deactivate') {
        $stmt = db()->prepare('UPDATE tents SET is_active = 0 WHERE id = ?');
        $stmt->execute([$tentId]);
        flash('success', 'Tent deactivated.');
    } elseif ($action === 'activate') {
        $stmt = db()->prepare('UPDATE tents SET is_active = 1 WHERE id = ?');
        $stmt->execute([$tentId]);
        flash('success', 'Tent activated.');
    } elseif ($action === 'delete_all_members') {
        $tentRowStmt = db()->prepare('SELECT name FROM tents WHERE id = ?');
        $tentRowStmt->execute([$tentId]);
        $tentRow = $tentRowStmt->fetch();

        if ($tentRow === false) {
            flash('error', 'Tent not found.');
            redirect('tents.php');
        }

        $tentName = (string) $tentRow['name'];
        $confirmName = (string) ($_POST['confirm_name'] ?? '');

        if ($confirmName !== $tentName) {
            flash('error', 'Tent name did not match — nothing was deleted.');
            redirect('tents.php');
        }

        $memberCountStmt = db()->prepare('SELECT COUNT(*) FROM members WHERE tent_id = ?');
        $memberCountStmt->execute([$tentId]);
        $memberCount = (int) $memberCountStmt->fetchColumn();

        $attendanceCountStmt = db()->prepare('SELECT COUNT(*) FROM attendance WHERE tent_id = ?');
        $attendanceCountStmt->execute([$tentId]);
        $attendanceCount = (int) $attendanceCountStmt->fetchColumn();

        db()->prepare('DELETE FROM members WHERE tent_id = ?')->execute([$tentId]);

        logDangerZoneAction(sprintf(
            'Super Admin %s (id=%d) deleted ALL members in tent "%s" (id=%d): %d members, %d attendance records permanently removed.',
            $me['name'],
            $me['id'],
            $tentName,
            $tentId,
            $memberCount,
            $attendanceCount
        ));

        flash('success', sprintf(
            'Deleted %d member%s and %d attendance record%s from %s.',
            $memberCount,
            $memberCount === 1 ? '' : 's',
            $attendanceCount,
            $attendanceCount === 1 ? '' : 's',
            $tentName
        ));
    }

    redirect('tents.php');
}

$tentsStmt = db()->prepare(
    "SELECT t.id, t.name, t.color_hex, t.leader_name, t.leader_phone, t.whatsapp_link, t.is_active,
            (SELECT COUNT(*) FROM members m WHERE m.tent_id = t.id AND m.status = 'active') AS active_members,
            (SELECT COUNT(*) FROM members m2 WHERE m2.tent_id = t.id) AS total_members,
            (SELECT COUNT(*) FROM attendance a WHERE a.tent_id = t.id) AS total_attendance
     FROM tents t
     ORDER BY t.is_active DESC, t.name ASC"
);
$tentsStmt->execute();
$tents = $tentsStmt->fetchAll();

foreach ($tents as &$tentRow) {
    $tentRow['total_members'] = (int) $tentRow['total_members'];
    $tentRow['total_attendance'] = (int) $tentRow['total_attendance'];
}
unset($tentRow);

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$destructiveButton = $buttonBase . ' bg-error text-on-error';

$pageTitle = 'Tents';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div x-data="tentsPage()">
  <div class="mx-auto max-w-5xl">
    <header class="mb-6 flex items-center justify-between gap-4 md:mb-8">
      <div>
        <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Tents</h1>
        <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">The groups members belong to.</p>
      </div>
      <button type="button" @click="openAdd()" class="<?= $primaryButton ?>">
        <i data-lucide="plus" class="h-5 w-5"></i>
        Add Tent
      </button>
    </header>

    <?php if ($tents === []): ?>
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="tent" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No tents yet. Add the first one to get started.</p>
        <button type="button" @click="openAdd()" class="<?= $primaryButton ?>">Add Tent</button>
      </div>
    <?php else: ?>
      <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($tents as $tent):
            $tentColor = (string) ($tent['color_hex'] ?? '');
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tentColor)) {
                $tentColor = '#2E86C1';
            }
        ?>
          <div class="bg-surface-lowest rounded-lg p-4 shadow-card">
            <div class="flex items-start gap-3">
              <div class="h-11 w-11 shrink-0 rounded-lg" style="background-color: <?= e($tentColor) ?>"></div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="truncate font-display text-[20px] leading-7 font-semibold text-on-surface"><?= e($tent['name']) ?></span>
                  <?php if ((int) $tent['is_active'] !== 1): ?>
                    <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant">Inactive</span>
                  <?php endif; ?>
                </div>
                <div class="mt-1 text-[14px] leading-5 text-on-surface-variant">
                  <?= (int) $tent['active_members'] ?> active member<?= (int) $tent['active_members'] === 1 ? '' : 's' ?>
                </div>
                <?php if ($tent['leader_name'] !== null || $tent['leader_phone'] !== null): ?>
                  <div class="mt-0.5 text-[14px] leading-5 text-on-surface-variant">
                    <?= e($tent['leader_name'] ?? '') ?><?= $tent['leader_name'] !== null && $tent['leader_phone'] !== null ? ' · ' : '' ?><?= e($tent['leader_phone'] ?? '') ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <?php if ($tent['whatsapp_link'] !== null && $tent['whatsapp_link'] !== ''): ?>
                <a href="<?= e($tent['whatsapp_link']) ?>" target="_blank" rel="noopener noreferrer" class="<?= $secondaryButton ?>">
                  <i data-lucide="message-circle" class="h-5 w-5"></i>
                  WhatsApp
                </a>
              <?php endif; ?>
              <button type="button" @click="openEdit(<?= e(json_encode($tent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>)" class="<?= $secondaryButton ?>">
                <i data-lucide="pencil" class="h-5 w-5"></i>
                Edit
              </button>
              <?php if ((int) $tent['is_active'] === 1): ?>
                <form method="post" action="tents.php">
                  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="tent_id" value="<?= (int) $tent['id'] ?>">
                  <button type="submit" name="action" value="deactivate"
                          onclick="return confirm('Deactivate this tent? Its members stay on file, but the tent is hidden from new activity.')"
                          class="<?= $destructiveButton ?>">Deactivate</button>
                </form>
              <?php else: ?>
                <form method="post" action="tents.php">
                  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="tent_id" value="<?= (int) $tent['id'] ?>">
                  <button type="submit" name="action" value="activate" class="<?= $secondaryButton ?>">Activate</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-inverse-surface/40 backdrop-blur-md md:items-center">
    <div class="w-full bg-surface-lowest rounded-t-xl p-5 shadow-elevated md:max-w-md md:rounded-xl md:p-6" @click.outside="showModal = false">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface" x-text="modalTitle"></h2>
        <button type="button" @click="showModal = false" aria-label="Close"
                class="flex h-11 w-11 items-center justify-center rounded-full hover:bg-surface-container">
          <i data-lucide="x" class="h-5 w-5 text-on-surface-variant"></i>
        </button>
      </div>

      <form method="post" action="tents.php" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" x-model="form.action">
        <input type="hidden" name="tent_id" x-model="form.tent_id">

        <div>
          <label for="tent-name" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Name</label>
          <input type="text" id="tent-name" name="name" x-model="form.name" required maxlength="100"
                 placeholder="e.g. Eagle Tent"
                 class="mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
        </div>

        <div>
          <label for="tent-color" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Color</label>
          <input type="color" id="tent-color" name="color_hex" x-model="form.color_hex"
                 class="mt-1 h-[44px] w-full cursor-pointer rounded-md border border-outline-variant bg-surface-container-low p-1.5">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="tent-leader-name" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Leader Name</label>
            <input type="text" id="tent-leader-name" name="leader_name" x-model="form.leader_name" maxlength="150"
                   class="mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
          </div>
          <div>
            <label for="tent-leader-phone" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Leader Phone</label>
            <input type="tel" id="tent-leader-phone" name="leader_phone" x-model="form.leader_phone" maxlength="20"
                   class="mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
          </div>
        </div>

        <div>
          <label for="tent-whatsapp" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">WhatsApp Group Link</label>
          <input type="url" id="tent-whatsapp" name="whatsapp_link" x-model="form.whatsapp_link" maxlength="512"
                 placeholder="https://chat.whatsapp.com/..."
                 class="mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" @click="showModal = false" class="<?= $secondaryButton ?>">Cancel</button>
          <button type="submit" class="<?= $primaryButton ?>">Save Tent</button>
        </div>
      </form>

      <div x-show="form.action === 'update'" x-cloak class="mt-6 rounded-md border border-error bg-error-container/20 p-4">
        <h3 class="font-display text-[14px] leading-5 font-semibold text-on-error-container">Danger Zone</h3>
        <p class="mt-1 text-[13px] leading-5 text-on-error-container/80">
          Permanently delete every member in this tent, along with their attendance history. This cannot be undone.
        </p>
        <button type="button" @click="showModal = false; openDanger()" class="<?= $destructiveButton ?> mt-3">
          Delete All Members in <span x-text="currentTent ? currentTent.name : ''"></span>
        </button>
      </div>
    </div>
  </div>

  <div x-show="showDanger" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-inverse-surface/40 backdrop-blur-md md:items-center">
    <div class="w-full bg-surface-lowest rounded-t-xl p-5 shadow-elevated md:max-w-md md:rounded-xl md:p-6" @click.outside="showDanger = false">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="font-display text-[20px] leading-7 font-semibold text-error">Delete All Members</h2>
        <button type="button" @click="showDanger = false" aria-label="Close"
                class="flex h-11 w-11 items-center justify-center rounded-full hover:bg-surface-container">
          <i data-lucide="x" class="h-5 w-5 text-on-surface-variant"></i>
        </button>
      </div>

      <p class="text-[14px] leading-5 text-on-surface-variant">
        This will permanently delete
        <strong class="text-on-surface" x-text="currentTent ? currentTent.total_members : 0"></strong>
        member<span x-show="!currentTent || currentTent.total_members !== 1">s</span> and
        <strong class="text-on-surface" x-text="currentTent ? currentTent.total_attendance : 0"></strong>
        attendance record<span x-show="!currentTent || currentTent.total_attendance !== 1">s</span> from
        <strong class="text-on-surface" x-text="currentTent ? currentTent.name : ''"></strong>. This cannot be undone.
      </p>

      <label for="danger-confirm-name" class="mt-4 block font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">
        Type "<span x-text="currentTent ? currentTent.name : ''"></span>" to confirm
      </label>
      <input type="text" id="danger-confirm-name" x-model="dangerConfirm" autocomplete="off"
             class="mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-error focus:bg-surface-lowest focus:outline-none">

      <form method="post" action="tents.php" class="mt-4">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="delete_all_members">
        <input type="hidden" name="tent_id" :value="currentTent ? currentTent.id : 0">
        <input type="hidden" name="confirm_name" :value="dangerConfirm">
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" @click="showDanger = false" class="<?= $secondaryButton ?>">Cancel</button>
          <button type="submit" :disabled="!currentTent || dangerConfirm !== currentTent.name"
                  class="<?= $destructiveButton ?> disabled:opacity-40 disabled:pointer-events-none">
            Delete All Members
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function tentsPage() {
    return {
      showModal: false,
      modalTitle: 'Add Tent',
      currentTent: null,
      showDanger: false,
      dangerConfirm: '',
      form: {
        action: 'add',
        tent_id: 0,
        name: '',
        color_hex: '#2E86C1',
        leader_name: '',
        leader_phone: '',
        whatsapp_link: '',
      },
      openAdd() {
        this.modalTitle = 'Add Tent';
        this.currentTent = null;
        this.form = {
          action: 'add',
          tent_id: 0,
          name: '',
          color_hex: '#2E86C1',
          leader_name: '',
          leader_phone: '',
          whatsapp_link: '',
        };
        this.showModal = true;
      },
      openEdit(tent) {
        this.modalTitle = 'Edit Tent';
        this.currentTent = tent;
        this.form = {
          action: 'update',
          tent_id: tent.id,
          name: tent.name || '',
          color_hex: tent.color_hex || '#2E86C1',
          leader_name: tent.leader_name || '',
          leader_phone: tent.leader_phone || '',
          whatsapp_link: tent.whatsapp_link || '',
        };
        this.showModal = true;
      },
      openDanger() {
        this.dangerConfirm = '';
        this.showDanger = true;
      },
    };
  }
</script>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
