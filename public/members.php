<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireLogin();

$isSuper = isSuperAdmin();
$scopeTentId = scopedTentId();
$tentId = $scopeTentId ?? (int) ($_GET['tent_id'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));

$tents = db()->prepare('SELECT id, name, is_active FROM tents ORDER BY is_active DESC, name ASC');
$tents->execute();
$tents = $tents->fetchAll();

$selectedTentName = null;
if ($tentId > 0) {
    $tentStmt = db()->prepare('SELECT name FROM tents WHERE id = ?');
    $tentStmt->execute([$tentId]);
    $tentRow = $tentStmt->fetch();
    $selectedTentName = $tentRow !== false ? (string) $tentRow['name'] : null;
}

$where = [];
$params = [];

if ($tentId > 0) {
    $where[] = 'm.tent_id = ?';
    $params[] = $tentId;
}

if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where[] = "(m.full_name LIKE ? ESCAPE '\\\\' OR m.phone LIKE ? ESCAPE '\\\\')";
    $params[] = $like;
    $params[] = $like;
}

$sql = 'SELECT m.id, m.full_name, m.phone, m.occupation, m.school_name, m.status, m.is_first_timer,
               m.join_date, m.first_seen_sunday, m.birth_month, m.birth_day, t.name AS tent_name
        FROM members m
        LEFT JOIN tents t ON t.id = m.tent_id';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY m.full_name ASC';

$membersStmt = db()->prepare($sql);
$membersStmt->execute($params);
$members = $membersStmt->fetchAll();

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$inputClass = 'mt-1 w-full min-h-[44px] rounded-md border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-[16px] leading-6 text-on-surface focus:border-primary focus:bg-surface-lowest focus:outline-none placeholder:text-on-surface-variant/60';

$addHref = $tentId > 0 ? 'member-edit.php?tent_id=' . $tentId : 'member-edit.php';

$pageTitle = 'Members';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-5xl">
  <header class="mb-6 flex items-center justify-between gap-4 md:mb-8">
    <div>
      <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Members</h1>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">
        <?= $selectedTentName !== null ? e($selectedTentName) : ($isSuper ? 'All tents' : '') ?>
      </p>
    </div>
    <a href="<?= e($addHref) ?>" class="<?= $primaryButton ?>">
      <i data-lucide="user-plus" class="h-5 w-5"></i>
      Add Member
    </a>
  </header>

  <form method="get" action="members.php" class="mb-6 flex flex-wrap items-end gap-3">
    <?php if ($isSuper && $tents !== []): ?>
      <div class="w-full sm:w-56">
        <label for="tent-filter" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Tent</label>
        <select id="tent-filter" name="tent_id" class="<?= $inputClass ?>">
          <option value="">All tents</option>
          <?php foreach ($tents as $tent): ?>
            <option value="<?= (int) $tent['id'] ?>" <?= (int) $tent['id'] === $tentId ? 'selected' : '' ?>>
              <?= e($tent['name']) ?><?= (int) $tent['is_active'] !== 1 ? ' (inactive)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="min-w-0 flex-1">
      <label for="member-search" class="font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-surface-variant">Search</label>
      <input type="search" id="member-search" name="q" value="<?= e($q) ?>" placeholder="Search by name or phone"
             class="<?= $inputClass ?>">
    </div>

    <button type="submit" class="<?= $primaryButton ?>">
      <i data-lucide="search" class="h-5 w-5"></i>
      Search
    </button>

    <?php if ($q !== '' || ($isSuper && $tentId > 0)): ?>
      <a href="members.php" class="<?= $secondaryButton ?>">Clear</a>
    <?php endif; ?>
  </form>

  <?php if ($members === []): ?>
    <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
      <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
      <?php if ($q !== ''): ?>
        <p class="text-[16px] leading-6 text-on-surface-variant">No members match &ldquo;<?= e($q) ?>&rdquo;.</p>
        <a href="members.php" class="<?= $secondaryButton ?> mt-4">Clear search</a>
      <?php else: ?>
        <p class="mb-4 text-[16px] leading-6 text-on-surface-variant">No members yet<?= $selectedTentName !== null ? ' in ' . e($selectedTentName) : '' ?>.</p>
        <a href="<?= e($addHref) ?>" class="<?= $primaryButton ?>">Add Member</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($members as $member):
          $nameParts = preg_split('/\s+/', trim((string) $member['full_name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
          $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
      ?>
        <div class="bg-surface-lowest rounded-lg p-4 shadow-card">
          <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-container font-display text-[14px] leading-5 font-semibold text-on-primary-container"><?= e($initials) ?></span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <a href="member-view.php?id=<?= (int) $member['id'] ?>" class="truncate font-display text-[16px] leading-6 font-semibold text-on-surface hover:text-primary">
                  <?= e($member['full_name']) ?>
                </a>
                <?php if ((int) $member['is_first_timer'] === 1): ?>
                  <span class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-secondary-container">First-Timer</span>
                <?php endif; ?>
                <?php if ((string) $member['status'] === 'active'): ?>
                  <span class="inline-flex items-center rounded-full bg-primary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary-container">Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant">Inactive</span>
                <?php endif; ?>
              </div>
              <div class="mt-1 truncate text-[14px] leading-5 text-on-surface-variant">
                <?= ucfirst((string) $member['occupation']) ?>
                <?php if ($member['occupation'] === 'student' && $member['school_name'] !== null && $member['school_name'] !== ''): ?>
                  &middot; <?= e($member['school_name']) ?>
                <?php endif; ?>
              </div>
              <div class="mt-0.5 flex items-center gap-1.5 text-[14px] leading-5 text-on-surface-variant">
                <i data-lucide="phone" class="h-4 w-4 shrink-0"></i>
                <?php if ((string) ($member['phone'] ?? '') !== ''): ?>
                  <a href="tel:<?= e($member['phone']) ?>" class="truncate hover:text-primary"><?= e($member['phone']) ?></a>
                <?php else: ?>
                  <span class="truncate">No phone on file</span>
                <?php endif; ?>
              </div>
              <?php if ($isSuper && $member['tent_name'] !== null): ?>
                <div class="mt-0.5 truncate text-[14px] leading-5 text-on-surface-variant"><?= e($member['tent_name']) ?></div>
              <?php endif; ?>
            </div>
            <a href="member-edit.php?id=<?= (int) $member['id'] ?>" aria-label="Edit <?= e($member['full_name']) ?>"
               class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full hover:bg-surface-container">
              <i data-lucide="pencil" class="h-5 w-5 text-on-surface-variant"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
