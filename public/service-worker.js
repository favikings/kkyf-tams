const CACHE_NAME = 'kkyf-tams-v6';

const ASSETS_TO_CACHE = [
  './',
  './offline.html',
  './manifest.json',
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/images/icon-192.png',
  './assets/images/icon-512.png',
  './assets/images/logo.jpg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_TO_CACHE))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => Promise.all(
      keyList.map((key) => (key !== CACHE_NAME ? caches.delete(key) : Promise.resolve()))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match('./offline.html'))
    );
    return;
  }

  if (event.request.url.startsWith(self.location.origin)) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) {
          return cached;
        }

        return fetch(event.request).then((response) => {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
          return response;
        });
      })
    );
  }
});

self.addEventListener('sync', (event) => {
  if (event.tag !== 'attendance-sync') {
    return;
  }

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      clients.forEach((client) => {
        client.postMessage({ type: 'offline-attendance-sync' });
      });
    })
  );
});
