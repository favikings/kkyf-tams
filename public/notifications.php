<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/notifications.php';

requireSuperAdmin();

$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'mark_all_read') {
        markAllNotificationsRead((int) $me['id']);
        flash('success', 'All notifications marked as read.');
    } elseif ($action === 'open') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $destination = 'notifications.php';

        if ($notificationId > 0) {
            $lookup = db()->prepare(
                'SELECT action_url FROM notifications WHERE id = ? AND user_id = ?'
            );
            $lookup->execute([$notificationId, (int) $me['id']]);
            $notification = $lookup->fetch();

            if ($notification !== false) {
                markNotificationRead((int) $me['id'], $notificationId);
                $candidate = (string) ($notification['action_url'] ?? '');
                if (preg_match('/\A[a-z0-9-]+\.php(?:\?[A-Za-z0-9_=&%-]+)?\z/', $candidate)) {
                    $destination = $candidate;
                }
            }
        }

        redirect($destination);
    }

    redirect('notifications.php');
}

$notifications = notificationsForUser((int) $me['id']);
$pushConfigured = pushIsConfigured();
$vapidPublicKey = pushPublicKey();
$pageTitle = 'Notifications';

require_once __DIR__ . '/../app/includes/header.php';
?>
<div class="mx-auto max-w-4xl" x-data="pushNotificationSettings(<?= json_encode($vapidPublicKey) ?>, <?= json_encode(csrfToken()) ?>)">
  <header class="mb-6 flex flex-col gap-4 md:mb-8 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="font-display font-semibold text-[28px] leading-9 tracking-[-0.01em] text-on-surface md:text-[32px] md:leading-10">Notifications</h1>
      <p class="mt-1 text-[14px] leading-5 text-on-surface-variant">Registration alerts and notification settings for this device.</p>
    </div>
    <?php if (notificationUnreadCount((int) $me['id']) > 0): ?>
      <form method="post" action="notifications.php">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-outline-variant bg-surface-container px-5 py-2.5 font-display text-[14px] leading-5 font-semibold tracking-[0.02em] text-on-surface active:scale-[0.98] motion-safe:transition-transform">Mark all as read</button>
      </form>
    <?php endif; ?>
  </header>

  <section class="mb-8 rounded-xl bg-surface-lowest p-5 shadow-card md:p-6" aria-labelledby="push-heading">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div class="flex items-start gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-container text-on-primary-container">
          <i data-lucide="bell-ring" class="h-5 w-5"></i>
        </span>
        <div>
          <h2 id="push-heading" class="font-display text-[20px] leading-7 font-semibold text-on-surface">Phone and browser alerts</h2>
          <?php if ($pushConfigured): ?>
            <p class="mt-1 text-[14px] leading-5 text-on-surface-variant" x-text="statusText">Check whether notifications are enabled on this device.</p>
          <?php else: ?>
            <p class="mt-1 text-[14px] leading-5 text-error">Web Push keys are not configured on the server yet.</p>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($pushConfigured): ?>
        <button type="button" @click="toggle()" :disabled="busy || unsupported"
                class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-primary px-5 py-2.5 font-display text-[14px] leading-5 font-semibold tracking-[0.02em] text-on-primary shadow-card disabled:cursor-not-allowed disabled:opacity-50 active:scale-[0.98] motion-safe:transition-transform">
          <span x-text="buttonText">Enable notifications</span>
        </button>
      <?php endif; ?>
    </div>
    <p class="mt-4 text-[12px] leading-4 text-on-surface-variant">On iPhone or iPad, install KKYF Portal on the Home Screen first, open it there, then enable notifications.</p>
  </section>

  <section aria-labelledby="notification-list-heading">
    <h2 id="notification-list-heading" class="mb-4 font-display text-[20px] leading-7 font-semibold text-on-surface">Recent notifications</h2>

    <?php if ($notifications === []): ?>
      <div class="rounded-lg bg-surface-lowest px-6 py-12 text-center shadow-card">
        <i data-lucide="bell" class="mx-auto mb-3 h-8 w-8 text-on-surface-variant/50"></i>
        <p class="text-[16px] leading-6 text-on-surface-variant">No notifications yet.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($notifications as $notification): ?>
          <form method="post" action="notifications.php" class="rounded-lg border <?= $notification['read_at'] === null ? 'border-primary bg-primary-container/30' : 'border-outline-variant bg-surface-lowest' ?> p-4 shadow-card">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="open">
            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
            <button type="submit" class="flex min-h-[44px] w-full items-start gap-3 text-left">
              <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container text-on-primary-container">
                <i data-lucide="user-plus" class="h-5 w-5"></i>
              </span>
              <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2">
                  <span class="font-display text-[16px] leading-6 font-semibold text-on-surface"><?= e($notification['title']) ?></span>
                  <?php if ($notification['read_at'] === null): ?>
                    <span class="inline-flex rounded-full bg-primary px-2 py-0.5 font-display text-[12px] leading-4 font-medium text-on-primary">New</span>
                  <?php endif; ?>
                </span>
                <span class="mt-0.5 block text-[14px] leading-5 text-on-surface-variant"><?= e($notification['message']) ?></span>
                <span class="mt-1 block text-[12px] leading-4 text-on-surface-variant/70"><?= e(date('M j, Y g:ia', strtotime((string) $notification['created_at']))) ?></span>
              </span>
              <i data-lucide="chevron-right" class="mt-2 h-5 w-5 shrink-0 text-on-surface-variant"></i>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
