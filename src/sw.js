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
