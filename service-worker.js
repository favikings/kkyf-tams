const CACHE_NAME = 'kkyf-tams-v1';
const ASSETS_TO_CACHE = [
  '/index.php',
  '/offline.html',
  '/manifest.json'
  // Add other CSS/JS files here as they are created
];

// Install Event: Cache Shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Caching App Shell');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate Event: Cleanup Old Caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          console.log('[Service Worker] Removing old cache', key);
          return caches.delete(key);
        }
      }));
    })
  );
  self.clients.claim();
});

// Fetch Event: Network First, Fallback to Cache, Fallback to Offline Page
self.addEventListener('fetch', (event) => {
  if (event.request.mode !== 'navigate') {
    return;
  }
  event.respondWith(
    fetch(event.request)
      .catch(() => {
          return caches.open(CACHE_NAME)
              .then((cache) => {
                  return cache.match(event.request)
                      .then((matching) => {
                          return matching || cache.match('/offline.html');
                      });
              });
      })
  );
});
