<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php
$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$nameParts = preg_split('/\s+/', trim($record['full_name'])) ?: [];
$initials = strtoupper(substr($nameParts[0] ?? 'F', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
$statusClasses = match ((string) ($record['status'] ?? '')) {
    'Pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
    'Called' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-100',
    'Converted' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
    'Not Returning' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
    default => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
};
?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="first-timer-title">
    <nav class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a class="font-semibold text-slate-600 transition hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/first-timers">First Timers</a>
        <span aria-hidden="true">/</span>
        <strong class="font-semibold text-slate-900">Follow-up Profile</strong>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.95fr)]">
        <div class="grid gap-5">
            <section class="overflow-hidden rounded-[28px] border border-white/70 bg-[linear-gradient(135deg,rgba(245,251,246,0.96),rgba(255,255,255,0.98))] shadow-[0_18px_55px_rgba(15,23,42,0.08)]" aria-labelledby="first-timer-title">
                <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-start">
                    <div class="flex justify-center lg:justify-start">
                        <div class="grid h-28 w-28 place-items-center rounded-[24px] border border-white/80 bg-gradient-to-br from-emerald-100 via-white to-emerald-50 text-3xl font-extrabold text-emerald-800 shadow-[0_16px_40px_rgba(15,23,42,0.12)] sm:h-32 sm:w-32">
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Visitor Follow-up</div>
                                <h1 id="first-timer-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-600">
                                        <i data-lucide="badge"></i>
                                        FT-<?= str_pad((string) (int) $record['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block"></span>
                                    <span><?= htmlspecialchars($record['phone'] ?: 'No phone', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <span class="inline-flex min-h-9 items-center self-start rounded-full px-4 text-sm font-bold <?= $statusClasses ?>">
                                <?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Tent</div>
                                <div class="mt-2 inline-flex items-center gap-2 text-sm font-bold text-slate-800">
                                    <i data-lucide="network"></i>
                                    <?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </article>
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">First Visit</div>
                                <div class="mt-2 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Converted Member</div>
                                <div class="mt-2 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['converted_member_name'] ?? 'Not converted yet', ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="first-timer-info-title">
                    <div class="mb-4">
                        <h2 id="first-timer-info-title" class="text-xl font-extrabold text-slate-900">Visitor Details</h2>
                        <p class="mt-1 text-sm text-slate-500">Core contact and follow-up context for this visitor.</p>
                    </div>
                    <dl class="grid gap-3">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Phone</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">First Visit Date</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Assigned Tent</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['tent_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Converted Member</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($record['converted_member_name'] ?? 'Not converted yet', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-xl font-extrabold text-slate-900">Follow-up Status</h2>
                        <p class="mt-1 text-sm text-slate-500">Current outreach state and membership transition progress.</p>
                    </div>
                    <div class="grid gap-3">
                        <article class="rounded-2xl bg-slate-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Current Status</div>
                            <strong class="mt-2 block text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="mt-2 block text-sm text-slate-600">Follow-up workflow in progress</small>
                        </article>
                        <article class="rounded-2xl bg-slate-50 px-4 py-4">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Conversion</div>
                            <strong class="mt-2 block text-2xl font-extrabold text-slate-900"><?= !empty($record['converted_member_id']) ? 'Done' : 'Open' ?></strong>
                            <small class="mt-2 block text-sm text-slate-600"><?= !empty($record['converted_member_name']) ? 'Linked to member profile' : 'Ready when visitor is confirmed' ?></small>
                        </article>
                    </div>
                </section>
            </div>

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="followup-title">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="followup-title" class="text-xl font-extrabold text-slate-900">Follow-up Notes</h2>
                        <p class="mt-1 text-sm text-slate-500">Current outreach notes, referral context, and next steps.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Current</span>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-slate-700">
                    <?= nl2br(htmlspecialchars($record['followup_notes'] ?: 'No notes recorded for this visitor yet.', ENT_QUOTES, 'UTF-8')) ?>
                </div>
            </section>
        </div>

        <aside class="grid gap-5 self-start xl:sticky xl:top-24">
            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="edit-first-timer-title">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="edit-first-timer-title" class="text-xl font-extrabold text-slate-900">Update Follow-up</h2>
                        <p class="mt-1 text-sm text-slate-500">Edit visitor details, tent assignment, and follow-up notes.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Visitor Record</span>
                </div>

                <form class="grid gap-4" method="POST" action="../first-timers/update">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Full Name</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="full_name" value="<?= htmlspecialchars($record['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Phone</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="tel" name="phone" value="<?= htmlspecialchars($record['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">First Visit Date</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="first_visit_date" value="<?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="status" <?= ($record['status'] ?? '') === 'Converted' ? 'disabled' : '' ?>>
                                <?php foreach (['Pending', 'Called', 'Not Returning'] as $status): ?>
                                    <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $record['status'] === $status ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (($record['status'] ?? '') === 'Converted'): ?>
                                    <option value="Converted" selected>Converted</option>
                                <?php endif; ?>
                            </select>
                        </label>
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                                <?php foreach ($tents as $tent): ?>
                                    <option value="<?= (int) $tent['id'] ?>" <?= (int) $record['tent_id'] === (int) $tent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Follow-up Notes</span>
                            <textarea class="min-h-[140px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-500" name="followup_notes" rows="4"><?= htmlspecialchars($record['followup_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </label>
                    </div>
                    <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                        <i data-lucide="save"></i>
                        Save Follow-up
                    </button>
                </form>
            </section>

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="convert-first-timer-title">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="convert-first-timer-title" class="text-xl font-extrabold text-slate-900">Convert to Member</h2>
                        <p class="mt-1 text-sm text-slate-500">Create a full member profile when this visitor is confirmed ready.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600"><?= !empty($record['converted_member_id']) ? 'Completed' : 'Ready' ?></span>
                </div>

                <?php if (!empty($record['converted_member_id'])): ?>
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                        This first-timer has already been converted.
                        <a class="mt-3 inline-flex items-center gap-2 font-bold text-emerald-700 transition hover:text-emerald-800" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/show?id=<?= (int) $record['converted_member_id'] ?>">
                            <i data-lucide="arrow-up-right"></i>
                            Open member profile
                        </a>
                    </div>
                <?php else: ?>
                    <form class="grid gap-4" id="convert-first-timer" method="POST" action="../first-timers/convert">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Occupation</span>
                                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="occupation">
                                    <option>Student</option>
                                    <option>Worker</option>
                                    <option>Alumni</option>
                                </select>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">School Name</span>
                                <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="school_name">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Birth Month</span>
                                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_month">
                                    <option value="">Month</option>
                                    <?php for ($month = 1; $month <= 12; $month++): ?>
                                        <option value="<?= $month ?>"><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Birth Day</span>
                                <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_day">
                                    <option value="">Day</option>
                                    <?php for ($day = 1; $day <= 31; $day++): ?>
                                        <option value="<?= $day ?>"><?= $day ?></option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label class="grid gap-2 sm:col-span-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Join Date</span>
                                <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="join_date" value="<?= htmlspecialchars($record['first_visit_date'], ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="grid gap-2 sm:col-span-2">
                                <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Member Notes</span>
                                <textarea class="min-h-[140px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-500" name="notes" rows="4" placeholder="Optional notes to carry into the new member profile."></textarea>
                            </label>
                        </div>
                        <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                            <i data-lucide="refresh-cw"></i>
                            Convert to Member
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
