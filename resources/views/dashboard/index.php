<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<section class="content-panel dashboard-panel" aria-labelledby="dashboard-title">
    <div class="dashboard-hero">
        <div>
            <div class="eyebrow"><?= ($user['role'] ?? null) === 'Super Admin' ? 'Super Admin' : 'Tent Admin' ?></div>
            <h1 id="dashboard-title">Dashboard</h1>
            <p class="lede">
                Welcome, <?= htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>.
                Track members, tents, and Sunday attendance from one clean view.
            </p>
        </div>

        <div class="dashboard-actions">
            <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="clipboard-check"></i> Take Attendance</a>
            <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members"><i data-lucide="users"></i> Members</a>
        </div>
    </div>

    <div class="metric-grid">
        <?php foreach ($metrics['cards'] as $card): ?>
            <article class="metric-card <?= $card['tone'] === 'primary' ? 'metric-card-primary' : '' ?>">
                <div class="metric-card-top">
                    <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <i data-lucide="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
                <strong><?= (int) $card['value'] ?></strong>
                <small><?= $card['tone'] === 'primary' ? 'Primary scope metric' : 'Updated from current v2 records' ?></small>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-grid">
        <section class="dashboard-card" aria-labelledby="recent-members-title">
            <div class="card-heading">
                <h2 id="recent-members-title">Recent Members</h2>
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">View all</a>
            </div>

            <div class="stack-list">
                <?php if ($metrics['recent_members'] === []): ?>
                    <div class="empty-state">No recent members yet.</div>
                <?php endif; ?>

                <?php foreach ($metrics['recent_members'] as $member): ?>
                    <?php
                    $memberParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $memberInitials = strtoupper(substr($memberParts[0] ?? 'M', 0, 1) . substr($memberParts[1] ?? '', 0, 1));
                    ?>
                    <div class="mini-row">
                        <span class="mini-icon mini-avatar"><?= htmlspecialchars($memberInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card" aria-labelledby="recent-attendance-title">
            <div class="card-heading">
                <h2 id="recent-attendance-title">Recent Attendance</h2>
                <a class="text-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history">History</a>
            </div>

            <div class="stack-list">
                <?php if ($metrics['recent_attendance'] === []): ?>
                    <div class="empty-state">No attendance records yet.</div>
                <?php endif; ?>

                <?php foreach ($metrics['recent_attendance'] as $record): ?>
                    <?php
                    $attendanceParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $attendanceInitials = strtoupper(substr($attendanceParts[0] ?? 'M', 0, 1) . substr($attendanceParts[1] ?? '', 0, 1));
                    ?>
                    <div class="mini-row">
                        <span class="mini-icon mini-avatar"><?= htmlspecialchars($attendanceInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <strong><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($record['attendance_date'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="dashboard-card dashboard-card-accent" aria-labelledby="quick-actions-title">
            <div class="card-heading">
                <h2 id="quick-actions-title">Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
                    <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/tents"><i data-lucide="tent"></i> Manage Tents</a>
                <?php else: ?>
                    <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/my-tent"><i data-lucide="map-pin"></i> My Tent</a>
                <?php endif; ?>
                <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members"><i data-lucide="user-plus"></i> Add Member</a>
                <a class="as-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance"><i data-lucide="calendar-check"></i> Check In</a>
            </div>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
