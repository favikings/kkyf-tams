<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$checkedCount = (int) ($summary['total'] ?? 0);
$visibleCount = count($members);
$checkedVisible = count(array_filter($members, static fn (array $member): bool => !empty($member['attendance_id'])));
$capacityPercent = $visibleCount > 0 ? min(100, (int) round(($checkedVisible / $visibleCount) * 100)) : 0;
?>

<section class="content-panel attendance-checkin-v2" aria-labelledby="attendance-title">
    <div class="attendance-header-v2">
        <div>
            <h1 id="attendance-title">Attendance Check-in</h1>
            <div class="attendance-meta-row">
                <span><i data-lucide="church"></i> Sunday Service</span>
                <span><i data-lucide="calendar-days"></i> <?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?></span>
                <span><i data-lucide="clock"></i> Active session</span>
            </div>
        </div>
        <a class="secondary-button" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance/history"><i data-lucide="history"></i> View History</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="attendance-layout-v2">
        <div class="attendance-main-stack">
            <section class="attendance-control-card" aria-label="Attendance controls">
                <button class="scan-button is-disabled" type="button" disabled aria-disabled="true" title="QR and camera check-in are planned for a later phase"><i data-lucide="scan-qr-code"></i> Scan QR Code / Open Camera</button>
                <span class="control-divider"></span>
                <label class="toggle-control">
                    <input type="checkbox" disabled>
                    <span></span>
                    Retroactive Check-in
                </label>
                <div class="locked-date-control">
                    <i data-lucide="calendar"></i>
                    <?= htmlspecialchars($summary['attendance_date'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </section>

            <form class="attendance-filter-card" method="GET" action="attendance">
                <label>
                    <span>Search</span>
                    <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search members by name or phone">
                </label>
                <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
                    <label>
                        <span>Tent</span>
                        <select name="tent_id">
                            <option value="">All tents</option>
                            <?php foreach ($tents as $tent): ?>
                                <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <button type="submit"><i data-lucide="search"></i> Search</button>
            </form>

            <section class="checkin-list-card" aria-labelledby="quick-checkin-title">
                <div class="card-heading">
                    <h2 id="quick-checkin-title">Quick Check-in List</h2>
                    <span class="soft-filter"><i data-lucide="list-filter"></i> Filtered View</span>
                </div>

                <?php if ($members === []): ?>
                    <div class="empty-state">No active members match this attendance search.</div>
                <?php endif; ?>

                <?php if ($members !== []): ?>
                    <div class="checkin-table-wrap">
                        <table class="checkin-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>ID / Tent</th>
                                    <th>Phone</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <?php
                                    $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                                    $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                                    ?>
                                    <tr>
                                        <td data-label="Member">
                                            <div class="member-identity">
                                                <span class="member-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                                <div>
                                                    <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <small>Active member</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="ID / Tent">
                                            <strong class="table-primary-text">#KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                                            <small><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td data-label="Phone"><?= htmlspecialchars($member['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td data-label="Action">
                                            <?php if (!empty($member['attendance_id'])): ?>
                                                <span class="checked-state"><i data-lucide="check-circle"></i> Checked In</span>
                                            <?php else: ?>
                                                <form method="POST" action="attendance/check-in">
                                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>">
                                                    <button class="checkin-button" type="submit">Check In</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="attendance-side-stack">
            <section class="session-summary-card" aria-labelledby="session-summary-title">
                <h2 id="session-summary-title">Session Summary</h2>
                <div class="session-metric-grid">
                    <div>
                        <i data-lucide="user-check"></i>
                        <strong><?= number_format($checkedCount) ?></strong>
                        <span>Total Checked In</span>
                    </div>
                    <div>
                        <i data-lucide="users"></i>
                        <strong><?= number_format($visibleCount) ?></strong>
                        <span>Visible Members</span>
                    </div>
                </div>
                <div class="progress-meter">
                    <span style="width: <?= (int) $capacityPercent ?>%"></span>
                </div>
                <div class="progress-details">
                    <span>Filtered completion: <?= (int) $capacityPercent ?>%</span>
                    <strong><?= (int) $checkedVisible ?> / <?= (int) $visibleCount ?></strong>
                </div>
            </section>

            <section class="recent-checkins-card" aria-labelledby="recent-checkins-title">
                <div class="card-heading">
                    <h2 id="recent-checkins-title">Recent Check-ins</h2>
                    <span class="text-link">Live Feed</span>
                </div>
                <div class="stack-list">
                    <?php foreach (array_slice(array_filter($members, static fn (array $member): bool => !empty($member['attendance_id'])), 0, 4) as $member): ?>
                        <?php
                        $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                        $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                        ?>
                        <div class="mini-row">
                            <span class="mini-icon mini-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                            <div>
                                <strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?> · checked in</small>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($checkedVisible === 0): ?>
                        <div class="empty-state">No check-ins in this filtered list yet.</div>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
