<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

requireSuperAdmin();

$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = (string) ($_POST['action'] ?? '');
    $targetId = (int) ($_POST['user_id'] ?? 0);

    if ($targetId > 0) {
        switch ($action) {
            case 'approve':
                $stmt = db()->prepare(
                    "UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW()
                     WHERE id = ? AND role = 'tent_admin' AND status = 'pending'"
                );
                $stmt->execute([$me['id'], $targetId]);
                flash('success', 'Registration approved.');
                break;

            case 'reject':
                $stmt = db()->prepare(
                    "UPDATE users SET status = 'rejected'
                     WHERE id = ? AND role = 'tent_admin' AND status = 'pending'"
                );
                $stmt->execute([$targetId]);
                flash('success', 'Registration rejected.');
                break;

            case 'deactivate':
                $stmt = db()->prepare(
                    "UPDATE users SET is_active = 0
                     WHERE id = ? AND role = 'tent_admin' AND status = 'approved'"
                );
                $stmt->execute([$targetId]);
                flash('success', 'Tent admin deactivated.');
                break;

            case 'activate':
                $stmt = db()->prepare(
                    "UPDATE users SET is_active = 1
                     WHERE id = ? AND role = 'tent_admin' AND status = 'approved'"
                );
                $stmt->execute([$targetId]);
                flash('success', 'Tent admin reactivated.');
                break;

            case 'promote':
                $stmt = db()->prepare(
                    "UPDATE users SET role = 'super_admin', tent_id = NULL, promoted_by = ?, promoted_at = NOW()
                     WHERE id = ? AND role = 'tent_admin' AND status = 'approved'"
                );
                $stmt->execute([$me['id'], $targetId]);
                flash('success', 'Promoted to Super Admin.');
                break;
        }
    }

    redirect('tent-admins.php');
}

$pendingStmt = db()->prepare(
    "SELECT u.id, u.name, u.email, u.phone, u.created_at, t.name AS tent_name
     FROM users u
     LEFT JOIN tents t ON t.id = u.tent_id
     WHERE u.role = 'tent_admin' AND u.status = 'pending'
     ORDER BY u.created_at ASC"
);
$pendingStmt->execute();
$pending = $pendingStmt->fetchAll();

$approvedStmt = db()->prepare(
    "SELECT u.id, u.name, u.email, u.phone, u.is_active, u.tent_id, t.name AS tent_name
     FROM users u
     LEFT JOIN tents t ON t.id = u.tent_id
     WHERE u.role = 'tent_admin' AND u.status = 'approved'
     ORDER BY t.name ASC, u.name ASC"
);
$approvedStmt->execute();

$adminsByTent = [];
foreach ($approvedStmt->fetchAll() as $row) {
    $tentId = $row['tent_id'] !== null ? (int) $row['tent_id'] : 0;
    $adminsByTent[$tentId]['name'] = $row['tent_name'] ?? 'No tent';
    $adminsByTent[$tentId]['admins'][] = $row;
}

$buttonBase = 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-md text-[14px] leading-5 font-semibold font-display tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform';
$primaryButton = $buttonBase . ' bg-primary text-on-primary shadow-card';
$secondaryButton = $buttonBase . ' bg-surface-container text-on-surface border border-outline-variant';
$destructiveButton = $buttonBase . ' bg-error text-on-error';

$pageTitle = 'Tent Admins';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-5xl">
  <header class="mb-6 md:mb-8">
    <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Tent Admins</h1>
    <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Approve, manage, and promote tent admin accounts.</p>
  </header>

  <section>
    <h2 class="mb-4 font-display text-[20px] leading-7 font-semibold text-on-surface">Pending Requests</h2>

    <?php if ($pending === []): ?>
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="inbox" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">No pending registration requests.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($pending as $row): ?>
          <div class="bg-surface-lowest rounded-lg p-4 shadow-card">
            <div class="flex flex-col gap-4 md:flex-row md:items-center">
              <div class="min-w-0 flex-1">
                <div class="truncate font-display text-[20px] leading-7 font-semibold text-on-surface"><?= e($row['name']) ?></div>
                <div class="text-[14px] leading-5 text-on-surface-variant">
                  <?= e($row['email']) ?> · <?= e($row['phone']) ?> · Tent: <?= e($row['tent_name'] ?? '—') ?>
                </div>
                <div class="mt-0.5 text-[12px] leading-4 text-on-surface-variant/70">
                  Requested <?= e(date('M j, Y g:ia', strtotime($row['created_at']))) ?>
                </div>
              </div>
              <form method="post" action="tent-admins.php" class="flex flex-wrap items-center gap-2 shrink-0">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                <button type="submit" name="action" value="approve" class="<?= $primaryButton ?>">Approve</button>
                <button type="submit" name="action" value="reject"
                  onclick="return confirm('Reject this registration request?')"
                  class="<?= $destructiveButton ?>">Reject</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mt-10">
    <h2 class="mb-4 font-display text-[20px] leading-7 font-semibold text-on-surface">Approved Admins</h2>

    <?php if ($adminsByTent === []): ?>
      <div class="bg-surface-lowest rounded-lg px-6 py-12 text-center shadow-card">
        <i data-lucide="users" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">No approved tent admins yet.</p>
      </div>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($adminsByTent as $group): ?>
          <div>
            <h3 class="mb-3 font-display text-[14px] leading-5 font-semibold tracking-[0.04em] uppercase text-on-surface-variant">
              <?= e($group['name']) ?> · <?= count($group['admins']) ?> admin<?= count($group['admins']) === 1 ? '' : 's' ?>
            </h3>
            <div class="space-y-3">
              <?php foreach ($group['admins'] as $row): ?>
                <div class="bg-surface-lowest rounded-lg p-4 shadow-card">
                  <div class="flex flex-col gap-4 md:flex-row md:items-center">
                    <div class="min-w-0 flex-1">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="truncate font-display text-[20px] leading-7 font-semibold text-on-surface"><?= e($row['name']) ?></span>
                        <?php if ((int) $row['is_active'] === 1): ?>
                          <span class="inline-flex items-center rounded-full bg-primary-container px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-primary-container">Active</span>
                        <?php else: ?>
                          <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 font-display text-[12px] leading-4 font-medium tracking-[0.04em] text-on-surface-variant">Inactive</span>
                        <?php endif; ?>
                      </div>
                      <div class="text-[14px] leading-5 text-on-surface-variant">
                        <?= e($row['email']) ?> · <?= e($row['phone']) ?>
                      </div>
                    </div>
                    <form method="post" action="tent-admins.php" class="flex flex-wrap items-center gap-2 shrink-0">
                      <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                      <?php if ((int) $row['is_active'] === 1): ?>
                        <button type="submit" name="action" value="deactivate"
                          onclick="return confirm('Deactivate this Tent Admin? They will not be able to sign in.')"
                          class="<?= $destructiveButton ?>">Deactivate</button>
                      <?php else: ?>
                        <button type="submit" name="action" value="activate" class="<?= $secondaryButton ?>">Activate</button>
                      <?php endif; ?>
                      <button type="submit" name="action" value="promote"
                        onclick="return confirm('Promote this Tent Admin to Super Admin? This gives them full access to all tents.')"
                        class="<?= $secondaryButton ?>">Promote to Super Admin</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
