// Minimal service worker — required by Chrome/Edge to offer the "Install app"
// prompt. This app is used online only, so it does no offline caching: every
// request is simply passed through to the network unchanged.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => event.respondWith(fetch(event.request)));
