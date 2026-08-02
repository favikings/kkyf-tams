<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$footerFlashes = getFlashes();
?>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.lucide) {
        window.lucide.createIcons();
      }
    });
  </script>
  <?php if ($footerFlashes !== []): ?>
  <script>
    <?php foreach ($footerFlashes as $flash): ?>
    window.notyf.<?= $flash['type'] === 'error' ? 'error' : 'success' ?>(<?= json_encode($flash['message'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>);
    <?php endforeach; ?>
  </script>
  <?php endif; ?>

  <?php if (isLoggedIn()): ?>
  <div x-data="installSheet()">
    <div x-show="androidPath || iosPath" x-cloak
         class="fixed inset-0 z-50 flex items-end justify-center bg-inverse-surface/40 backdrop-blur-md md:items-center">
      <div class="w-full bg-surface-lowest rounded-t-xl p-5 shadow-elevated md:max-w-md md:rounded-xl md:p-6" @click.outside="hide()">
        <div x-show="androidPath">
          <div class="flex items-start gap-3">
            <img src="assets/icons/icon-192.png" alt="KKYF Portal" class="h-11 w-11 shrink-0 rounded-lg">
            <div class="min-w-0 flex-1">
              <h2 class="font-display font-semibold text-[20px] leading-7 text-on-surface">Install KKYF Portal</h2>
              <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Get quick access from your home screen — no app store needed.</p>
              <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" @click="dismiss()" class="min-h-[44px] px-5 py-2.5 rounded-md bg-surface-container text-on-surface border border-outline-variant font-display font-semibold text-[14px] leading-5 tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform">Not now</button>
                <button type="button" @click="install()" class="min-h-[44px] px-5 py-2.5 rounded-md bg-primary text-on-primary font-display font-semibold text-[14px] leading-5 tracking-[0.02em] shadow-card active:scale-[0.98] motion-safe:transition-transform">Install</button>
              </div>
            </div>
          </div>
        </div>

        <div x-show="iosPath">
          <div class="flex items-start gap-3">
            <img src="assets/icons/icon-192.png" alt="KKYF Portal" class="h-11 w-11 shrink-0 rounded-lg">
            <div class="min-w-0 flex-1">
              <h2 class="font-display font-semibold text-[20px] leading-7 text-on-surface">Install KKYF Portal</h2>
              <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">
                Tap the <i data-lucide="share" class="mx-0.5 inline h-4 w-4 align-[-2px] text-on-surface"></i> Share icon, then &ldquo;Add to Home Screen&rdquo;.
              </p>
              <div class="mt-5 flex items-center justify-end">
                <button type="button" @click="dismiss()" class="min-h-[44px] px-5 py-2.5 rounded-md bg-surface-container text-on-surface border border-outline-variant font-display font-semibold text-[14px] leading-5 tracking-[0.02em] active:scale-[0.98] motion-safe:transition-transform">Got it</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function installSheet() {
      return {
        get store() {
          return Alpine.store('installPrompt');
        },
        get androidPath() {
          const s = this.store;
          return s.show && s.deferredEvent !== null;
        },
        get iosPath() {
          const s = this.store;
          return s.show && s.deferredEvent === null && s.isIos;
        },
        init() {
          const s = this.store;
          if (s.isIos && !s.isStandalone && !s.dismissed()) {
            s.show = true;
          }
        },
        async install() {
          const s = this.store;
          s.deferredEvent.prompt();
          await s.deferredEvent.userChoice;
          s.show = false;
          s.deferredEvent = null;
        },
        dismiss() {
          this.store.dismissNow();
        },
        hide() {
          const s = this.store;
          s.show = false;
          s.deferredEvent = null;
        },
      };
    }
  </script>
  <?php endif; ?>
</body>
</html>
