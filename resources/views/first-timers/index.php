<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$statusClasses = static function (string $status): string {
    return match ($status) {
        'Pending' => 'bg-amber-50 text-amber-700',
        'Called' => 'bg-sky-50 text-sky-700',
        'Converted' => 'bg-emerald-50 text-emerald-700',
        'Not Returning' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
};
$pendingCount = count(array_filter($firstTimers, static fn (array $row): bool => ($row['status'] ?? '') === 'Pending'));
$convertedCount = count(array_filter($firstTimers, static fn (array $row): bool => ($row['status'] ?? '') === 'Converted'));
$calledCount = count(array_filter($firstTimers, static fn (array $row): bool => ($row['status'] ?? '') === 'Called'));
?>

<section class="mx-auto w-full max-w-[1280px] py-5" aria-labelledby="first-timers-title">
    <div class="flex flex-col gap-4 pb-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h1 id="first-timers-title" class="text-3xl font-extrabold text-slate-900 md:text-4xl">First-Timer Follow-up</h1>
            <p class="mt-1 text-sm text-slate-500">Track new visitors, update follow-up progress, and convert ready records into full members.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" data-modal-open="add-first-timer-modal">
                <i data-lucide="user-plus"></i>
                Add First-Timer
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Visible Records</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format(count($firstTimers)) ?></strong>
            <small class="mt-3 inline-block text-xs font-bold text-slate-500">Current filtered view</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Pending</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($pendingCount) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Needs first follow-up</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Called</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($calledCount) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-sky-700"><span class="h-2 w-2 rounded-full bg-sky-400"></span> Outreach in progress</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Converted</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($convertedCount) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Moved into members</small>
        </article>
    </div>

    <form class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1.4fr)_minmax(180px,0.8fr)_minmax(180px,0.8fr)_auto]" method="GET" action="first-timers">
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase text-slate-500">Search</span>
            <div class="relative">
                <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" data-lucide="search"></i>
                <input class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-12 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-500" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search names, phones...">
            </div>
        </label>
        <?php if (($user['role'] ?? null) === 'Super Admin'): ?>
            <label class="grid gap-2">
                <span class="text-xs font-extrabold uppercase text-slate-500">Tent Location</span>
                <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id">
                    <option value="">All Tents</option>
                    <?php foreach ($tents as $tent): ?>
                        <option value="<?= (int) $tent['id'] ?>" <?= (int) $selectedTentId === (int) $tent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase text-slate-500">Status</span>
            <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="status">
                <option value="">All Statuses</option>
                <?php foreach (['Pending', 'Called', 'Converted', 'Not Returning'] as $status): ?>
                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="flex items-end">
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733] lg:w-auto" type="submit">
                <i data-lucide="search"></i>
                Search
            </button>
        </div>
    </form>

    <div class="mt-5 rounded-lg border border-slate-200 bg-white shadow-sm">
        <?php if ($firstTimers === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">No first-timer records match this view yet.</div>
        <?php endif; ?>

        <?php if ($firstTimers !== []): ?>
            <div class="hidden overflow-x-auto xl:block">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Name</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Contact</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">First Visit</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firstTimers as $record): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            ?>
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="min-w-0">
                                            <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="text-xs font-bold uppercase text-slate-500">FT-<?= str_pad((string) (int) $record['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= !empty($record['converted_member_name']) ? 'Converted to ' . htmlspecialchars($record['converted_member_name'], ENT_QUOTES, 'UTF-8') : 'Follow-up record' ?></small>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $statusClasses((string) $record['status']) ?>">
                                        <?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="first-timers/show?id=<?= (int) $record['id'] ?>" aria-label="View <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if (($record['status'] ?? '') !== 'Converted'): ?>
                                            <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="first-timers/show?id=<?= (int) $record['id'] ?>#convert-first-timer" aria-label="Convert <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
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

            <div class="grid gap-2 p-3 xl:hidden">
                <?php foreach ($firstTimers as $record): ?>
                    <?php
                    $nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
                    $initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                    ?>
                    <article class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-xs font-bold uppercase text-slate-500">FT-<?= str_pad((string) (int) $record['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                </div>
                            </div>
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $statusClasses((string) $record['status']) ?>">
                                <?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Contact</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="mt-1 block text-slate-500"><?= !empty($record['converted_member_name']) ? 'Converted to ' . htmlspecialchars($record['converted_member_name'], ENT_QUOTES, 'UTF-8') : 'Follow-up record' ?></small>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">First Visit</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2.5 sm:col-span-2">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                                <div class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="first-timers/show?id=<?= (int) $record['id'] ?>" aria-label="View <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <i data-lucide="eye"></i>
                            </a>
                            <?php if (($record['status'] ?? '') !== 'Converted'): ?>
                                <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="first-timers/show?id=<?= (int) $record['id'] ?>#convert-first-timer" aria-label="Convert <?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="refresh-cw"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col gap-2 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <span>Showing <?= count($firstTimers) ?> first-timer records</span>
                <span>Scoped to your current KKYF access level</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-backdrop" data-modal="add-first-timer-modal" aria-hidden="true">
    <div class="modal-panel rounded-lg border border-slate-200 bg-white shadow-panel" role="dialog" aria-modal="true" aria-labelledby="add-first-timer-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-700">New Visitor</div>
                <h2 id="add-first-timer-title" class="mt-1 text-2xl font-extrabold text-slate-900">Add a first-timer</h2>
            </div>
            <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" type="button" data-modal-close aria-label="Close add first-timer form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="grid gap-6 px-6 py-6" method="POST" action="first-timers/create">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Full Name</span>
                    <input class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="full_name" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Phone</span>
                    <input class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="tel" name="phone">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">First Visit Date</span>
                    <input class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="first_visit_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Status</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="status">
                        <option value="Pending">Pending</option>
                        <option value="Called">Called</option>
                        <option value="Not Returning">Not Returning</option>
                    </select>
                </label>
                <label class="grid gap-2 md:col-span-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Tent</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>">
                                <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="grid gap-2 md:col-span-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Follow-up Notes</span>
                    <textarea class="min-h-[120px] rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-500" name="followup_notes" rows="4" placeholder="Add outreach notes, referral details, or next action."></textarea>
                </label>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50" data-modal-close>Cancel</button>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]"><i data-lucide="user-plus"></i> Add First-Timer</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
