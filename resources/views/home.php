<?php require __DIR__ . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.16),transparent_24%),linear-gradient(180deg,#faf7f1_0%,#f2eee5_100%)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
        <section class="grid w-full overflow-hidden rounded-[36px] border border-white/80 bg-white/92 shadow-panel backdrop-blur xl:grid-cols-[1.05fr_0.95fr]" aria-labelledby="page-title">
            <div class="hidden bg-[linear-gradient(160deg,#102017_0%,#173824_48%,#1b8a4b_100%)] p-8 text-white xl:flex xl:flex-col xl:justify-between">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-white/68">Phase 0 Foundation</div>
                    <h1 class="mt-4 font-display text-[clamp(3rem,5vw,4rem)] leading-[0.88] tracking-[-0.06em]">The v2 portal foundation is isolated and ready for controlled build-out.</h1>
                    <p class="mt-5 max-w-md text-sm leading-7 text-white/75">This entry surface exists for local and staging development while the production portal remains untouched.</p>
                </div>
                <div class="rounded-[28px] border border-white/12 bg-white/10 p-5">
                    <strong class="block text-sm font-bold text-white">Current scope</strong>
                    <span class="mt-2 block text-sm leading-7 text-white/72">A safe foundation for iterating on members, tents, attendance, reporting, and later governance workflows.</span>
                </div>
            </div>

            <div class="px-6 py-8 sm:px-8 sm:py-10">
                <div class="mx-auto max-w-xl">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-[22px] bg-emerald-50 text-emerald-700 shadow-soft">
                        <i data-lucide="badge-check"></i>
                    </div>
                    <div class="mt-6 text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-700">Development Entry</div>
                    <h2 id="page-title" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mt-4 text-sm leading-7 text-slate-500">The v2 application foundation is isolated from the live production portal. This screen is meant for local and staging verification only.</p>

                    <div class="mt-8 grid gap-3">
                        <?php foreach ($statusItems as $item): ?>
                            <div class="flex items-start gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="inline-grid h-10 w-10 place-items-center rounded-full bg-white text-emerald-700 shadow-sm"><i data-lucide="check-circle-2"></i></span>
                                <div class="min-w-0 text-sm font-semibold leading-7 text-slate-700"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-8 rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-7 text-amber-900">
                        No members, tents, attendance, reports, SMS, or offline sync features are included in Phase 0.
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-[#013f26] px-5 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">
                            <i data-lucide="log-in"></i>
                            Continue to Portal
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
