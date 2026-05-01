/**
 * Nexus Notes - Service Worker
 * Aggressive caching strategy for offline support
 */

const CACHE_NAME = 'nexus-notes-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';

// Static assets to cache immediately
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/assets/css/style.css',
    '/assets/js/utils.js',
    '/assets/js/calendar.js',
    '/assets/js/editor.js',
    '/assets/js/app.js',
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/lucide@latest',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .catch((err) => console.log('Cache install error:', err))
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip chrome-extension and other non-http(s) requests
    if (!url.protocol.startsWith('http')) return;
    
    // API requests - network first
    if (url.pathname.includes('api.php')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(DYNAMIC_CACHE).then((cache) => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    return caches.match(request);
                })
        );
        return;
    }
    
    // Static assets - cache first
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        // Return cached version while updating in background
                        fetchAndCache(request);
                        return cachedResponse;
                    }
                    return fetchAndCache(request);
                })
                .catch(() => {
                    // Offline fallback for navigation
                    if (request.mode === 'navigate') {
                        return caches.match('/index.php');
                    }
                })
        );
        return;
    }
    
    // Default - network first with cache fallback
    event.respondWith(
        fetch(request)
            .then((response) => {
                const responseClone = response.clone();
                caches.open(DYNAMIC_CACHE).then((cache) => {
                    cache.put(request, responseClone);
                });
                return response;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});

// Check if URL is a static asset
function isStaticAsset(url) {
    const staticPatterns = [
        /\.css$/,
        /\.js$/,
        /\.woff2?$/,
        /\.ttf$/,
        /\.eot$/,
        /\.svg$/,
        /\.png$/,
        /\.jpg$/,
        /\.jpeg$/,
        /\.gif$/,
        /\.ico$/,
        /tailwindcss\.com/,
        /unpkg\.com/,
        /fonts\.googleapis\.com/
    ];
    
    return staticPatterns.some(pattern => pattern.test(url.href));
}

// Fetch and cache helper
async function fetchAndCache(request) {
    const response = await fetch(request);
    const cache = await caches.open(DYNAMIC_CACHE);
    cache.put(request, response.clone());
    return response;
}

// Background sync for offline note edits
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-notes') {
        event.waitUntil(syncNotes());
    }
});

async function syncNotes() {
    // Get pending notes from IndexedDB (would need implementation)
    console.log('Syncing pending notes...');
}

// Push notifications (future feature)
self.addEventListener('push', (event) => {
    const options = {
        body: event.data?.text() || 'New notification',
        icon: '/icon-192.png',
        badge: '/badge-72.png',
        vibrate: [100, 50, 100],
        data: { dateOfArrival: Date.now() }
    };
    
    event.waitUntil(
        self.registration.showNotification('Nexus Notes', options)
    );
});
