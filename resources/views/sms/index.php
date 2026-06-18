<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$isSuperAdmin = ($user['role'] ?? null) === 'Super Admin';
$selectedScope = $selectedScope ?? 'member';
$selectedMemberId = (int) ($selectedMemberId ?? 0);
$selectedTentId = (int) ($selectedTentId ?? 0);
$prefilledMessage = (string) ($prefilledMessage ?? '');
$smsMode = $smsMode ?? ['is_live' => false, 'label' => 'Simulation Mode', 'message' => 'SMS logs only.'];
$modeLabel = static function (string $scope): string {
    return match ($scope) {
        'member' => 'Single Member',
        'tent' => 'Tent Broadcast',
        'bulk' => 'Bulk SMS',
        default => 'SMS',
    };
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'sent' => 'is-active',
        'simulated' => 'is-called',
        'failed' => 'is-not-returning',
        default => 'is-pending',
    };
};
?>

<section class="content-panel sms-hub" aria-labelledby="sms-title">
    <div class="directory-header">
        <div>
            <div class="eyebrow">Phase 10</div>
            <h1 id="sms-title">SMS Communication</h1>
            <p class="lede">Send one-to-one, tent-wide, or bulk updates with a safe log-first workflow.</p>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="notice" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="status-banner <?= !empty($smsMode['is_live']) ? 'is-live' : 'is-pending' ?>">
        <strong><?= htmlspecialchars((string) $smsMode['label'], ENT_QUOTES, 'UTF-8') ?></strong>
        <span><?= htmlspecialchars((string) $smsMode['message'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="sms-grid">
        <section class="dashboard-card">
            <div class="card-heading">
                <h2>Send to One Member</h2>
                <span class="soft-filter"><?= $selectedScope === 'member' ? 'Selected' : count($members) . ' with phones' ?></span>
            </div>
            <form class="management-form" method="POST" action="sms/send">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="scope" value="member">
                <label>
                    <span>Member</span>
                    <select name="member_id" required>
                        <option value="">Choose member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['id'] ?>" <?= $selectedMemberId === (int) $member['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($member['full_name'] . ' · ' . $member['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Message</span>
                    <textarea name="message" rows="5" maxlength="480" placeholder="Type a short member update..." required><?= htmlspecialchars($selectedScope === 'member' ? $prefilledMessage : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </label>
                <button type="submit"><i data-lucide="send"></i> Send Member SMS</button>
            </form>
        </section>

        <section class="dashboard-card">
            <div class="card-heading">
                <h2>Send to One Tent</h2>
                <span class="soft-filter"><?= $selectedScope === 'tent' ? 'Selected' : count($tents) . ' tent groups' ?></span>
            </div>
            <form class="management-form" method="POST" action="sms/send">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="scope" value="tent">
                <label>
                    <span>Tent</span>
                    <select name="tent_id" required>
                        <option value="">Choose tent</option>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>" <?= $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tent['name'] . ' · ' . (int) $tent['sms_member_count'] . ' contacts', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Message</span>
                    <textarea name="message" rows="5" maxlength="480" placeholder="Type a tent-wide reminder..." required></textarea>
                </label>
                <button type="submit"><i data-lucide="messages-square"></i> Send Tent SMS</button>
            </form>
        </section>

        <?php if ($isSuperAdmin): ?>
            <section class="dashboard-card">
                <div class="card-heading">
                    <h2>Bulk SMS</h2>
                    <span class="soft-filter"><?= $selectedScope === 'bulk' ? 'Selected' : 'Super Admin' ?></span>
                </div>
                <form class="management-form" method="POST" action="sms/send">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="scope" value="bulk">
                    <label>
                        <span>Message</span>
                        <textarea name="message" rows="6" maxlength="480" placeholder="Type a portal-wide announcement..." required></textarea>
                    </label>
                    <button type="submit"><i data-lucide="megaphone"></i> Send Bulk SMS</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="dashboard-card" aria-labelledby="sms-log-title">
            <div class="card-heading">
                <h2 id="sms-log-title">Recent SMS Logs</h2>
                <span class="soft-filter">Latest 20</span>
            </div>

            <?php if ($logs === []): ?>
                <div class="empty-state">No SMS logs yet.</div>
            <?php else: ?>
                <div class="stack-list">
                    <?php foreach ($logs as $log): ?>
                        <div class="alert-member-row sms-log-row">
                            <div>
                                <strong><?= htmlspecialchars($modeLabel((string) $log['scope']), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small>
                                    <?= htmlspecialchars((string) $log['actor_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($log['member_name'])): ?>
                                        · <?= htmlspecialchars((string) $log['member_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php elseif (!empty($log['tent_name'])): ?>
                                        · <?= htmlspecialchars((string) $log['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                    · <?= (int) $log['recipient_count'] ?> recipient(s)
                                </small>
                                <small><?= htmlspecialchars((string) $log['message'], ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <span class="status-pill <?= $statusClass((string) $log['status']) ?>">
                                <?= htmlspecialchars(ucfirst((string) $log['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
