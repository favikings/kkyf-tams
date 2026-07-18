<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php $basePath = rtrim(\App\Core\Env::get('BASE_PATH', '/kkyf-tams-1/public'), '/'); ?>

<main class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(27,138,75,0.14),transparent_28%),radial-gradient(circle_at_top_right,rgba(213,183,107,0.16),transparent_24%),linear-gradient(180deg,#faf7f1_0%,#f2eee5_100%)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-5xl items-center justify-center">
        <section class="grid w-full overflow-hidden rounded-[36px] border border-white/80 bg-white/92 shadow-panel backdrop-blur xl:grid-cols-[0.9fr_1.1fr]" aria-labelledby="error-title">
            <div class="hidden bg-[linear-gradient(160deg,#2b1408_0%,#5b2212_50%,#8a3b15_100%)] p-8 text-white xl:flex xl:flex-col xl:justify-between">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-white/68">403</div>
                    <h1 class="mt-4 font-display text-[clamp(3rem,5vw,4rem)] leading-[0.88] tracking-[-0.06em]">This route is protected.</h1>
                    <p class="mt-5 max-w-md text-sm leading-7 text-white/75">Your current account role does not have permission to open this area of the v2 portal.</p>
                </div>
                <div class="rounded-[28px] border border-white/12 bg-white/10 p-5">
                    <strong class="block text-sm font-bold text-white">Access control is working</strong>
                    <span class="mt-2 block text-sm leading-7 text-white/72">Use the main dashboard to continue within the routes assigned to your role.</span>
                </div>
            </div>

            <div class="px-6 py-8 sm:px-8 sm:py-10">
                <div class="mx-auto max-w-xl">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-[22px] bg-orange-50 text-orange-700 shadow-soft">
                        <i data-lucide="shield-alert"></i>
                    </div>
                    <div class="mt-6 text-xs font-extrabold uppercase tracking-[0.18em] text-orange-700">403 Unauthorized</div>
                    <h2 id="error-title" class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Your account does not have permission to access this route.</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-500">If this looks unexpected, verify you are signed in with the right role or ask a Super Admin to review your access level.</p>

                    <div class="mt-8 grid gap-4 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start gap-3">
                            <span class="inline-grid h-10 w-10 place-items-center rounded-full bg-white text-slate-600 shadow-sm"><i data-lucide="lock-keyhole"></i></span>
                            <div>
                                <strong class="block text-sm font-bold text-slate-900">Restricted by role middleware</strong>
                                <p class="mt-1 text-sm leading-6 text-slate-500">The v2 portal is intentionally blocking this route based on account permissions.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-[#013f26] px-5 py-2 text-sm font-bold text-white shadow-soft transition hover:bg-[#035733]" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/dashboard">
                            <i data-lucide="arrow-left"></i>
                            Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
