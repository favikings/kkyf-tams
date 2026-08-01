<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<?php
$badgeIcon = static function (string $badge): string {
    return match ($badge) {
        'Unstoppable' => 'flame',
        'Faithful' => 'shield-check',
        'On Fire' => 'zap',
        'First Step' => 'sparkles',
        '1-Year Member' => 'award',
        '6-Month Member' => 'medal',
        '3-Month Member' => 'star',
        default => 'badge-check',
    };
};
$callHref = static function (?string $phone): ?string {
    $normalized = preg_replace('/(?!^\+)[^\d]/', '', trim((string) $phone)) ?? '';

    return $normalized !== '' ? 'tel:' . $normalized : null;
};
$activeCount = count(array_filter($members, static fn (array $member): bool => ($member['active_status'] ?? '') === 'active'));
$inactiveCount = max(0, count($members) - $activeCount);
?>

<section class="mx-auto w-full max-w-[1280px] py-5" aria-labelledby="members-title">
    <div class="flex flex-col gap-4 pb-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h1 id="members-title" class="text-3xl font-extrabold text-slate-900 md:text-4xl">Member Directory</h1>
            <p class="mt-1 text-sm text-slate-500">Manage active constituents, track attendance, and oversee tent assignments.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/export?format=csv">
                <i data-lucide="file-down"></i>
                Export CSV
            </a>
            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/export?format=excel">
                <i data-lucide="sheet"></i>
                Export Excel
            </a>
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" data-modal-open="add-member-modal">
                <i data-lucide="user-plus"></i>
                Add Member
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Visible Members</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format(count($members)) ?></strong>
            <small class="mt-3 inline-block text-xs font-bold text-slate-500">Current filtered view</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Active</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($activeCount) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Ready for attendance</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-extrabold uppercase text-slate-500">Inactive</span>
            <strong class="mt-3 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format($inactiveCount) ?></strong>
            <small class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Needs follow-up</small>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-3 xl:col-span-1">
            <span class="text-xs font-extrabold uppercase text-slate-500">Scope</span>
            <strong class="mt-3 block text-2xl font-extrabold leading-tight text-slate-900"><?= ($user['role'] ?? null) === 'Super Admin' ? 'All Tents' : 'Assigned Tent' ?></strong>
            <small class="mt-3 inline-block text-xs font-bold text-slate-500">Directory tools stay in this view</small>
        </article>
    </div>

    <form class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,1.4fr)_minmax(180px,0.8fr)_minmax(180px,0.7fr)_auto]" method="GET" action="members">
        <label class="grid gap-2">
            <span class="text-xs font-extrabold uppercase text-slate-500">Search</span>
            <div class="relative">
                <i class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" data-lucide="search"></i>
                <input class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-12 pr-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-500" type="search" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search members, phones...">
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
                <option value="active" <?= ($selectedStatus ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($selectedStatus ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
        <?php if ($members === []): ?>
            <div class="px-5 py-10 text-center text-sm text-slate-500">No v2 members match this view.</div>
        <?php endif; ?>

        <?php if ($members !== []): ?>
            <div class="hidden overflow-x-auto xl:block">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80">
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Contact</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Streak</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Badges</th>
                            <th class="px-5 py-4 text-left text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <?php
                            $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                            $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            $memberCallHref = $callHref((string) ($member['phone'] ?? ''));
                            ?>
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="min-w-0">
                                            <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="text-xs font-bold uppercase text-slate-500">KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($member['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-sm text-slate-500"><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-slate-700"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $member['active_status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                                        <i data-lucide="<?= (int) ($member['current_streak'] ?? 0) > 0 ? 'flame' : 'minus' ?>"></i>
                                        <?= (int) ($member['current_streak'] ?? 0) ?> wks
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <?php if (!empty($member['badges'])): ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <?php foreach (array_slice($member['badges'], 0, 3) as $badge): ?>
                                                <span class="inline-grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-700" title="<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="<?= $badgeIcon((string) $badge) ?>"></i>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($member['badges']) > 3): ?>
                                                <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">+<?= count($member['badges']) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-slate-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <?php if ($memberCallHref !== null): ?>
                                            <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($memberCallHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="Call <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="phone"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300" title="No phone saved for call" aria-hidden="true">
                                                <i data-lucide="phone"></i>
                                            </span>
                                        <?php endif; ?>
                                        <form method="POST" action="members/deactivate">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                            <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100" type="submit" aria-label="Deactivate <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i data-lucide="ban"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="grid gap-2 p-3 xl:hidden">
                <?php foreach ($members as $member): ?>
                    <?php
                    $nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
                    $initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                    $memberCallHref = $callHref((string) ($member['phone'] ?? ''));
                    ?>
                    <article class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-800"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-xs font-bold uppercase text-slate-500">KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?></small>
                                </div>
                            </div>
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $member['active_status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 text-sm">
                            <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                                <div class="grid gap-0.5">
                                    <span class="text-xs font-extrabold uppercase text-slate-500">Contact</span>
                                    <strong class="text-slate-900"><?= htmlspecialchars($member['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-slate-500"><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                                <div class="grid gap-0.5">
                                    <span class="text-xs font-extrabold uppercase text-slate-500">Tent</span>
                                    <div class="font-semibold text-slate-700"><?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1fr)] sm:gap-3">
                                <div class="grid gap-0.5">
                                    <span class="text-xs font-extrabold uppercase text-slate-500">Streak</span>
                                    <div class="inline-flex items-center gap-2 font-bold text-slate-700">
                                        <i data-lucide="<?= (int) ($member['current_streak'] ?? 0) > 0 ? 'flame' : 'minus' ?>"></i>
                                        <?= (int) ($member['current_streak'] ?? 0) ?> wks
                                    </div>
                                </div>
                                <div class="grid gap-0.5">
                                    <span class="text-xs font-extrabold uppercase text-slate-500">Badges</span>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <?php if (!empty($member['badges'])): ?>
                                            <?php foreach (array_slice($member['badges'], 0, 3) as $badge): ?>
                                                <span class="inline-grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-700" title="<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>">
                                                    <i data-lucide="<?= $badgeIcon((string) $badge) ?>"></i>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-slate-400">None</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="members/show?id=<?= (int) $member['id'] ?>" aria-label="View <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <i data-lucide="eye"></i>
                            </a>
                            <?php if ($memberCallHref !== null): ?>
                                <a class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($memberCallHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="Call <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="phone"></i>
                                </a>
                            <?php else: ?>
                                <span class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300" title="No phone saved for call" aria-hidden="true">
                                    <i data-lucide="phone"></i>
                                </span>
                            <?php endif; ?>
                            <form method="POST" action="members/deactivate">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100" type="submit" aria-label="Deactivate <?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="ban"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col gap-2 border-t border-slate-200 px-5 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <span>Showing <?= count($members) ?> members</span>
                <span>Limited to the current v2 directory view</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-backdrop" data-modal="add-member-modal" aria-hidden="true">
    <div class="modal-panel rounded-lg border border-slate-200 bg-white shadow-panel" role="dialog" aria-modal="true" aria-labelledby="add-member-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-700">New Profile</div>
                <h2 id="add-member-title" class="mt-1 text-2xl font-extrabold text-slate-900">Add a member</h2>
            </div>
            <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" type="button" data-modal-close aria-label="Close add member form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="grid gap-6 px-6 py-6" method="POST" action="members/create" enctype="multipart/form-data">
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
                    <span class="text-xs font-extrabold uppercase text-slate-500">Birth Month</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_month">
                        <option value="">Month</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="<?= $month ?>"><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Birth Day</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_day">
                        <option value="">Day</option>
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Occupation</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="occupation">
                        <option>Student</option>
                        <option>Worker</option>
                        <option>Alumni</option>
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">School Name</span>
                    <input class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="school_name">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Join Date</span>
                    <input class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="join_date">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Tent</span>
                    <select class="h-12 rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                        <?php foreach ($tents as $tent): ?>
                            <option value="<?= (int) $tent['id'] ?>">
                                <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Profile Photo</span>
                    <input class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                </label>
                <label class="grid gap-2 md:col-span-2">
                    <span class="text-xs font-extrabold uppercase text-slate-500">Notes</span>
                    <textarea class="min-h-[110px] rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-500" name="notes" rows="3"></textarea>
                </label>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50" data-modal-close>Cancel</button>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]"><i data-lucide="user-plus"></i> Add Member</button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
