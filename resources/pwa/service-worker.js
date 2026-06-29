/* BuchaPro PWA service worker — static assets + offline fallback only. */
const CACHE_VERSION = '__CACHE_VERSION__';
const STATIC_CACHE = `buchapro-static-${CACHE_VERSION}`;

const PRECACHE_URLS = [
    '/offline.html',
    '/favicon.ico',
    '/favicon-32x32.png',
    '/favicon-16x16.png',
    '/pwa-icon-192.png',
    '/pwa-icon-512.png',
];

const SKIP_PREFIXES = [
    '/dashboard',
    '/api',
    '/livewire',
    '/storage',
    '/_debugbar',
    '/sanctum',
    '/pwa/',
];

/** Never cache — dynamic PWA endpoints must always hit the network. */
const SKIP_PATHS = new Set([
    '/pwa/service-worker.js',
    '/manifest.webmanifest',
]);

function shouldSkip(url, request) {
    if (request.method !== 'GET') {
        return true;
    }

    if (url.origin !== self.location.origin) {
        return true;
    }

    if (SKIP_PATHS.has(url.pathname)) {
        return true;
    }

    return SKIP_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));
}

function isStaticAsset(pathname) {
    return /\.(css|js|png|jpe?g|gif|webp|svg|ico|woff2?|webmanifest)$/i.test(pathname)
        || pathname.startsWith('/build/');
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('buchapro-static-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (shouldSkip(url, request)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );

        return;
    }

    if (! isStaticAsset(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        }),
    );
});
