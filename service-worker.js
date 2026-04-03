// service-worker.js
// NutriDeq PWA Engine - Smooth Offline Caching and Rapid Deployment
const CACHE_NAME = 'nutrideq-v1';
const ASSETS_TO_CACHE = [
    'dashboard.php',
    'css/base.css',
    'css/dashboard.css',
    'css/user-premium.css',
    'scripts/user-realtime.js',
    'assets/icon-512x512.png'
];

// 1. Install Event - Cache initial assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('NutriDeq - PWA Engine Primed');
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

// 2. Fetch Event - Serve from cache first (Faster experience)
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});

// 3. Activate Event - Clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        })
    );
});