function pushNotificationSettings(vapidPublicKey, csrf) {
  return {
    busy: false,
    subscribed: false,
    unsupported: false,
    statusText: 'Checking notification status…',
    get buttonText() {
      if (this.busy) return 'Please wait…';
      return this.subscribed ? 'Disable notifications' : 'Enable notifications';
    },
    async init() {
      this.unsupported = !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window);
      if (this.unsupported) {
        this.statusText = 'This browser does not support Web Push notifications.';
        return;
      }
      const registration = await navigator.serviceWorker.ready;
      this.subscribed = (await registration.pushManager.getSubscription()) !== null;
      this.statusText = this.subscribed
        ? 'Notifications are enabled on this device.'
        : Notification.permission === 'denied'
          ? 'Notifications are blocked in this device’s browser settings.'
          : 'Enable alerts for new Tent Admin registration requests.';
    },
    decodeKey(value) {
      const padding = '='.repeat((4 - value.length % 4) % 4);
      const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
      const raw = atob(base64);
      return Uint8Array.from([...raw].map(char => char.charCodeAt(0)));
    },
    async toggle() {
      if (this.busy || this.unsupported) return;
      this.busy = true;
      try {
        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (subscription) {
          const endpoint = subscription.endpoint;
          await this.persist({ action: 'unsubscribe', endpoint });
          await subscription.unsubscribe();
          this.subscribed = false;
          this.statusText = 'Notifications are disabled on this device.';
          window.notyf.success('Notifications disabled.');
          return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
          this.statusText = 'Notification permission was not granted.';
          window.notyf.error('Notification permission was not granted.');
          return;
        }

        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: this.decodeKey(vapidPublicKey),
        });
        const json = subscription.toJSON();
        await this.persist({
          action: 'subscribe',
          endpoint: subscription.endpoint,
          public_key: json.keys?.p256dh || '',
          auth_token: json.keys?.auth || '',
          content_encoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
        });
        this.subscribed = true;
        this.statusText = 'Notifications are enabled on this device.';
        window.notyf.success('Notifications enabled.');
      } catch (error) {
        this.statusText = 'Could not update notification settings. Please try again.';
        window.notyf.error(error instanceof Error ? error.message : 'Could not update notifications.');
      } finally {
        this.busy = false;
      }
    },
    async persist(payload) {
      const response = await fetch('api/push-subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ ...payload, csrf }),
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.error || 'Could not save notification settings.');
      }
    },
  };
}
</script>
<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
