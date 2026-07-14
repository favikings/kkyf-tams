<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$todayTotal = (int) ($summary['today_total'] ?? 0);
$nextSevenTotal = (int) ($summary['next_7_days_total'] ?? 0);
$nextThirtyTotal = (int) ($summary['next_30_days_total'] ?? 0);
?>

<section class="content-panel birthday-hub" aria-labelledby="birthday-title">
    <div class="directory-header">
        <div>
            <div class="eyebrow">Celebrations</div>
            <h1 id="birthday-title">Upcoming Birthdays</h1>
            <p class="lede">Track who is celebrating soon and jump straight into a birthday message when needed.</p>
        </div>
        <div class="directory-actions">
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/anniversaries"><i data-lucide="party-popper"></i> Anniversaries</a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($todayTotal > 0): ?>
        <div class="notice birthday-highlight-banner" role="status">
            <i data-lucide="cake"></i>
            <?= $todayTotal === 1 ? '1 member is celebrating today.' : number_format($todayTotal) . ' members are celebrating today.' ?>
        </div>
    <?php endif; ?>

    <div class="birthday-summary-grid">
        <article class="birthday-summary-card <?= $todayTotal > 0 ? 'birthday-summary-card-feature' : '' ?>">
            <span>Today</span>
            <strong><?= number_format($todayTotal) ?></strong>
            <small>Members celebrating today</small>
        </article>
        <article class="birthday-summary-card">
            <span>Next 7 Days</span>
            <strong><?= number_format($nextSevenTotal) ?></strong>
            <small>Immediate celebration window</small>
        </article>
        <article class="birthday-summary-card">
            <span>Next 30 Days</span>
            <strong><?= number_format($nextThirtyTotal) ?></strong>
            <small>Full planning window</small>
        </article>
    </div>

    <form class="directory-filter-card birthday-filter-card" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/birthdays">
        <label>
            <span>Birthday Window</span>
            <select name="days">
                <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Next 7 days</option>
                <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Next 30 days</option>
            </select>
        </label>
        <div class="birthday-filter-note">
            <strong><?= $days === 7 ? 'Short window' : 'Extended window' ?></strong>
            <small><?= $days === 7 ? 'Best for immediate outreach.' : 'Useful for monthly planning and celebrations.' ?></small>
        </div>
        <button type="submit"><i data-lucide="calendar-heart"></i> Update View</button>
    </form>

    <div class="member-table-card">
        <?php if ($birthdays === []): ?>
            <div class="empty-state">
                No active birthdays fall inside this window.
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">Review member records</a>
            </div>
        <?php else: ?>
            <div class="member-table-scroll">
                <table class="member-table birthday-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Tent</th>
                            <th>Birthday</th>
                            <th>Countdown</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($birthdays as $member): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            $birthdayMessage = 'Happy Birthday, ' . trim((string) $member['full_name']) . '! Wishing you joy, grace, and a blessed new year ahead from KKYF.';
                            ?>
                            <tr class="<?= !empty($member['is_today_birthday']) ? 'birthday-row-today' : '' ?>">
                                <td data-label="Member">
                                    <div class="member-identity">
                                        <span class="member-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div>
                                            <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small>KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="member-mobile-detail" data-label="Tent">
                                    <strong class="table-primary-text"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="member-mobile-detail" data-label="Birthday">
                                    <strong class="table-primary-text"><?= htmlspecialchars((string) $member['birthday_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= !empty($member['phone']) ? htmlspecialchars((string) $member['phone'], ENT_QUOTES, 'UTF-8') : 'No phone saved' ?></small>
                                </td>
                                <td class="member-mobile-detail" data-label="Countdown">
                                    <span class="status-pill <?= !empty($member['is_today_birthday']) ? 'is-active' : 'is-called' ?>">
                                        <?= !empty($member['is_today_birthday']) ? 'Today' : 'In ' . (int) $member['days_until_birthday'] . ' day' . ((int) $member['days_until_birthday'] === 1 ? '' : 's') ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <a class="icon-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if (!empty($member['phone'])): ?>
                                            <a
                                                class="icon-button"
                                                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=member&amp;member_id=<?= (int) $member['id'] ?>&amp;message=<?= urlencode($birthdayMessage) ?>"
                                                aria-label="Send birthday SMS to <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <i data-lucide="messages-square"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="icon-button is-disabled-inline" title="No phone saved for birthday SMS" aria-hidden="true">
                                                <i data-lucide="phone-off"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="member-table-footer">
                <span>Showing <?= count($birthdays) ?> birthday reminder<?= count($birthdays) === 1 ? '' : 's' ?></span>
                <span class="muted">Only active members inside your visible scope are shown</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
