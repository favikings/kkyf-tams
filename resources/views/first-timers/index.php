<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$statusClass = static function (string $status): string {
    return match ($status) {
        'Pending' => 'is-pending',
        'Called' => 'is-called',
        'Converted' => 'is-converted',
        'Not Returning' => 'is-not-returning',
        default => 'is-inactive',
    };
};
?>

<section class="content-panel first-timer-directory" aria-labelledby="first-timers-title">
    <div class="directory-header">
        <div>
            <h1 id="first-timers-title">First-Timer Follow-up</h1>
            <p class="lede">Track new visitors, update follow-up progress, and convert ready records into full members.</p>
        </div>
        <div class="directory-actions">
            <button class="secondary-button is-disabled" type="button" disabled aria-disabled="true" title="CSV export arrives in Phase 12."><i data-lucide="download"></i> Export CSV</button>
            <button type="button" data-modal-open="add-first-timer-modal"><i data-lucide="user-plus"></i> Add First-Timer</button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="directory-filter-card" method="GET" action="first-timers">
        <label>
            <span>Search</span>
            <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search names, phones...">
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label>
                <span>Tent Location</span>
                <select name="tent_id">
                    <option value="">All Tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach (['Pending', 'Called', 'Converted', 'Not Returning'] as $status): ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit"><i data-lucide="search"></i> Search</button>
    </form>

    <div class="member-table-card">
        <?php if ($firstTimers === []): ?>
            <div class="empty-state">No first-timer records match this view yet.</div>
        <?php endif; ?>

        <?php if ($firstTimers !== []): ?>
            <div class="member-table-scroll">
                <table class="member-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>First Visit</th>
                            <th>Tent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firstTimers as $record): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td data-label="First-Timer">
                                    <div class="member-identity">
                                        <span class="member-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div>
                                            <strong><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small>FT-<?= str_pad((string) (int) $record['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="member-mobile-detail" data-label="Contact">
                                    <strong class="table-primary-text"><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= !empty($record['converted_member_name']) ? 'Converted to ' . $record['converted_member_name'] : 'Follow-up record' ?></small>
                                </td>
                                <td class="member-mobile-detail" data-label="First Visit"><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="member-mobile-detail" data-label="Tent"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="member-mobile-detail" data-label="Status">
                                    <span class="status-pill <?= $statusClass((string) $record['status']) ?>">
                                        <?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <a class="icon-button" href="first-timers/show?id=<?= (int) $record['id'] ?>" aria-label="View <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if (($record['status'] ?? '') !== 'Converted'): ?>
                                            <a class="icon-button" href="first-timers/show?id=<?= (int) $record['id'] ?>#convert-first-timer" aria-label="Convert <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="refresh-cw"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="member-table-footer">
                <span>Showing <?= count($firstTimers) ?> first-timer records</span>
                <span class="muted">Scoped to your current KKYF access level</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-backdrop" data-modal="add-first-timer-modal" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="add-first-timer-title">
        <div class="modal-header">
            <div>
                <div class="eyebrow">New Visitor</div>
                <h2 id="add-first-timer-title">Add a first-timer</h2>
            </div>
            <button class="icon-button" type="button" data-modal-close aria-label="Close add first-timer form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="management-form modal-form" method="POST" action="first-timers/create">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
                <label>
                    <span>Full Name</span>
                    <input type="text" name="full_name" required>
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone">
                </label>
                <label>
                    <span>First Visit Date</span>
                    <input type="date" name="first_visit_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="Pending">Pending</option>
                        <option value="Called">Called</option>
                        <option value="Not Returning">Not Returning</option>
                    </select>
                </label>
                <label class="span-2">
                    <span>Tent</span>
                    <select name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>">
                                <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-2">
                    <span>Follow-up Notes</span>
                    <textarea name="followup_notes" rows="4" placeholder="Add outreach notes, referral details, or next action."></textarea>
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-modal-close>Cancel</button>
                <button type="submit"><i data-lucide="user-plus"></i> Add First-Timer</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
