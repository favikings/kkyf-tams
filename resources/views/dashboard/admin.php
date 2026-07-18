<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php require dirname(__DIR__) . '/partials/app-shell-start.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<section class="mx-auto w-full max-w-[980px] py-8" aria-labelledby="admin-title">
    <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-panel">
        <div class="bg-[linear-gradient(135deg,#102017_0%,#173824_55%,#1b8a4b_100%)] px-6 py-8 text-white sm:px-8">
            <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-white/70">Super Admin Only</div>
            <h1 id="admin-title" class="mt-3 font-display text-[clamp(2.4rem,5vw,3.3rem)] leading-[0.92] tracking-[-0.05em]">Protected Admin Area</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/78">Super Admin middleware is active for this route. Use this surface for governance tools, sensitive controls, and later-phase administration features.</p>
        </div>

        <div class="grid gap-5 px-6 py-6 sm:px-8 sm:py-8">
            <div class="grid gap-4 md:grid-cols-2">
                <article class="rounded-[24px] border border-emerald-100 bg-emerald-50 p-5">
                    <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-800">Access Status</span>
                    <strong class="mt-3 block text-2xl font-extrabold text-slate-900">Authorized</strong>
                    <p class="mt-2 text-sm leading-7 text-slate-600">This page is currently restricted to Super Admin accounts.</p>
                </article>
                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                    <span class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">Planned Use</span>
                    <strong class="mt-3 block text-2xl font-extrabold text-slate-900">Governance Tools</strong>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Reserved for protected controls as the v2 portal expands beyond the current workflow set.</p>
                </article>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-[#013f26] px-5 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/tents">
                    <i data-lucide="network"></i>
                    Manage Tents
                </a>
                <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                    <i data-lucide="layout-dashboard"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/app-shell-end.php'; ?>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
