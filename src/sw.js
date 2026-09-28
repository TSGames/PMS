/**
 * Service Worker der installierten Backend-App.
 *
 * Bewusst zurückhaltend: Das Backend zeigt angemeldete, sich ständig
 * ändernde Daten - ein gecachtes Formular oder eine gecachte Übersicht
 * wäre schlimmer als gar keine. Gecacht werden nur die immer gleichen
 * Bausteine (Stylesheets, Skripte, Symbole); Seitenaufrufe und alles
 * andere (insbesondere jedes Formular-POST) gehen unverändert ins Netz.
 * Ohne einen Service Worker mit fetch-Handler böten die meisten Browser
 * das Installieren erst gar nicht an - mehr braucht es dafür nicht.
 */

const CACHE = 'pms-admin-shell-v2';

const SHELL_ASSETS = [
    'admin.css',
    'crop_modal.css',
    'admin-favicon.svg',
    'app-icons/icon-192.png',
    'app-icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL_ASSETS).catch(() => {}))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

const STATIC_EXTENSIONS = /\.(css|js|png|svg|ico|woff2?)$/;

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Nur lesende, gleich bleibende Anfragen behandeln - alles andere
    // (Seitenaufrufe mit Sitzungsdaten, jedes Formular-POST, die
    // JSON-Schnittstellen) läuft am Service Worker vorbei direkt ins Netz.
    if (request.method !== 'GET' || !STATIC_EXTENSIONS.test(new URL(request.url).pathname)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const network = fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }
                return response;
            }).catch(() => cached);

            return cached || network;
        })
    );
});

// Push-Nachrichten (Wochenbericht, siehe Backend\Push\PushService). Die
// Nachricht bringt Titel, Text und die zu öffnende Adresse als JSON mit.
self.addEventListener('push', (event) => {
    let message = {};
    try {
        message = event.data ? event.data.json() : {};
    } catch (e) {
        message = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(message.title || 'PMS Administration', {
        body: message.body || '',
        icon: 'app-icons/icon-192.png',
        badge: 'app-icons/icon-192.png',
        tag: 'pms-weekly-report',
        data: { url: message.url || self.registration.scope },
    }));
});

// Tippen auf die Nachricht: ein schon offenes Fenster der App nach vorn
// holen, sonst eines öffnen.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : self.registration.scope, self.location.href).href;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        for (const client of windows) {
            if (client.url.startsWith(self.registration.scope) && 'focus' in client) {
                return client.navigate(url).then((navigated) => (navigated || client).focus());
            }
        }
        return self.clients.openWindow(url);
    }));
});
