<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$overview = $overview ?? [];
$adminsByTent = $adminsByTent ?? [];
$statusTone = static function (array $tent): string {
    if (($tent['status'] ?? 'inactive') !== 'active') {
        return 'bg-slate-100 text-slate-600';
    }

    $memberCount = (int) ($tent['member_count'] ?? 0);
    $monthAttendance = (int) ($tent['month_attendance_count'] ?? 0);
    $attendanceRate = $memberCount > 0 ? (int) round(($monthAttendance / $memberCount) * 100) : 0;

    if ($memberCount > 0 && $attendanceRate < 40) {
        return 'bg-rose-50 text-rose-700';
    }

    return 'bg-emerald-50 text-emerald-700';
};
?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="tents-title">
    <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Super Admin</div>
            <h1 id="tents-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Tent Management</h1>
            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">Oversee and manage all regional tents, leaders, admins, and attendance performance.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" data-modal-open="create-tent-modal">
                <i data-lucide="plus"></i>
                Create New Tent
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Total Active Tents</span>
                <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="network"></i></span>
            </div>
            <strong class="mt-4 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($overview['active_tents'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">+<?= number_format((int) ($overview['created_this_year'] ?? 0)) ?> this year</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Average Tent Size</span>
                <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-sky-50 text-sky-700"><i data-lucide="users"></i></span>
            </div>
            <strong class="mt-4 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($overview['average_tent_size'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500">members</small>
        </article>
        <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Top Performing</span>
                <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="trending-up"></i></span>
            </div>
            <strong class="mt-4 block text-2xl font-extrabold leading-tight text-slate-900"><?= htmlspecialchars((string) ($overview['top_performing_name'] ?? 'No data yet'), ENT_QUOTES, 'UTF-8') ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-slate-500"><?= number_format((int) ($overview['top_performing_rate'] ?? 0)) ?>% attendance</small>
        </article>
        <article class="rounded-[24px] border border-rose-100 bg-rose-50 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-rose-700">Needs Support</span>
                <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-white text-rose-700"><i data-lucide="triangle-alert"></i></span>
            </div>
            <strong class="mt-4 block text-4xl font-extrabold leading-none text-slate-900"><?= number_format((int) ($overview['needs_support_count'] ?? 0)) ?></strong>
            <small class="mt-3 inline-block text-sm font-semibold text-rose-700">Tents under 40% attendance</small>
        </article>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2 2xl:grid-cols-3">
        <?php if ($tents === []): ?>
            <div class="rounded-[24px] border border-dashed border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500 lg:col-span-2 2xl:col-span-3">No v2 tents have been created yet.</div>
        <?php endif; ?>

        <?php foreach ($tents as $tent): ?>
            <?php
            $memberCount = (int) ($tent['member_count'] ?? 0);
            $monthAttendance = (int) ($tent['month_attendance_count'] ?? 0);
            $attendanceRate = $memberCount > 0 ? min(100, (int) round(($monthAttendance / $memberCount) * 100)) : 0;
            $leaderAssigned = !empty($tent['leader_name']);
            $adminAssigned = !empty($tent['admin_name']);
            $adminCount = (int) ($tent['admin_count'] ?? 0);
            $adminSummary = trim((string) ($tent['admin_names'] ?? ''));
            $assignedAdmins = $adminsByTent[(int) $tent['id']] ?? [];
            ?>
            <article class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm">
                <div class="h-2 w-full" style="background: <?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>"></div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="truncate text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex min-h-8 items-center rounded-full px-3 text-xs font-bold <?= $statusTone($tent) ?>">
                                    <?= htmlspecialchars(ucfirst((string) $tent['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= $memberCount > 0 ? $attendanceRate . '% monthly attendance' : 'No attendance data yet' ?></span>
                            </div>
                        </div>
                        <button type="button" class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>" aria-label="Edit <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <i data-lucide="ellipsis-vertical"></i>
                        </button>
                    </div>

                    <div class="mt-5 flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-4">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-white text-slate-700 shadow-sm">
                            <i data-lucide="<?= $leaderAssigned ? 'user-round' : 'user-round-x' ?>"></i>
                        </span>
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($leaderAssigned ? (string) $tent['leader_name'] : 'Unassigned', ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="text-sm text-slate-500"><?= htmlspecialchars($leaderAssigned ? 'Tent Leader' : 'Needs leader', ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Members</span>
                            <strong class="mt-2 block text-2xl font-extrabold text-slate-900"><?= number_format($memberCount) ?></strong>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Attendance</span>
                            <strong class="mt-2 block text-2xl font-extrabold text-slate-900"><?= $memberCount > 0 ? number_format($attendanceRate) . '%' : '--' ?></strong>
                        </article>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm text-slate-600">
                        <div class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 px-3 py-3">
                            <span class="font-semibold text-slate-500">Tent Admins</span>
                            <span class="text-right font-semibold text-slate-800">
                                <?= htmlspecialchars(
                                    $adminAssigned
                                        ? ($adminCount > 1 ? $adminCount . ' tent admins assigned' : (string) $tent['admin_name'])
                                        : 'No tent admin assigned',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <span class="font-semibold text-slate-500">Assigned Admin List</span>
                                <span class="text-right text-xs font-bold uppercase tracking-[0.12em] text-slate-400"><?= count($assignedAdmins) ?> total</span>
                            </div>
                            <?php if ($assignedAdmins === []): ?>
                                <p class="mt-2 text-sm text-slate-500">No active tent admin has been assigned to this tent yet.</p>
                            <?php else: ?>
                                <div class="mt-3 grid gap-2">
                                    <?php foreach ($assignedAdmins as $assignedAdmin): ?>
                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                            <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars((string) $assignedAdmin['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="mt-1 block break-all text-sm text-slate-500"><?= htmlspecialchars((string) ($assignedAdmin['email'] ?? 'No email saved'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 px-3 py-3">
                            <span class="font-semibold text-slate-500">Assets</span>
                            <span class="text-right font-semibold text-slate-800"><?= htmlspecialchars(!empty($tent['banner']) ? 'Banner saved' : 'No banner yet', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                            <i data-lucide="pencil"></i>
                            Edit
                        </button>
                        <?php if (!empty($tent['whatsapp_link'])): ?>
                            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars((string) $tent['whatsapp_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <i data-lucide="link"></i>
                                WhatsApp
                            </a>
                        <?php elseif (!$adminAssigned): ?>
                            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                                <i data-lucide="user-plus"></i>
                                Assign
                            </button>
                        <?php else: ?>
                            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" data-modal-open="edit-tent-modal-<?= (int) $tent['id'] ?>">
                                <i data-lucide="settings-2"></i>
                                Manage
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <div class="modal-backdrop" data-modal="edit-tent-modal-<?= (int) $tent['id'] ?>" aria-hidden="true">
                <div class="modal-panel rounded-[24px] border border-slate-200 bg-white shadow-panel" role="dialog" aria-modal="true" aria-labelledby="edit-tent-title-<?= (int) $tent['id'] ?>">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                        <div>
                            <div class="text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-700">Tent Control</div>
                            <h2 id="edit-tent-title-<?= (int) $tent['id'] ?>" class="mt-1 text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        </div>
                        <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" type="button" data-modal-close aria-label="Close tent editor">
                            <i data-lucide="x"></i>
                        </button>
                    </div>

                    <div class="grid gap-5 px-6 py-6">
                        <form class="grid gap-5" method="POST" action="tents/update" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $tent['id'] ?>">
                            <input type="hidden" name="existing_banner" value="<?= htmlspecialchars($tent['banner'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Name</span>
                                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="name" value="<?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</span>
                                    <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="status">
                                        <option value="active" <?= $tent['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $tent['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Color</span>
                                    <input class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-2 py-2" type="color" name="color" value="<?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader Phone</span>
                                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="leader_phone" value="<?= htmlspecialchars($tent['leader_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader Name</span>
                                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="leader_name" value="<?= htmlspecialchars($tent['leader_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Banner</span>
                                    <input class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700" type="file" name="banner" accept="image/png,image/jpeg,image/webp,image/gif">
                                    <?php if (!empty($tent['banner'])): ?>
                                        <span class="text-xs font-medium text-slate-500">Current banner saved</span>
                                    <?php endif; ?>
                                </label>
                                <label class="grid gap-2 md:col-span-2">
                                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">WhatsApp Link</span>
                                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="url" name="whatsapp_link" value="<?= htmlspecialchars($tent['whatsapp_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </label>
                            </div>
                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50" data-modal-close>Close</button>
                                <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                                    <i data-lucide="save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>

                        <form class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" method="POST" action="tents/assign-admin">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="tent_id" value="<?= (int) $tent['id'] ?>">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-900">Assign Tent Admin</h3>
                                <?php if ($adminAssigned): ?>
                                    <p class="mt-1 text-sm text-slate-500">Current: <?= htmlspecialchars($adminSummary, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($assignedAdmins !== []): ?>
                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                                    <div class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Assigned Admins</div>
                                    <div class="mt-3 grid gap-2">
                                        <?php foreach ($assignedAdmins as $assignedAdmin): ?>
                                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars((string) $assignedAdmin['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small class="mt-1 block break-all text-sm text-slate-500"><?= htmlspecialchars((string) ($assignedAdmin['email'] ?? 'No email saved'), ENT_QUOTES, 'UTF-8') ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label class="grid gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent Admin</span>
                                <select class="h-12 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="user_id" required>
                                    <option value=""><?= htmlspecialchars($adminAssigned ? 'Add another admin' : 'Assign admin', ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php foreach ($tentAdmins as $admin): ?>
                                        <option value="<?= (int) $admin['id'] ?>">
                                            <?= htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                                <i data-lucide="user-check"></i>
                                Assign Admin
                            </button>
                        </form>

                        <form method="POST" action="<?= $tent['status'] === 'active' ? 'tents/deactivate' : 'tents/update' ?>">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $tent['id'] ?>">
                            <?php if ($tent['status'] !== 'active'): ?>
                                <input type="hidden" name="name" value="<?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="existing_banner" value="<?= htmlspecialchars($tent['banner'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="color" value="<?= htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="leader_name" value="<?= htmlspecialchars($tent['leader_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="leader_phone" value="<?= htmlspecialchars($tent['leader_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="whatsapp_link" value="<?= htmlspecialchars($tent['whatsapp_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="status" value="active">
                            <?php endif; ?>
                            <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-bold <?= $tent['status'] === 'active' ? 'border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100' : 'border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50' ?>" type="submit">
                                <i data-lucide="<?= $tent['status'] === 'active' ? 'ban' : 'rotate-ccw' ?>"></i>
                                <?= $tent['status'] === 'active' ? 'Deactivate Tent' : 'Reactivate Tent' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal-backdrop" data-modal="create-tent-modal" aria-hidden="true">
    <div class="modal-panel rounded-[24px] border border-slate-200 bg-white shadow-panel" role="dialog" aria-modal="true" aria-labelledby="create-tent-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-700">New Tent</div>
                <h2 id="create-tent-title" class="mt-1 text-2xl font-extrabold text-slate-900">Create New Tent</h2>
            </div>
            <button class="inline-grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" type="button" data-modal-close aria-label="Close create tent form">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form class="grid gap-6 px-6 py-6" method="POST" action="tents/create" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Name</span>
                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="name" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Color</span>
                    <input class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-2 py-2" type="color" name="color" value="#00bd06">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader Name</span>
                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="leader_name">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader Phone</span>
                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="leader_phone">
                </label>
                <label class="grid gap-2 md:col-span-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Banner</span>
                    <input class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700" type="file" name="banner" accept="image/png,image/jpeg,image/webp,image/gif">
                </label>
                <label class="grid gap-2 md:col-span-2">
                    <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">WhatsApp Link</span>
                    <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="url" name="whatsapp_link" placeholder="https://chat.whatsapp.com/...">
                </label>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50" data-modal-close>Cancel</button>
                <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                    <i data-lucide="plus"></i>
                    Create New Tent
                </button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
