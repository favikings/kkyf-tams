<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$filters = $filters ?? [];
$query = (string) ($filters['query'] ?? '');
$action = (string) ($filters['action'] ?? '');
$entityType = (string) ($filters['entity_type'] ?? '');
$selectedUserId = (int) ($filters['user_id'] ?? 0);
?>

<section class="content-panel activity-log-hub" aria-labelledby="activity-log-title">
    <div class="directory-header">
        <div>
            <div class="eyebrow">Phase 14</div>
            <h1 id="activity-log-title">Activity Logs</h1>
            <p class="lede">Review member actions, attendance events, admin changes, and communication history across the portal.</p>
        </div>
    </div>

    <form class="directory-filter-card activity-log-filter-card" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/activity-logs">
        <label>
            <span>Search</span>
            <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Action, entity, actor...">
        </label>
        <label>
            <span>Action</span>
            <input type="search" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" placeholder="member.updated">
        </label>
        <label>
            <span>Entity Type</span>
            <input type="search" name="entity_type" value="<?= htmlspecialchars($entityType, ENT_QUOTES, 'UTF-8') ?>" placeholder="member, tent, sms">
        </label>
        <label>
            <span>Actor</span>
            <select name="user_id">
                <option value="">All admins</option>
                <?php foreach ($actors as $actor): ?>
                    <option value="<?= (int) $actor['id'] ?>" <?= $selectedUserId === (int) $actor['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $actor['full_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $actor['role'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit"><i data-lucide="search"></i> Filter Logs</button>
    </form>

    <div class="member-table-card">
        <div class="card-heading report-card-heading">
            <h2>Recent Audit Trail</h2>
            <span class="soft-filter">Latest <?= count($logs) ?> records</span>
        </div>

        <?php if ($logs === []): ?>
            <div class="empty-state">No activity logs found for the current filters.</div>
        <?php else: ?>
            <div class="member-table-scroll">
                <table class="member-table activity-log-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $metadata = (array) ($log['metadata_array'] ?? []);
                            $actorLabel = trim((string) ($log['actor_name'] ?? 'System'));
                            $actorRole = trim((string) ($log['actor_role'] ?? ''));
                            ?>
                            <tr>
                                <td data-label="When">
                                    <strong class="table-primary-text"><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td data-label="Actor">
                                    <strong class="table-primary-text"><?= htmlspecialchars($actorLabel !== '' ? $actorLabel : 'System', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars($actorRole, ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td data-label="Action">
                                    <span class="status-pill is-called activity-log-pill"><?= htmlspecialchars((string) ($log['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Entity">
                                    <strong class="table-primary-text"><?= htmlspecialchars((string) ($log['entity_type'] ?? 'system'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= htmlspecialchars((string) ($log['entity_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td data-label="Details">
                                    <?php if ($metadata === []): ?>
                                        <span class="muted">No extra metadata</span>
                                    <?php else: ?>
                                        <details class="activity-log-details">
                                            <summary>View metadata</summary>
                                            <div class="activity-log-meta-list">
                                                <?php foreach ($metadata as $key => $value): ?>
                                                    <div class="activity-log-meta-row">
                                                        <span><?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?></span>
                                                        <strong><?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]' : (string) $value, ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="member-table-footer">
                <span>Activity logs are append-only audit entries.</span>
                <span class="muted">Includes auth, member, tent, attendance, and SMS actions currently wired.</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
