<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$footerFlashes = getFlashes();
?>
    </main>
    <?php if (isLoggedIn()): ?>
    <div x-cloak x-show="drawerOpen" class="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Navigation menu" @keydown.escape.window="drawerOpen = false">
      <div class="absolute inset-0 bg-inverse-surface/40 backdrop-blur-md"
           x-transition:enter="transition-opacity ease-out duration-200"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition-opacity ease-in duration-150"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           @click="drawerOpen = false"></div>
      <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-primary text-on-primary shadow-elevated"
             x-transition:enter="transition-transform ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
        <div class="flex min-h-[64px] shrink-0 items-center justify-between gap-2.5 px-5">
          <div class="flex min-w-0 items-center gap-2.5">
            <img src="assets/icons/icon-192.png" alt="KKYF" class="w-9 h-9 rounded-lg">
            <span class="font-display text-[16px] leading-6 font-semibold tracking-[-0.01em] text-on-primary"><?= e(APP_NAME) ?></span>
          </div>
          <button type="button" @click="drawerOpen = false" aria-label="Close menu"
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full hover:bg-on-primary/10">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
          <?php foreach ($navItems as $item): ?>
            <a href="<?= e($item['href']) ?>" @click="drawerOpen = false"
               class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2.5 text-[14px] leading-5 <?= $item['active'] ? 'bg-on-primary/15 font-medium text-on-primary' : 'text-on-primary/85 hover:bg-on-primary/10' ?>">
              <i data-lucide="<?= e($item['icon']) ?>" class="h-5 w-5 shrink-0"></i>
              <span class="font-display"><?= e($item['label']) ?></span>
            </a>
          <?php endforeach; ?>

          <?php if (isSuperAdmin()): ?>
            <div class="px-3 pb-2 pt-5 font-display text-[12px] leading-4 font-medium tracking-[0.04em] uppercase text-on-primary/60">Admin</div>
            <?php foreach ($adminNavItems as $item): ?>
              <a href="<?= e($item['href']) ?>" @click="drawerOpen = false"
                 class="flex min-h-[44px] items-center gap-3 rounded-md px-3 py-2.5 text-[14px] leading-5 <?= $item['active'] ? 'bg-on-primary/15 font-medium text-on-primary' : 'text-on-primary/85 hover:bg-on-primary/10' ?>">
                <i data-lucide="<?= e($item['icon']) ?>" class="h-5 w-5 shrink-0"></i>
                <span class="font-display"><?= e($item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </nav>

        <div class="shrink-0 border-t border-on-primary/10 p-3">
          <div class="flex items-center gap-3 px-3 py-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-on-primary/15 font-display text-[12px] leading-4 font-semibold text-on-primary"><?= e($initials) ?></span>
            <div class="min-w-0 flex-1">
              <div class="truncate font-display text-[14px] leading-5 font-semibold text-on-primary"><?= e($user['name']) ?></div>
              <div class="truncate text-[12px] leading-4 text-on-primary/60"><?= e($userRoleLabel) ?></div>
            </div>
          </div>
          <div class="mt-1 flex items-center justify-between gap-2 border-t border-on-primary/10 px-2 pt-3">
            <button type="button" @click="$store.theme.toggle()" aria-label="Toggle dark mode"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full hover:bg-on-primary/10">
              <i data-lucide="sun" class="h-5 w-5" x-show="$store.theme.dark" x-cloak></i>
              <i data-lucide="moon" class="h-5 w-5" x-show="!$store.theme.dark" x-cloak></i>
            </button>
            <a href="logout.php" @click="drawerOpen = false"
               class="flex h-11 flex-1 items-center justify-center gap-2 rounded-full text-[14px] leading-5 font-medium text-on-primary/85 hover:bg-on-primary/10">
              <i data-lucide="log-out" class="h-5 w-5"></i>
              <span class="font-display">Log out</span>
            </a>
          </div>
        </div>
      </aside>
    </div>
    <?php endif; ?>
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
