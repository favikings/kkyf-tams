self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  event.respondWith(fetch(event.request));
});

self.addEventListener('push', (event) => {
  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (error) {
      data = { body: event.data.text() };
    }
  }

  const title = typeof data.title === 'string' && data.title !== ''
    ? data.title
    : 'KKYF Membership Portal';
  const options = {
    body: typeof data.body === 'string' ? data.body : 'You have a new notification.',
    icon: typeof data.icon === 'string' ? data.icon : 'assets/icons/icon-192.png',
    badge: typeof data.badge === 'string' ? data.badge : 'assets/icons/icon-192.png',
    tag: typeof data.tag === 'string' ? data.tag : 'kkyf-notification',
    data: { url: typeof data.url === 'string' ? data.url : 'notifications.php' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  let target = new URL('notifications.php', self.registration.scope);
  try {
    const candidate = new URL(event.notification.data?.url || 'notifications.php', self.registration.scope);
    if (candidate.origin === self.location.origin) {
      target = candidate;
    }
  } catch (error) {
    // Keep the safe same-origin fallback.
  }

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of windows) {
      if ('focus' in client) {
        await client.navigate(target.href);
        return client.focus();
      }
    }
    return self.clients.openWindow(target.href);
  })());
});
