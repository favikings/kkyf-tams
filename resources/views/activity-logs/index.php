<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$filters = $filters ?? [];
$query = (string) ($filters['query'] ?? '');
$action = (string) ($filters['action'] ?? '');
$entityType = (string) ($filters['entity_type'] ?? '');
$selectedUserId = (int) ($filters['user_id'] ?? 0);
$totalLogs = count($logs);

$fieldClass = 'mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
$buttonClass = 'inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100';
$statusClass = static function (string $actionName): string {
    $actionName = strtolower($actionName);

    return match (true) {
        str_contains($actionName, 'login'), str_contains($actionName, 'logout'), str_contains($actionName, 'auth') => 'bg-sky-100 text-sky-700 ring-sky-200',
        str_contains($actionName, 'attendance'), str_contains($actionName, 'check') => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        str_contains($actionName, 'sms'), str_contains($actionName, 'message') => 'bg-violet-100 text-violet-700 ring-violet-200',
        str_contains($actionName, 'delete'), str_contains($actionName, 'deactivate'), str_contains($actionName, 'failed') => 'bg-rose-100 text-rose-700 ring-rose-200',
        default => 'bg-amber-100 text-amber-700 ring-amber-200',
    };
};
?>

<section class="space-y-6" aria-labelledby="activity-log-title">
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_32%),linear-gradient(135deg,_rgba(255,255,255,0.98),_rgba(240,253,244,0.96))] shadow-sm shadow-emerald-100/50">
        <div class="flex flex-col gap-6 px-5 py-6 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:px-8 lg:py-8">
            <div class="max-w-3xl space-y-4">
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-white/85 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">
                    Phase 14 Oversight
                </span>
                <div class="space-y-3">
                    <h1 id="activity-log-title" class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                        Activity logs
                    </h1>
                    <p class="max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                        Review login events, member changes, attendance actions, tent updates, and SMS operations from one audit timeline.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-slate-600 sm:text-sm">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2">
                        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                        Super Admin audit view
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/80 px-3 py-2">
                        <i data-lucide="history" class="h-4 w-4 text-emerald-600"></i>
                        Append-only log stream
                    </span>
                </div>
            </div>

            <div class="grid w-full gap-3 sm:grid-cols-3 lg:w-[22rem] lg:grid-cols-1">
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-emerald-100/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Visible logs</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= $totalLogs ?></p>
                    <p class="mt-1 text-xs text-slate-500">Current filtered result set</p>
                </div>
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-slate-200/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Actors</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= count($actors) ?></p>
                    <p class="mt-1 text-xs text-slate-500">Admins available in filter</p>
                </div>
                <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-sm shadow-slate-200/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Coverage</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">Auth+</p>
                    <p class="mt-1 text-xs text-slate-500">Members, tents, attendance, SMS</p>
                </div>
            </div>
        </div>
    </div>

    <form class="rounded-[2rem] border border-slate-200/70 bg-white/95 p-5 shadow-sm shadow-slate-200/40 sm:p-6" method="GET" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/activity-logs">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <label class="block flex-1">
                <span class="text-sm font-medium text-slate-700">Search</span>
                <input type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" class="<?= $fieldClass ?>" placeholder="Action, entity, actor...">
            </label>
            <label class="block flex-1">
                <span class="text-sm font-medium text-slate-700">Action</span>
                <input type="search" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="<?= $fieldClass ?>" placeholder="member.updated">
            </label>
            <label class="block flex-1">
                <span class="text-sm font-medium text-slate-700">Entity type</span>
                <input type="search" name="entity_type" value="<?= htmlspecialchars($entityType, ENT_QUOTES, 'UTF-8') ?>" class="<?= $fieldClass ?>" placeholder="member, tent, sms">
            </label>
            <label class="block flex-1">
                <span class="text-sm font-medium text-slate-700">Actor</span>
                <select name="user_id" class="<?= $fieldClass ?>">
                    <option value="">All admins</option>
                    <?php foreach ($actors as $actor): ?>
                        <option value="<?= (int) $actor['id'] ?>" <?= $selectedUserId === (int) $actor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $actor['full_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $actor['role'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="<?= $buttonClass ?>">
                <i data-lucide="search" class="h-4 w-4"></i>
                Filter logs
            </button>
        </div>
    </form>

    <section class="rounded-[2rem] border border-slate-200/70 bg-white/95 p-5 shadow-sm shadow-slate-200/40 sm:p-6" aria-labelledby="audit-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="audit-title" class="text-xl font-semibold text-slate-900">Recent audit trail</h2>
                <p class="mt-1 text-sm text-slate-500">Trace system activity with actor, entity, action, and expandable metadata.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                Latest <?= $totalLogs ?> records
            </span>
        </div>

        <?php if ($logs === []): ?>
            <div class="mt-6 rounded-3xl border border-dashed border-slate-200 bg-slate-50/80 px-6 py-10 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm shadow-slate-200/50">
                    <i data-lucide="file-search" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">No activity logs found</h3>
                <p class="mt-2 text-sm text-slate-500">Try widening the current filters or wait for new system actions to be recorded.</p>
            </div>
        <?php else: ?>
            <div class="mt-6 hidden overflow-hidden rounded-3xl border border-slate-200/80 xl:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                                <th class="px-5 py-4">When</th>
                                <th class="px-5 py-4">Actor</th>
                                <th class="px-5 py-4">Action</th>
                                <th class="px-5 py-4">Entity</th>
                                <th class="px-5 py-4">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $metadata = (array) ($log['metadata_array'] ?? []);
                                $actorLabel = trim((string) ($log['actor_name'] ?? 'System'));
                                $actorRole = trim((string) ($log['actor_role'] ?? ''));
                                ?>
                                <tr class="align-top">
                                    <td class="px-5 py-4 text-slate-600">
                                        <strong class="block font-semibold text-slate-900"><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <strong class="block font-semibold text-slate-900"><?= htmlspecialchars($actorLabel !== '' ? $actorLabel : 'System', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="mt-1 block text-xs text-slate-500"><?= htmlspecialchars($actorRole !== '' ? $actorRole : 'System actor', ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 <?= $statusClass((string) ($log['action'] ?? '')) ?>">
                                            <?= htmlspecialchars((string) ($log['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <strong class="block font-semibold text-slate-900"><?= htmlspecialchars((string) ($log['entity_type'] ?? 'system'), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="mt-1 block text-xs text-slate-500"><?= htmlspecialchars((string) ($log['entity_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <?php if ($metadata === []): ?>
                                            <span class="text-sm text-slate-400">No extra metadata</span>
                                        <?php else: ?>
                                            <details class="group rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                                                <summary class="cursor-pointer list-none text-sm font-semibold text-slate-700">
                                                    <span class="inline-flex items-center gap-2">
                                                        <i data-lucide="chevron-right" class="h-4 w-4 transition group-open:rotate-90"></i>
                                                        View metadata
                                                    </span>
                                                </summary>
                                                <div class="mt-3 space-y-2">
                                                    <?php foreach ($metadata as $key => $value): ?>
                                                        <div class="rounded-2xl bg-white px-3 py-2">
                                                            <span class="block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400"><?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?></span>
                                                            <strong class="mt-1 block break-words text-sm font-medium text-slate-700"><?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]' : (string) $value, ENT_QUOTES, 'UTF-8') ?></strong>
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
            </div>

            <div class="mt-6 space-y-4 xl:hidden">
                <?php foreach ($logs as $log): ?>
                    <?php
                    $metadata = (array) ($log['metadata_array'] ?? []);
                    $actorLabel = trim((string) ($log['actor_name'] ?? 'System'));
                    $actorRole = trim((string) ($log['actor_role'] ?? ''));
                    ?>
                    <article class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-200/30">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 <?= $statusClass((string) ($log['action'] ?? '')) ?>">
                                    <?= htmlspecialchars((string) ($log['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($actorLabel !== '' ? $actorLabel : 'System', ENT_QUOTES, 'UTF-8') ?><?= $actorRole !== '' ? ' · ' . htmlspecialchars($actorRole, ENT_QUOTES, 'UTF-8') : '' ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($log['entity_type'] ?? 'system'), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string) ($log['entity_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <?php if ($metadata === []): ?>
                            <p class="mt-4 text-sm text-slate-400">No extra metadata</p>
                        <?php else: ?>
                            <details class="group mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                                <summary class="cursor-pointer list-none text-sm font-semibold text-slate-700">
                                    <span class="inline-flex items-center gap-2">
                                        <i data-lucide="chevron-right" class="h-4 w-4 transition group-open:rotate-90"></i>
                                        View metadata
                                    </span>
                                </summary>
                                <div class="mt-3 space-y-2">
                                    <?php foreach ($metadata as $key => $value): ?>
                                        <div class="rounded-2xl bg-white px-3 py-2">
                                            <span class="block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400"><?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?></span>
                                            <strong class="mt-1 block break-words text-sm font-medium text-slate-700"><?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]' : (string) $value, ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 flex flex-col gap-2 border-t border-slate-200 pt-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>Activity logs are append-only audit entries.</span>
                <span>Includes auth, member, tent, attendance, and SMS actions currently wired.</span>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
