/**
 * Push-Benachrichtigungen (Wochenbericht) für dieses Gerät ein- und
 * ausschalten - die Karte "Benachrichtigungen auf diesem Gerät" auf der
 * Seite Wochenbericht.
 *
 * Der Service Worker (sw.js) wird von admin-pwa-install.js registriert;
 * hier kommt nur das Push-Abo dazu. Das Abo selbst bewahrt der Browser auf,
 * der Server (Http\PushEndpoint) bekommt eine Kopie zum Versenden.
 */
(function () {
    'use strict';

    /** Der öffentliche VAPID-Schlüssel kommt als Base64url, subscribe() will Bytes. */
    function keyToBytes(base64) {
        var padded = (base64 + '===='.slice((base64.length % 4) || 4)).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(padded);
        var bytes = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) {
            bytes[i] = raw.charCodeAt(i);
        }
        return bytes;
    }

    function init() {
        var card = document.getElementById('push-settings');
        if (!card) {
            return;
        }

        var status = card.querySelector('[data-push-status]');
        var enable = card.querySelector('[data-push-enable]');
        var disable = card.querySelector('[data-push-disable]');
        var test = card.querySelector('[data-push-test]');

        function show(element, visible) {
            element.style.display = visible ? '' : 'none';
        }

        function setStatus(text) {
            status.textContent = text;
        }

        function post(fields) {
            var body = new FormData();
            Object.keys(fields).forEach(function (key) {
                body.append(key, fields[key]);
            });
            body.append('pms_token', window.PMS_TOKEN || '');

            return fetch(card.getAttribute('data-endpoint'), { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || data.error) {
                            throw new Error(data.error || ('Fehler ' + response.status));
                        }
                        return data;
                    });
                });
        }

        function register(subscription) {
            var json = subscription.toJSON();
            return post({ do: 'subscribe', endpoint: json.endpoint, p256dh: json.keys.p256dh, auth: json.keys.auth });
        }

        function refresh() {
            return navigator.serviceWorker.ready
                .then(function (registration) {
                    return registration.pushManager.getSubscription();
                })
                .then(function (subscription) {
                    show(enable, !subscription);
                    show(disable, !!subscription);
                    show(test, !!subscription);
                    if (!subscription) {
                        setStatus('Dieses Gerät bekommt noch keine Benachrichtigungen.');
                        return null;
                    }
                    setStatus('Aktiv: Dieses Gerät bekommt den Wochenbericht.');
                    // Falls der Server das Abo verloren hat (z.B. nach einer
                    // Wiederherstellung), stillschweigend erneut melden
                    return register(subscription).catch(function () {});
                });
        }

        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            setStatus(/iphone|ipad|ipod/i.test(navigator.userAgent)
                ? 'Auf dem iPhone gibt es Benachrichtigungen nur in der installierten App: Teilen-Symbol, "Zum Home-Bildschirm", dann von dort öffnen.'
                : 'Dieser Browser unterstützt keine Push-Benachrichtigungen.');
            return;
        }

        if (Notification.permission === 'denied') {
            setStatus('Benachrichtigungen sind für diese Seite im Browser blockiert. Sie lassen sich in den Website-Einstellungen des Browsers wieder erlauben.');
            return;
        }

        enable.addEventListener('click', function () {
            enable.disabled = true;
            Notification.requestPermission()
                .then(function (permission) {
                    if (permission !== 'granted') {
                        throw new Error('Benachrichtigungen wurden nicht erlaubt.');
                    }
                    return navigator.serviceWorker.ready;
                })
                .then(function (registration) {
                    return registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: keyToBytes(card.getAttribute('data-key')),
                    });
                })
                .then(register)
                .then(refresh)
                .catch(function (error) {
                    setStatus('Aktivieren fehlgeschlagen: ' + error.message);
                })
                .finally(function () {
                    enable.disabled = false;
                });
        });

        disable.addEventListener('click', function () {
            disable.disabled = true;
            navigator.serviceWorker.ready
                .then(function (registration) {
                    return registration.pushManager.getSubscription();
                })
                .then(function (subscription) {
                    if (!subscription) {
                        return null;
                    }
                    return post({ do: 'unsubscribe', endpoint: subscription.endpoint })
                        .then(function () {
                            return subscription.unsubscribe();
                        });
                })
                .then(refresh)
                .catch(function (error) {
                    setStatus('Abschalten fehlgeschlagen: ' + error.message);
                })
                .finally(function () {
                    disable.disabled = false;
                });
        });

        test.addEventListener('click', function () {
            test.disabled = true;
            setStatus('Testnachricht wird verschickt…');
            post({ do: 'test' })
                .then(function (result) {
                    setStatus(result.sent > 0
                        ? 'Testnachricht verschickt - sie sollte gleich erscheinen.'
                        : 'Die Testnachricht konnte nicht zugestellt werden.');
                })
                .catch(function (error) {
                    setStatus('Testnachricht fehlgeschlagen: ' + error.message);
                })
                .finally(function () {
                    test.disabled = false;
                });
        });

        refresh().catch(function (error) {
            setStatus('Status unbekannt: ' + error.message);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
