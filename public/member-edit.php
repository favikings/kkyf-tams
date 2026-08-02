<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$me = currentUser();
$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$memberId = (int) ($_GET['id'] ?? 0);

$member = null;
if ($memberId > 0) {
    $memberStmt = db()->prepare('SELECT * FROM members WHERE id = ?');
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
}

$errors = [];
$formData = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $input = $_POST;
    if (!$isSuper) {
        $input['tent_id'] = $scopeTentId;
    }

    $result = validateMember($input);
    $errors = $result['errors'];
    $clean = $result['clean'];

    $joinDate = trim((string) ($input['join_date'] ?? ''));
    if ($joinDate === '') {
        $joinDate = date('Y-m-d');
    }
    $joinDateDt = DateTime::createFromFormat('!Y-m-d', $joinDate);
    if ($joinDateDt === false || $joinDateDt->format('Y-m-d') !== $joinDate) {
        $errors[] = 'Join date is not a valid date.';
    }

    $status = (string) ($input['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
    $isFirstTimer = isset($input['is_first_timer']) && (string) $input['is_first_timer'] === '1' ? 1 : 0;

    if ($errors === []) {
        if ($member === null) {
            $insertStmt = db()->prepare(
                'INSERT INTO members (tent_id, full_name, phone, birth_month, birth_day, occupation,
                                      school_name, is_first_timer, join_date, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertStmt->execute([
                $clean['tent_id'],
                $clean['full_name'],
                $clean['phone'],
                $clean['birth_month'],
                $clean['birth_day'],
                $clean['occupation'],
                $clean['school_name'],
                $isFirstTimer,
                $joinDate,
                $status,
                $me['id'],
            ]);

            flash('success', 'Member added.');
            redirect('member-view.php?id=' . (int) db()->lastInsertId());
        }

        $updateStmt = db()->prepare(
            'UPDATE members
             SET tent_id = ?, full_name = ?, phone = ?, birth_month = ?, birth_day = ?,
                 occupation = ?, school_name = ?, is_first_timer = ?, join_date = ?, status = ?
             WHERE id = ?'
        );
        $updateStmt->execute([
            $clean['tent_id'],
            $clean['full_name'],
            $clean['phone'],
            $clean['birth_month'],
            $clean['birth_day'],
            $clean['occupation'],
            $clean['school_name'],
            $isFirstTimer,
            $joinDate,
            $status,
            $member['id'],
        ]);

        flash('success', 'Member updated.');
        redirect('member-view.php?id=' . $member['id']);
    }

    $formData = $input;
}

if ($formData !== null) {
    $values = [
        'full_name' => (string) ($formData['full_name'] ?? ''),
        'phone' => (string) ($formData['phone'] ?? ''),
        'occupation' => (string) ($formData['occupation'] ?? '') === 'student' ? 'student' : 'worker',
        'school_name' => (string) ($formData['school_name'] ?? ''),
        'birth_month' => (string) ($formData['birth_month'] ?? ''),
        'birth_day' => (string) ($formData['birth_day'] ?? ''),
        'join_date' => (string) ($formData['join_date'] ?? date('Y-m-d')),
        'status' => (string) ($formData['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
        'is_first_timer' => isset($formData['is_first_timer']) ? 1 : 0,
        'tent_id' => (int) ($formData['tent_id'] ?? 0),
    ];
} elseif ($member !== null) {
    $values = [
        'full_name' => (string) $member['full_name'],
        'phone' => (string) $member['phone'],
        'occupation' => (string) $member['occupation'],
        'school_name' => (string) ($member['school_name'] ?? ''),
        'birth_month' => $member['birth_month'] !== null ? (string) $member['birth_month'] : '',
        'birth_day' => $member['birth_day'] !== null ? (string) $member['birth_day'] : '',
        'join_date' => (string) $member['join_date'],
        'status' => (string) $member['status'],
        'is_first_timer' => (int) $member['is_first_timer'],
        'tent_id' => (int) $member['tent_id'],
    ];
} else {
    $defaultTent = $scopeTentId ?? (int) ($_GET['tent_id'] ?? 0);
    $values = [
        'full_name' => '',
        'phone' => '',
        'occupation' => 'worker',
        'school_name' => '',
        'birth_month' => '',
        'birth_day' => '',
        'join_date' => date('Y-m-d'),
        'status' => 'active',
        'is_first_timer' => 0,
        'tent_id' => $defaultTent,
    ];
}

$activeTents = db()->prepare('SELECT id, name FROM tents WHERE is_active = 1 ORDER BY name ASC');
$activeTents->execute();
$activeTents = $activeTents->fetchAll();

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$isEdit = $member !== null;
$pageTitle = $isEdit ? 'Edit Member' : 'Add Member';
$cancelHref = $isEdit ? 'member-view.php?id=' . $member['id'] : 'members.php';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-2xl">
  <header class="mb-6 md:mb-8">
    <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10"><?= $isEdit ? 'Edit Member' : 'Add Member' ?></h1>
    <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">
      <?= $isEdit ? e($values['full_name']) : 'Add a member to the membership list.' ?>
    </p>
  </header>

  <?php if ($errors !== []): ?>
    <div class="mb-6 rounded-lg bg-error-container px-5 py-4">
      <div class="mb-1 flex items-center gap-2 font-display text-[14px] leading-5 font-semibold text-on-error-container">
        <i data-lucide="shield-alert" class="h-5 w-5"></i>
        Please fix the following:
      </div>
      <ul class="list-inside list-disc space-y-1 text-[14px] leading-5 text-on-error-container">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= $isEdit ? 'member-edit.php?id=' . (int) $member['id'] : 'member-edit.php' ?>"
        x-data="{ occupation: '<?= e($values['occupation']) ?>' }"
        class="bg-surface-lowest rounded-lg p-5 shadow-card">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

    <div>
      <label for="full-name" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Full Name</label>
      <input type="text" id="full-name" name="full_name" value="<?= e($values['full_name']) ?>" required maxlength="200"
             class="<?= $inputClass ?>">
    </div>

    <div class="mt-4">
      <label for="phone" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Phone</label>
      <input type="tel" id="phone" name="phone" value="<?= e($values['phone']) ?>" maxlength="20"
             placeholder="Optional — e.g. 08012345678"
             class="<?= $inputClass ?>">
    </div>

    <?php if ($isSuper): ?>
      <div class="mt-4">
        <label for="tent" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
        <select id="tent" name="tent_id" required class="<?= $inputClass ?>">
          <option value="" disabled <?= $values['tent_id'] === 0 ? 'selected' : '' ?>>Select a tent</option>
          <?php foreach ($activeTents as $tent): ?>
            <option value="<?= (int) $tent['id'] ?>" <?= (int) $tent['id'] === $values['tent_id'] ? 'selected' : '' ?>>
              <?= e($tent['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php else: ?>
      <input type="hidden" name="tent_id" value="<?= (int) $values['tent_id'] ?>">
    <?php endif; ?>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <div>
        <label for="occupation" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Occupation</label>
        <select id="occupation" name="occupation" x-model="occupation" class="<?= $inputClass ?>">
          <option value="worker" <?= $values['occupation'] === 'worker' ? 'selected' : '' ?>>Worker</option>
          <option value="student" <?= $values['occupation'] === 'student' ? 'selected' : '' ?>>Student</option>
        </select>
      </div>
      <div>
        <label for="status" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Status</label>
        <select id="status" name="status" class="<?= $inputClass ?>">
          <option value="active" <?= $values['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div class="mt-4" x-show="occupation === 'student'" x-cloak>
      <label for="school-name" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">School Name</label>
      <input type="text" id="school-name" name="school_name" value="<?= e($values['school_name']) ?>" maxlength="200"
             placeholder="Optional"
             class="<?= $inputClass ?>">
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <div>
        <label for="birth-month" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Birth Month</label>
        <select id="birth-month" name="birth_month" class="<?= $inputClass ?>">
          <option value="">Not set</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $values['birth_month'] === (string) $m ? 'selected' : '' ?>><?= DateTimeImmutable::createFromFormat('!n', (string) $m)->format('F') ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div>
        <label for="birth-day" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Birth Day</label>
        <select id="birth-day" name="birth_day" class="<?= $inputClass ?>">
          <option value="">Not set</option>
          <?php for ($d = 1; $d <= 31; $d++): ?>
            <option value="<?= $d ?>" <?= $values['birth_day'] === (string) $d ? 'selected' : '' ?>><?= $d ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="mt-4">
      <label for="join-date" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Join Date</label>
      <input type="date" id="join-date" name="join_date" value="<?= e($values['join_date']) ?>" required
             class="<?= $inputClass ?>">
    </div>

    <label class="mt-4 flex min-h-[44px] cursor-pointer items-center gap-3">
      <input type="checkbox" name="is_first_timer" value="1" <?= $values['is_first_timer'] === 1 ? 'checked' : '' ?>
             class="h-5 w-5 rounded border-outline-variant bg-surface-container-low accent-[rgb(var(--color-primary))]">
      <span class="text-[16px] leading-6 text-on-surface">Mark as a first-timer</span>
    </label>

    <div class="mt-6 flex items-center justify-end gap-2 border-t border-outline-variant pt-5">
      <a href="<?= e($cancelHref) ?>" class="<?= $secondaryButton ?>">Cancel</a>
      <button type="submit" class="<?= $primaryButton ?>">
        <i data-lucide="check" class="h-5 w-5"></i>
        <?= $isEdit ? 'Save Changes' : 'Add Member' ?>
      </button>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
