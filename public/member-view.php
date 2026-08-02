<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$me = currentUser();
$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$memberId = (int) ($_GET['id'] ?? 0);

if ($memberId < 1) {
    flash('error', 'Member not found.');
    redirect('members.php');
}

$memberStmt = db()->prepare(
    'SELECT m.*, t.name AS tent_name, t.color_hex AS tent_color
     FROM members m
     LEFT JOIN tents t ON t.id = m.tent_id
     WHERE m.id = ?'
);
$memberStmt->execute([$memberId]);
$member = $memberStmt->fetch();

if ($member === false) {
    flash('error', 'Member not found.');
    redirect('members.php');
}

if (!$isSuper && (int) $member['tent_id'] !== $scopeTentId) {
    flash('error', 'Member not found.');
    redirect('members.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $action = (string) ($_POST['action'] ?? 'add_note');

    if ($action === 'delete_member') {
        // Re-check server-side — never trust the button variant the client rendered.
        $countStmt = db()->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ?');
        $countStmt->execute([$member['id']]);
        if ((int) $countStmt->fetchColumn() > 0) {
            flash('error', 'This member has attendance history and cannot be deleted. Deactivate instead.');
            redirect('member-view.php?id=' . $member['id']);
        }

        $memberName = (string) $member['full_name'];
        db()->prepare('DELETE FROM members WHERE id = ?')->execute([$member['id']]);

        flash('success', $memberName . ' was deleted.');
        redirect('members.php');
    }

    if ($action === 'deactivate_member') {
        db()->prepare("UPDATE members SET status = 'inactive' WHERE id = ?")->execute([$member['id']]);
        flash('success', (string) $member['full_name'] . ' was deactivated.');
        redirect('member-view.php?id=' . $member['id']);
    }

    $note = trim((string) ($_POST['note'] ?? ''));
    if ($note === '') {
        flash('error', 'Note cannot be empty.');
        redirect('member-view.php?id=' . $member['id']);
    }
    if (mb_strlen($note) > 2000) {
        flash('error', 'Note must be 2000 characters or fewer.');
        redirect('member-view.php?id=' . $member['id']);
    }

    db()->prepare('INSERT INTO member_notes (member_id, admin_id, note) VALUES (?, ?, ?)')
        ->execute([$member['id'], $me['id'], $note]);

    flash('success', 'Note added.');
    redirect('member-view.php?id=' . $member['id']);
}

$attendanceStmt = db()->prepare(
    'SELECT sunday_date FROM attendance WHERE member_id = ? AND tent_id = ? ORDER BY sunday_date DESC'
);
$attendanceStmt->execute([$member['id'], (int) $member['tent_id']]);
$attendance = $attendanceStmt->fetchAll();
$attendanceCount = count($attendance);

$followupStmt = db()->prepare(
    'SELECT * FROM first_timer_followups WHERE member_id = ? AND tent_id = ?'
);
$followupStmt->execute([$member['id'], (int) $member['tent_id']]);
$followup = $followupStmt->fetch();

$notesStmt = db()->prepare(
    'SELECT n.id, n.note, n.created_at, u.name AS admin_name
     FROM member_notes n
     LEFT JOIN users u ON u.id = n.admin_id
     WHERE n.member_id = ?
     ORDER BY n.created_at DESC'
);
$notesStmt->execute([$member['id']]);
$notes = $notesStmt->fetchAll();

$followupBadges = [
    'pending' => 'bg-tertiary text-on-tertiary',
    'called' => 'bg-secondary text-on-secondary',
    'converted' => 'bg-primary text-on-primary',
    'not_returning' => 'bg-error text-on-error',
];

$birthday = '—';
if ($member['birth_month'] !== null && $member['birth_day'] !== null) {
    $birthdayDt = DateTimeImmutable::createFromFormat('!n-j', ((int) $member['birth_month']) . '-' . ((int) $member['birth_day']));
    $birthday = $birthdayDt !== false ? $birthdayDt->format('F j') : '—';
}

$hasPhone = (string) ($member['phone'] ?? '') !== '';

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$destructiveButton = $buttonBase . ' bg-error text-on-error';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$pageTitle = 'Member Profile';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-4xl">
  <a href="members.php" class="mb-5 inline-flex min-h-[44px] items-center gap-2 rounded-full px-3 text-[14px] leading-5 font-medium text-on-surface-variant hover:bg-surface-container">
    <i data-lucide="arrow-left" class="h-5 w-5"></i>
    Back to members
  </a>

  <header class="mb-6 flex flex-wrap items-start justify-between gap-4 md:mb-8">
    <div class="min-w-0">
      <div class="flex flex-wrap items-center gap-2">
        <?php if ((int) $member['is_first_timer'] === 1): ?>
          <span class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">First-Timer</span>
        <?php endif; ?>
        <?php if ((string) $member['status'] === 'active'): ?>
          <span class="inline-flex items-center rounded-full bg-primary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary-container">Active</span>
        <?php else: ?>
          <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant">Inactive</span>
        <?php endif; ?>
      </div>
      <h1 class="mt-2 font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10"><?= e($member['full_name']) ?></h1>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">
        <?= e($member['tent_name'] ?? '') ?> &middot; Joined <?= e(date('M j, Y', strtotime((string) $member['join_date']))) ?>
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if ($hasPhone): ?>
        <a href="tel:<?= e($member['phone']) ?>" class="<?= $primaryButton ?>">
          <i data-lucide="phone" class="h-5 w-5"></i>
          Call
        </a>
      <?php endif; ?>
      <a href="member-edit.php?id=<?= (int) $member['id'] ?>" class="<?= $secondaryButton ?>">
        <i data-lucide="pencil" class="h-5 w-5"></i>
        Edit
      </a>
      <?php if ($attendanceCount === 0): ?>
        <form method="post" action="member-view.php?id=<?= (int) $member['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="delete_member">
          <button type="submit" class="<?= $destructiveButton ?>"
                  onclick="return confirm(<?= e(json_encode('Delete ' . $member['full_name'] . '? This member has no attendance history, so this is permanent and cannot be undone.', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>)">
            <i data-lucide="trash-2" class="h-5 w-5"></i>
            Delete Member
          </button>
        </form>
      <?php elseif ((string) $member['status'] === 'active'): ?>
        <form method="post" action="member-view.php?id=<?= (int) $member['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="deactivate_member">
          <button type="submit" class="<?= $secondaryButton ?>"
                  onclick="return confirm(<?= e(json_encode('Deactivate ' . $member['full_name'] . '? They\'ll be hidden from active lists but their attendance history is kept.', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>)">
            <i data-lucide="user-x" class="h-5 w-5"></i>
            Deactivate Member
          </button>
        </form>
      <?php endif; ?>
    </div>
  </header>

  <section class="bg-surface-lowest rounded-lg p-5 shadow-card">
    <h2 class="mb-4 font-display text-[20px] leading-7 font-semibold text-on-surface">Profile</h2>
    <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= e($member['tent_name'] ?? '—') ?></dd>
      </div>
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Phone</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface">
          <?php if ($hasPhone): ?>
            <a href="tel:<?= e($member['phone']) ?>" class="hover:text-primary"><?= e($member['phone']) ?></a>
          <?php else: ?>
            <span class="text-on-surface-variant">Not on file</span>
          <?php endif; ?>
        </dd>
      </div>
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Occupation</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= ucfirst((string) $member['occupation']) ?></dd>
      </div>
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">School</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= $member['occupation'] === 'student' && $member['school_name'] !== null && $member['school_name'] !== '' ? e($member['school_name']) : '—' ?></dd>
      </div>
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Birthday</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= e($birthday) ?></dd>
      </div>
      <div>
        <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">First attended</dt>
        <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= $member['first_seen_sunday'] !== null ? e(date('M j, Y', strtotime((string) $member['first_seen_sunday']))) : '—' ?></dd>
      </div>
    </dl>
  </section>

  <section class="mt-6 bg-surface-lowest rounded-lg p-5 shadow-card">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">Attendance History</h2>
      <span class="inline-flex items-center rounded-full bg-primary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary-container">
        <?= count($attendance) ?> visit<?= count($attendance) === 1 ? '' : 's' ?>
      </span>
    </div>

    <?php if ($attendance === []): ?>
      <div class="py-8 text-center">
        <i data-lucide="calendar-days" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">No attendance recorded for this member yet.</p>
      </div>
    <?php else: ?>
      <ul class="divide-y divide-outline-variant">
        <?php foreach ($attendance as $row): ?>
          <li class="flex items-center gap-3 py-3">
            <i data-lucide="check" class="h-5 w-5 shrink-0 text-primary"></i>
            <span class="font-display text-[16px] leading-6 font-medium text-on-surface"><?= e(date('l, M j, Y', strtotime((string) $row['sunday_date']))) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <?php if ($followup !== false): ?>
    <section class="mt-6 bg-surface-lowest rounded-lg p-5 shadow-card">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-display text-[20px] leading-7 font-semibold text-on-surface">First-Timer Follow-Up</h2>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] <?= e($followupBadges[$followup['status']] ?? 'bg-surface-container-high text-on-surface-variant') ?>">
          <?= ucwords(str_replace('_', ' ', (string) $followup['status'])) ?>
        </span>
      </div>
      <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
        <div>
          <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">First visit</dt>
          <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= e(date('M j, Y', strtotime((string) $followup['first_visit']))) ?></dd>
        </div>
        <?php if ($followup['assigned_to'] !== null): ?>
          <div>
            <dt class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Assigned to</dt>
            <dd class="mt-1 text-[16px] leading-6 text-on-surface"><?= e((string) $followup['assigned_to']) ?></dd>
          </div>
        <?php endif; ?>
      </dl>
      <?php if ($followup['notes'] !== null && $followup['notes'] !== ''): ?>
        <p class="mt-4 border-t border-outline-variant pt-4 text-[16px] leading-6 text-on-surface-variant"><?= nl2br(e((string) $followup['notes'])) ?></p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="mt-6 bg-surface-lowest rounded-lg p-5 shadow-card">
    <h2 class="mb-4 font-display text-[20px] leading-7 font-semibold text-on-surface">Private Notes</h2>

    <?php if ($notes === []): ?>
      <div class="py-6 text-center">
        <i data-lucide="message-circle" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">No private notes yet.</p>
      </div>
    <?php else: ?>
      <ul class="space-y-4">
        <?php foreach ($notes as $note): ?>
          <li class="rounded-lg bg-surface-container-low p-4">
            <div class="mb-1 flex flex-wrap items-center gap-2 text-[12px] leading-4 text-on-surface-variant">
              <span class="font-display font-medium text-on-surface"><?= e($note['admin_name'] ?? 'Unknown admin') ?></span>
              &middot; <?= e(date('M j, Y g:i A', strtotime((string) $note['created_at']))) ?>
            </div>
            <p class="whitespace-pre-wrap text-[16px] leading-6 text-on-surface"><?= e((string) $note['note']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="member-view.php?id=<?= (int) $member['id'] ?>" class="mt-5 border-t border-outline-variant pt-5">
      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
      <label for="note" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Add a note</label>
      <textarea id="note" name="note" maxlength="2000" required rows="3" placeholder="Private note visible only to admins"
                class="<?= $inputClass ?>"></textarea>
      <div class="mt-3 flex justify-end">
        <button type="submit" class="<?= $primaryButton ?>">
          <i data-lucide="plus" class="h-5 w-5"></i>
          Add Note
        </button>
      </div>
    </form>
  </section>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
