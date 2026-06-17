<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$todayTotal = (int) ($summary['today_total'] ?? 0);
$nextSevenTotal = (int) ($summary['next_7_days_total'] ?? 0);
$nextThirtyTotal = (int) ($summary['next_30_days_total'] ?? 0);
?>

<section class="content-panel birthday-hub anniversary-hub" aria-labelledby="anniversary-title">
    <div class="directory-header">
        <div>
            <div class="eyebrow">Milestones</div>
            <h1 id="anniversary-title">Upcoming Anniversaries</h1>
            <p class="lede">See who is marking another year in KKYF and send a quick congratulations message when it matters.</p>
        </div>
        <div class="directory-actions">
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/birthdays"><i data-lucide="cake"></i> Birthdays</a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard"><i data-lucide="layout-dashboard"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($todayTotal > 0): ?>
        <div class="notice anniversary-highlight-banner" role="status">
            <i data-lucide="party-popper"></i>
            <?= $todayTotal === 1 ? '1 member is marking an anniversary today.' : number_format($todayTotal) . ' members are marking anniversaries today.' ?>
        </div>
    <?php endif; ?>

    <div class="birthday-summary-grid">
        <article class="birthday-summary-card <?= $todayTotal > 0 ? 'anniversary-summary-card-feature' : '' ?>">
            <span>Today</span>
            <strong><?= number_format($todayTotal) ?></strong>
            <small>Anniversaries happening today</small>
        </article>
        <article class="birthday-summary-card">
            <span>Next 7 Days</span>
            <strong><?= number_format($nextSevenTotal) ?></strong>
            <small>Immediate milestone window</small>
        </article>
        <article class="birthday-summary-card">
            <span>Next 30 Days</span>
            <strong><?= number_format($nextThirtyTotal) ?></strong>
            <small>Extended planning view</small>
        </article>
    </div>

    <form class="directory-filter-card birthday-filter-card" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/anniversaries">
        <label>
            <span>Anniversary Window</span>
            <select name="days">
                <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Next 7 days</option>
                <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Next 30 days</option>
            </select>
        </label>
        <div class="birthday-filter-note">
            <strong><?= $days === 7 ? 'Short window' : 'Extended window' ?></strong>
            <small><?= $days === 7 ? 'Best for quick recognition.' : 'Useful for planning monthly milestone outreach.' ?></small>
        </div>
        <button type="submit"><i data-lucide="calendar-range"></i> Update View</button>
    </form>

    <div class="member-table-card">
        <?php if ($anniversaries === []): ?>
            <div class="empty-state">
                No active anniversaries fall inside this window.
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">Review join dates</a>
            </div>
        <?php else: ?>
            <div class="member-table-scroll">
                <table class="member-table anniversary-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Tent</th>
                            <th>Anniversary</th>
                            <th>Milestone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anniversaries as $member): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            $message = 'Congratulations, ' . trim((string) $member['full_name']) . '! Happy ' . (int) $member['celebrating_years'] . ' year anniversary in KKYF. We celebrate your journey and growth with us.';
                            ?>
                            <tr class="<?= !empty($member['is_today_anniversary']) ? 'anniversary-row-today' : '' ?>">
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
                                <td class="member-mobile-detail" data-label="Anniversary">
                                    <strong class="table-primary-text"><?= htmlspecialchars((string) $member['anniversary_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small>Joined <?= htmlspecialchars((string) $member['join_date'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="member-mobile-detail" data-label="Milestone">
                                    <span class="status-pill <?= !empty($member['is_today_anniversary']) ? 'is-active' : 'is-called' ?>">
                                        <?= !empty($member['is_today_anniversary']) ? 'Today' : 'In ' . (int) $member['days_until_anniversary'] . ' day' . ((int) $member['days_until_anniversary'] === 1 ? '' : 's') ?>
                                    </span>
                                    <small class="anniversary-milestone-copy"><?= (int) $member['celebrating_years'] ?> year<?= (int) $member['celebrating_years'] === 1 ? '' : 's' ?> in KKYF</small>
                                </td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <a class="icon-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if (!empty($member['phone'])): ?>
                                            <a
                                                class="icon-button"
                                                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=member&amp;member_id=<?= (int) $member['id'] ?>&amp;message=<?= urlencode($message) ?>"
                                                aria-label="Send anniversary SMS to <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <i data-lucide="messages-square"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="icon-button is-disabled-inline" title="No phone saved for anniversary SMS" aria-hidden="true">
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
                <span>Showing <?= count($anniversaries) ?> anniversary reminder<?= count($anniversaries) === 1 ? '' : 's' ?></span>
                <span class="muted">Only active members with join dates inside your visible scope are shown</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
