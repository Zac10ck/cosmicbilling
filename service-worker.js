const CACHE_NAME = 'cosmic-stock-v2-3';
const STATIC_ASSETS = [
  '/xamp-cosmic/assets/css/style.css?v=2.0.2',
  '/xamp-cosmic/assets/js/app.js?v=2.0.3',
  '/xamp-cosmic/assets/vendor/zxing/index.min.js?v=0.21.3',
  '/xamp-cosmic/assets/js/barcode.js?v=2.0.3',
  '/xamp-cosmic/assets/icons/icon.svg',
  '/xamp-cosmic/offline.html'
];
self.addEventListener('install', event => event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())));
self.addEventListener('activate', event => event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))).then(() => self.clients.claim())));
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== location.origin) return;
  if (url.pathname.indexOf('/assets/') !== -1) {
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request).then(response => { const copy=response.clone(); caches.open(CACHE_NAME).then(cache=>cache.put(event.request,copy)); return response; })));
    return;
  }
  if (event.request.mode === 'navigate') event.respondWith(fetch(event.request).catch(() => caches.match('/xamp-cosmic/offline.html')));
});
