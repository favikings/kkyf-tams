<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php
$birthday = 'Not set';
if (!empty($member['date_of_birth']) && preg_match('/^\d{2}-\d{2}$/', $member['date_of_birth'])) {
    [$month, $day] = explode('-', $member['date_of_birth']);
    $birthday = date('F j', mktime(0, 0, 0, (int) $month, (int) $day));
}

$basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/');
$nameParts = preg_split('/\s+/', trim($member['full_name'])) ?: [];
$initials = strtoupper(substr($nameParts[0] ?? 'M', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
[$birthMonth, $birthDay] = array_pad(explode('-', $member['date_of_birth'] ?? ''), 2, '');
$anniversary = 'Not set';
$anniversaryYears = null;
if (!empty($member['join_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $member['join_date'])) {
    $joinDate = new DateTimeImmutable($member['join_date']);
    $today = new DateTimeImmutable('today');
    $anniversary = $joinDate->format('F j, Y');
    $anniversaryYears = max(0, (int) $joinDate->diff($today)->y);
}
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
$callHref = null;
if (!empty($member['phone'])) {
    $normalizedPhone = preg_replace('/(?!^\+)[^\d]/', '', trim((string) $member['phone'])) ?? '';
    $callHref = $normalizedPhone !== '' ? 'tel:' . $normalizedPhone : null;
}
$statusClasses = ($member['active_status'] ?? '') === 'active'
    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
    : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>

<section class="mx-auto w-full max-w-[1320px] py-5" aria-labelledby="member-title">
    <nav class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a class="font-semibold text-slate-600 transition hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members">Members</a>
        <span aria-hidden="true">/</span>
        <strong class="font-semibold text-slate-900">Member Profile</strong>
    </nav>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.95fr)]">
        <div class="grid gap-5">
            <section class="overflow-hidden rounded-[28px] border border-white/70 bg-[linear-gradient(135deg,rgba(245,251,246,0.96),rgba(255,255,255,0.98))] shadow-[0_18px_55px_rgba(15,23,42,0.08)]" aria-labelledby="member-title">
                <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-start">
                    <div class="flex justify-center lg:justify-start">
                        <?php if (!empty($member['profile_photo'])): ?>
                            <img
                                class="h-28 w-28 rounded-[24px] border border-white/80 object-cover shadow-[0_16px_40px_rgba(15,23,42,0.12)] sm:h-32 sm:w-32"
                                src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/members/photo?id=<?= (int) $member['id'] ?>"
                                alt="<?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?> profile photo"
                            >
                        <?php else: ?>
                            <div class="grid h-28 w-28 place-items-center rounded-[24px] border border-white/80 bg-gradient-to-br from-emerald-100 via-white to-emerald-50 text-3xl font-extrabold text-emerald-800 shadow-[0_16px_40px_rgba(15,23,42,0.12)] sm:h-32 sm:w-32">
                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Member Profile</div>
                                <h1 id="member-title" class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-600">
                                        <i data-lucide="badge"></i>
                                        KKYF-<?= str_pad((string) (int) $member['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block"></span>
                                    <span><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <span class="inline-flex min-h-9 items-center self-start rounded-full px-4 text-sm font-bold <?= $statusClasses ?>">
                                <?= htmlspecialchars(ucfirst($member['active_status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Tent</div>
                                <div class="mt-2 inline-flex items-center gap-2 text-sm font-bold text-slate-800">
                                    <i data-lucide="network"></i>
                                    <?= htmlspecialchars($member['tent_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </article>
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Join Date</div>
                                <div class="mt-2 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['join_date'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                            <article class="rounded-2xl border border-slate-200/80 bg-white/85 p-4">
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">School</div>
                                <div class="mt-2 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['school_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <?php if ($callHref !== null): ?>
                                <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($callHref, ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="phone"></i>
                                    Call Member
                                </a>
                            <?php else: ?>
                                <span class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-bold text-slate-400" aria-hidden="true" title="No phone saved for call">
                                    <i data-lucide="phone"></i>
                                    Call Member
                                </span>
                            <?php endif; ?>
                            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/sms?scope=member&amp;member_id=<?= (int) $member['id'] ?>">
                                <i data-lucide="messages-square"></i>
                                Send SMS
                            </a>
                            <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/attendance?q=<?= urlencode((string) $member['full_name']) ?>">
                                <i data-lucide="clipboard-check"></i>
                                Check Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="personal-info-title">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 id="personal-info-title" class="text-xl font-extrabold text-slate-900">Personal Information</h2>
                            <p class="mt-1 text-sm text-slate-500">Core contact and member identity details.</p>
                        </div>
                    </div>
                    <dl class="grid gap-3">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Phone</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['phone'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Date of Birth</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($birthday, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Occupation</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['occupation'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">School/Institution</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['school_name'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="milestone-title">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 id="milestone-title" class="text-xl font-extrabold text-slate-900">Membership Milestones</h2>
                            <p class="mt-1 text-sm text-slate-500">Service history and long-term growth markers.</p>
                        </div>
                    </div>
                    <dl class="grid gap-3">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Join Date</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($member['join_date'] ?: 'Not set', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Anniversary</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= htmlspecialchars($anniversary, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <dt class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Years in KKYF</dt>
                            <dd class="mt-1 text-sm font-bold text-slate-800"><?= $anniversaryYears !== null ? number_format($anniversaryYears) : 'Not set' ?></dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="badge-vault-title">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="badge-vault-title" class="text-xl font-extrabold text-slate-900">Badge Vault</h2>
                        <p class="mt-1 text-sm text-slate-500">Recognition earned through attendance and consistency.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-emerald-50 px-3 text-xs font-bold text-emerald-700"><?= count($member['badges'] ?? []) ?> earned</span>
                </div>

                <?php if (!empty($member['badges'])): ?>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($member['badges'] as $badge): ?>
                            <article class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-slate-700 shadow-sm">
                                    <i data-lucide="<?= $badgeIcon((string) $badge) ?>"></i>
                                </span>
                                <div class="min-w-0">
                                    <strong class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small class="text-xs font-medium text-slate-500">Milestone achieved</small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">No badges earned yet. Consistent Sunday attendance will unlock them.</div>
                <?php endif; ?>
            </section>

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="notes-title">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="notes-title" class="text-xl font-extrabold text-slate-900">Follow-up Notes</h2>
                        <p class="mt-1 text-sm text-slate-500">Context for care, outreach, and next actions.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Current</span>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-7 text-slate-700">
                    <?= nl2br(htmlspecialchars($member['notes'] ?: 'No notes recorded for this member yet.', ENT_QUOTES, 'UTF-8')) ?>
                </div>
            </section>
        </div>

        <aside class="grid gap-5 self-start xl:sticky xl:top-24">
            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-extrabold text-slate-900">Attendance Snapshot</h2>
                    <p class="mt-1 text-sm text-slate-500">Quick view of weekly consistency and participation.</p>
                </div>
                <div class="grid gap-3">
                    <article class="rounded-2xl bg-emerald-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700">Current Streak</div>
                        <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= (int) ($member['current_streak'] ?? 0) ?> wks</strong>
                        <small class="mt-2 block text-sm text-slate-600"><?= !empty($member['last_attendance_date']) ? 'Last attendance: ' . htmlspecialchars($member['last_attendance_date'], ENT_QUOTES, 'UTF-8') : 'No Sunday attendance yet' ?></small>
                    </article>
                    <article class="rounded-2xl bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Total Attendance</div>
                        <strong class="mt-2 block text-3xl font-extrabold text-slate-900"><?= number_format((int) ($member['total_attendance'] ?? 0)) ?></strong>
                        <small class="mt-2 block text-sm text-slate-600">Longest streak: <?= (int) ($member['longest_streak'] ?? 0) ?> weeks</small>
                    </article>
                </div>
            </section>

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="edit-member-title">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="edit-member-title" class="text-xl font-extrabold text-slate-900">Edit Member</h2>
                        <p class="mt-1 text-sm text-slate-500">Update contact, tent, status, and profile details.</p>
                    </div>
                    <span class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-bold text-slate-600">Profile Details</span>
                </div>

                <form class="grid gap-4" method="POST" action="../members/update" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                    <input type="hidden" name="existing_profile_photo" value="<?= htmlspecialchars($member['profile_photo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Full Name</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-500" type="text" name="full_name" value="<?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Phone</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="tel" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Occupation</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="occupation">
                                <?php foreach (['Student', 'Worker', 'Alumni'] as $occupation): ?>
                                    <option value="<?= $occupation ?>" <?= $member['occupation'] === $occupation ? 'selected' : '' ?>><?= $occupation ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Birth Month</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_month">
                                <option value="">Month</option>
                                <?php for ($month = 1; $month <= 12; $month++): ?>
                                    <option value="<?= $month ?>" <?= (int) $birthMonth === $month ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $month, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Birth Day</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="birth_day">
                                <option value="">Day</option>
                                <?php for ($day = 1; $day <= 31; $day++): ?>
                                    <option value="<?= $day ?>" <?= (int) $birthDay === $day ? 'selected' : '' ?>><?= $day ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">School Name</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="text" name="school_name" value="<?= htmlspecialchars($member['school_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Join Date</span>
                            <input class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" type="date" name="join_date" value="<?= htmlspecialchars($member['join_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Tent</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="tent_id" required <?= ($user['role'] ?? null) === 'Tent Admin' ? 'disabled' : '' ?>>
                                <?php foreach ($tents as $tent): ?>
                                    <option value="<?= (int) $tent['id'] ?>" <?= (int) $member['tent_id'] === (int) $tent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tent['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Status</span>
                            <select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-900 outline-none focus:border-emerald-500" name="active_status">
                                <option value="active" <?= $member['active_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $member['active_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </label>
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Profile Photo</span>
                            <input class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-emerald-700" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp">
                            <?php if (!empty($member['profile_photo'])): ?>
                                <span class="text-xs font-medium text-slate-500">Current photo saved</span>
                            <?php endif; ?>
                        </label>
                        <label class="grid gap-2 sm:col-span-2">
                            <span class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Notes</span>
                            <textarea class="min-h-[140px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none focus:border-emerald-500" name="notes" rows="4"><?= htmlspecialchars($member['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </label>
                    </div>

                    <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#013f26] px-4 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" type="submit">
                        <i data-lucide="save"></i>
                        Save Member
                    </button>
                </form>
            </section>
        </aside>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
