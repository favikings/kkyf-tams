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
        'sent' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'simulated' => 'bg-sky-100 text-sky-700 ring-sky-200',
        'failed' => 'bg-rose-100 text-rose-700 ring-rose-200',
        default => 'bg-amber-100 text-amber-700 ring-amber-200',
    };
};
$cardClass = 'rounded-3xl border border-slate-200/70 bg-white/95 p-5 shadow-sm shadow-slate-200/40 sm:p-6';
$fieldClass = 'mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
$buttonClass = 'inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100';
$isLive = !empty($smsMode['is_live']);
$totalRecipients = count($members);
$totalTents = count($tents);
$sentCount = 0;
$failedCount = 0;
$simulatedCount = 0;

foreach ($logs as $log) {
    $status = (string) ($log['status'] ?? '');
    if ($status === 'sent') {
        $sentCount++;
    } elseif ($status === 'failed') {
        $failedCount++;
    } elseif ($status === 'simulated') {
        $simulatedCount++;
    }
}
?>

<section class="space-y-6" aria-labelledby="sms-title">
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(135deg,_rgba(255,255,255,0.98),_rgba(240,253,244,0.96))] shadow-sm shadow-emerald-100/50">
        <div class="flex flex-col gap-6 px-5 py-6 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:px-8 lg:py-8">
            <div class="max-w-3xl space-y-4">
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-white/85 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">
                    Phase 10 Communication
                </span>
                <div class="space-y-3">
                    <h1 id="sms-title" class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                        SMS communication hub
                    </h1>
                    <p class="max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                        Send single-member, tent-wide, or full-portal updates from one place with clear recipient controls and recent delivery visibility.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-slate-600 sm:text-sm">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2">
                        <span class="h-2.5 w-2.5 rounded-full <?= $isLive ? 'bg-emerald-500' : 'bg-amber-400' ?>"></span>
                        <?= htmlspecialchars((string) $smsMode['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2">
                        <i data-lucide="users" class="h-4 w-4 text-emerald-600"></i>
                        <?= $totalRecipients ?> member contacts
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2">
                        <i data-lucide="waypoints" class="h-4 w-4 text-emerald-600"></i>
                        <?= $totalTents ?> tent groups
                    </span>
                </div>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-3 lg:w-[22rem] lg:grid-cols-1">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-emerald-100/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Sent</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $sentCount ?></p>
                    <p class="mt-1 text-xs text-slate-500">Successful logged deliveries</p>
                </div>
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-slate-200/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Simulation</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $simulatedCount ?></p>
                    <p class="mt-1 text-xs text-slate-500">Safe log-only sends</p>
                </div>
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-rose-100/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Failed</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $failedCount ?></p>
                    <p class="mt-1 text-xs text-slate-500">Messages needing follow-up</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="rounded-3xl border px-5 py-4 shadow-sm <?= $isLive ? 'border-emerald-200 bg-emerald-50/80 shadow-emerald-100/40' : 'border-amber-200 bg-amber-50/80 shadow-amber-100/40' ?>">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) $smsMode['label'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) $smsMode['message'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] <?= $isLive ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                <span class="h-2 w-2 rounded-full <?= $isLive ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                <?= $isLive ? 'Live provider' : 'Simulation only' ?>
            </span>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.9fr)]">
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="<?= $cardClass ?>">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Send to one member</h2>
                        <p class="mt-1 text-sm text-slate-500">Use this for personal reminders, welfare follow-up, or attendance nudges.</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <?= $selectedScope === 'member' ? 'Selected' : $totalRecipients . ' contacts' ?>
                    </span>
                </div>

                <form class="mt-6 space-y-5" method="POST" action="sms/send">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="scope" value="member">

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Member</span>
                        <select name="member_id" class="<?= $fieldClass ?>" required>
                            <option value="">Choose member</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int) $member['id'] ?>" <?= $selectedMemberId === (int) $member['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($member['full_name'] . ' · ' . $member['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Message</span>
                        <textarea name="message" rows="5" maxlength="480" class="<?= $fieldClass ?> min-h-[9rem] resize-y" placeholder="Type a short member update..." required><?= htmlspecialchars($selectedScope === 'member' ? $prefilledMessage : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <span class="mt-2 block text-xs text-slate-400">Keep it short and clear. Maximum 480 characters.</span>
                    </label>

                    <button type="submit" class="<?= $buttonClass ?>">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Send member SMS
                    </button>
                </form>
            </section>

            <section class="<?= $cardClass ?>">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Send to one tent</h2>
                        <p class="mt-1 text-sm text-slate-500">Broadcast to everyone in a tent for service reminders and coordinated updates.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        <?= $selectedScope === 'tent' ? 'Selected' : $totalTents . ' tents' ?>
                    </span>
                </div>

                <form class="mt-6 space-y-5" method="POST" action="sms/send">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="scope" value="tent">

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Tent</span>
                        <select name="tent_id" class="<?= $fieldClass ?>" required>
                            <option value="">Choose tent</option>
                            <?php foreach ($tents as $tent): ?>
                                <option value="<?= (int) $tent['id'] ?>" <?= $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tent['name'] . ' · ' . (int) $tent['sms_member_count'] . ' contacts', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Message</span>
                        <textarea name="message" rows="5" maxlength="480" class="<?= $fieldClass ?> min-h-[9rem] resize-y" placeholder="Type a tent-wide reminder..." required></textarea>
                        <span class="mt-2 block text-xs text-slate-400">Use this for a single tent audience only.</span>
                    </label>

                    <button type="submit" class="<?= $buttonClass ?>">
                        <i data-lucide="messages-square" class="h-4 w-4"></i>
                        Send tent SMS
                    </button>
                </form>
            </section>

            <?php if ($isSuperAdmin): ?>
                <section class="<?= $cardClass ?> lg:col-span-2">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-2xl">
                            <h2 class="text-lg font-semibold text-slate-900">Bulk SMS</h2>
                            <p class="mt-1 text-sm text-slate-500">Portal-wide announcements for every valid contact in scope. Reserved for Super Admin.</p>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-white">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                            Super Admin only
                        </span>
                    </div>

                    <form class="mt-6 space-y-5" method="POST" action="sms/send">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="scope" value="bulk">

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Message</span>
                            <textarea name="message" rows="6" maxlength="480" class="<?= $fieldClass ?> min-h-[10rem] resize-y" placeholder="Type a portal-wide announcement..." required></textarea>
                            <span class="mt-2 block text-xs text-slate-400">Use sparingly. This reaches the broadest audience available to the platform.</span>
                        </label>

                        <button type="submit" class="<?= $buttonClass ?>">
                            <i data-lucide="megaphone" class="h-4 w-4"></i>
                            Send bulk SMS
                        </button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <section class="<?= $cardClass ?> h-fit" aria-labelledby="sms-log-title">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="sms-log-title" class="text-lg font-semibold text-slate-900">Recent SMS logs</h2>
                    <p class="mt-1 text-sm text-slate-500">The latest 20 send attempts across member, tent, and bulk activity.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Latest 20</span>
            </div>

            <?php if ($logs === []): ?>
                <div class="mt-6 rounded-3xl border border-dashed border-slate-200 bg-slate-50/80 px-6 py-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm shadow-slate-200/50">
                        <i data-lucide="messages-square" class="h-6 w-6"></i>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">No SMS logs yet</h3>
                    <p class="mt-2 text-sm text-slate-500">Once messages are sent or simulated, they will appear here with recipient and status details.</p>
                </div>
            <?php else: ?>
                <div class="mt-6 space-y-4">
                    <?php foreach ($logs as $log): ?>
                        <article class="rounded-3xl border border-slate-200/70 bg-slate-50/70 p-4 shadow-sm shadow-slate-200/30">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                            <?= htmlspecialchars($modeLabel((string) $log['scope']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 <?= $statusClass((string) $log['status']) ?>">
                                            <?= htmlspecialchars(ucfirst((string) $log['status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <div class="text-sm text-slate-600">
                                        <span class="font-medium text-slate-800"><?= htmlspecialchars((string) $log['actor_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($log['member_name'])): ?>
                                            <span> · <?= htmlspecialchars((string) $log['member_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php elseif (!empty($log['tent_name'])): ?>
                                            <span> · <?= htmlspecialchars((string) $log['tent_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <span> · <?= (int) $log['recipient_count'] ?> recipient(s)</span>
                                    </div>

                                    <p class="text-sm leading-6 text-slate-500">
                                        <?= nl2br(htmlspecialchars((string) $log['message'], ENT_QUOTES, 'UTF-8')) ?>
                                    </p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
