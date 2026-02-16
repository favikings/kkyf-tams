<?php
// includes/footer.php
?>
</main> <!-- End Main Content Wrapper -->

<!-- PWA Install Prompt (Stitch UI Toast) -->
<div id="pwa-install-toast"
    class="fixed bottom-20 left-4 right-4 bg-slate-900/95 backdrop-blur-sm text-white p-4 rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.3)] transform translate-y-full transition-transform duration-300 z-50 hidden md:bottom-8 md:right-8 md:left-auto md:w-80 border border-white/10">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#00BD06]/20 rounded-lg flex items-center justify-center text-[#00BD06]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-sm">Install TAMS</h4>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Native Experience</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button id="pwa-install-btn"
                class="bg-[#00BD06] hover:bg-[#009605] text-white text-xs font-bold py-2 px-4 rounded-full transition-all active:scale-95 shadow-lg shadow-green-500/20">
                Install
            </button>
            <button id="pwa-dismiss-btn" class="text-slate-500 hover:text-white transition-colors p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- PWA Installation Script -->
<script>
    // PWA Install Logic
    let deferredPrompt;
    const installToast = document.getElementById('pwa-install-toast');
    const installBtn = document.getElementById('pwa-install-btn');
    const dismissBtn = document.getElementById('pwa-dismiss-btn');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (installToast) {
            installToast.classList.remove('translate-y-full', 'hidden');
            installToast.classList.add('translate-y-0');
        }
    });

    if (installBtn) {
        installBtn.addEventListener('click', (e) => {
            if (installToast) installToast.classList.add('translate-y-full');
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the A2HS prompt');
                    }
                    deferredPrompt = null;
                });
            }
        });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            if (installToast) installToast.classList.add('translate-y-full');
        });
    }
</script>
</body>

</html>