<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $adminsByTent = $adminsByTent ?? []; ?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="my-tent-title">
    <?php if ($tent === null): ?>
        <div class="rounded-[24px] border border-dashed border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">No tent has been assigned to your v2 account yet.</div>
    <?php else: ?>
        <?php $tentColor = htmlspecialchars($tent['color'] ?: '#00bd06', ENT_QUOTES, 'UTF-8'); ?>
        <?php $assignedAdmins = $adminsByTent[(int) $tent['id']] ?? []; ?>
        <div class="flex flex-col gap-4 pb-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Localized Command Center</div>
                <h1 id="my-tent-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200">
                        <i data-lucide="user"></i>
                        Leader: <?= htmlspecialchars($tent['leader_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full <?= ($tent['status'] ?? '') === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' ?> px-3 py-1.5 font-semibold">
                        <i data-lucide="badge-check"></i>
                        Status: <?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=tent&amp;tent_id=<?= (int) $tent['id'] ?>">
                    <i data-lucide="messages-square"></i>
                    Send Tent SMS
                </a>
                <?php if (!empty($tent['whatsapp_link'])): ?>
                    <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($tent['whatsapp_link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <i data-lucide="message-square"></i>
                        WhatsApp Group
                        <i data-lucide="external-link"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.95fr)]">
            <section class="overflow-hidden rounded-[28px] border border-white/70 bg-[linear-gradient(135deg,rgba(245,251,246,0.96),rgba(255,255,255,0.98))] shadow-[0_18px_55px_rgba(15,23,42,0.08)]">
                <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.9fr)] lg:items-start">
                    <div class="min-w-0">
                        <div class="rounded-[24px] border border-white/70 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.08)]" style="background: linear-gradient(135deg, <?= $tentColor ?>22, <?= $tentColor ?>08 55%, rgba(255,255,255,0.92));">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-600">Tent Identity</div>
                                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                                <span class="inline-flex min-h-9 items-center rounded-full bg-white/80 px-3 text-xs font-bold text-slate-700 ring-1 ring-white/70">
                                    <?= !empty($tent['banner']) ? 'Banner uploaded' : 'Brand color active' ?>
                                </span>
                            </div>
                            <div class="mt-6 h-40 rounded-[22px] border border-white/70 shadow-inner" style="background:
                                radial-gradient(circle at top left, rgba(255,255,255,0.65), transparent 45%),
                                linear-gradient(135deg, <?= $tentColor ?>, rgba(15,23,42,0.92));">
                                <div class="flex h-full items-end justify-between p-5">
                                    <div>
                                        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-white/70">Tent Banner Surface</div>
                                        <div class="mt-2 text-3xl font-extrabold text-white"><?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <span class="inline-grid h-11 w-11 place-items-center rounded-2xl bg-white/15 text-white backdrop-blur">
                                        <i data-lucide="<?= !empty($tent['banner']) ? 'image' : 'palette' ?>"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Leader Phone</span>
                            <strong class="mt-2 block text-lg font-extrabold text-slate-900"><?= htmlspecialchars($tent['leader_phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">WhatsApp</span>
                            <strong class="mt-2 block break-all text-sm font-bold text-slate-900"><?= htmlspecialchars($tent['whatsapp_link'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                        </article>
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500">Color</span>
                            <div class="mt-2 flex items-center gap-3">
                                <span class="h-8 w-8 rounded-full border border-slate-200 shadow-sm" style="background: <?= $tentColor ?>"></span>
                                <strong class="text-sm font-bold text-slate-900"><?= htmlspecialchars($tent['color'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <aside class="grid gap-5 self-start xl:sticky xl:top-24">
                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-xl font-extrabold text-slate-900">Tent Snapshot</h2>
                        <p class="mt-1 text-sm text-slate-500">Quick operational view of your assigned tent.</p>
                    </div>
                    <div class="grid gap-3">
                        <article class="rounded-2xl bg-emerald-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700">Tent Status</div>
                            <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= htmlspecialchars(ucfirst($tent['status']), ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="mt-2 block text-sm text-slate-600">Current v2 tent state</small>
                        </article>
                        <article class="rounded-2xl bg-slate-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Local Tools</div>
                            <strong class="mt-2 block text-3xl font-extrabold text-slate-900">Ready</strong>
                            <small class="mt-2 block text-sm text-slate-600">Members and attendance are available from the sidebar</small>
                        </article>
                        <article class="rounded-2xl bg-slate-50 px-4 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Assigned Admins</div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-[0.12em] text-slate-500 ring-1 ring-slate-200"><?= count($assignedAdmins) ?></span>
                            </div>
                            <?php if ($assignedAdmins === []): ?>
                                <small class="mt-3 block text-sm leading-6 text-slate-600">No active tent admin is assigned yet.</small>
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
                        </article>
                    </div>
                </section>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
