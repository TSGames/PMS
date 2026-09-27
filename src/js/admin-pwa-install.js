/**
 * Macht das Backend installierbar und zeigt das prominent an.
 *
 * Registriert den Service Worker (Voraussetzung für die Installation in
 * praktisch jedem Browser) und legt bei Gelegenheit ein Banner darüber,
 * statt auf die versteckten Browser-Menüs zu vertrauen - die findet kaum
 * jemand von selbst. Chrome/Edge/Android liefern dafür das Ereignis
 * "beforeinstallprompt"; Safari auf iOS kennt das nicht und bekommt
 * stattdessen eine Kurzanleitung fürs Teilen-Menü.
 */
(function () {
    'use strict';

    var DISMISS_KEY = 'pwaInstallDismissedUntil';
    var DISMISS_DAYS = 14;

    function base() {
        return window.PMS_BASE_PATH || '';
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function isDismissed() {
        try {
            var until = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
            return until > Date.now();
        } catch (e) {
            return false;
        }
    }

    function dismiss() {
        try {
            localStorage.setItem(DISMISS_KEY, String(Date.now() + DISMISS_DAYS * 86400000));
        } catch (e) {}
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function isSafari() {
        var ua = window.navigator.userAgent;
        return /safari/i.test(ua) && !/crios|fxios|edgios/i.test(ua);
    }

    function showBanner(text, actionLabel, onAction) {
        if (document.getElementById('pwa-install-banner')) {
            return;
        }
        var banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML =
            '<span class="pwa-install-text">' + text + '</span>'
            + '<span class="pwa-install-actions">'
            + (actionLabel ? '<button type="button" class="pwa-install-action">' + actionLabel + '</button>' : '')
            + '<button type="button" class="pwa-install-close" aria-label="Schließen">&times;</button>'
            + '</span>';
        document.body.appendChild(banner);

        if (actionLabel) {
            banner.querySelector('.pwa-install-action').addEventListener('click', function () {
                onAction();
            });
        }
        banner.querySelector('.pwa-install-close').addEventListener('click', dismiss);
    }

    // Service Worker: nur fürs Backend zuständig (Scope .../admin/), rührt
    // die öffentliche Website unter derselben Adresse nicht an.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(base() + '/sw.js', { scope: base() + '/admin/' }).catch(function () {});
        });
    }

    if (isStandalone() || isDismissed()) {
        return;
    }

    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        showBanner(
            'Dieses Backend lässt sich als App installieren - schneller Zugriff vom Homescreen, eigenes Fenster ohne Adressleiste.',
            'Installieren',
            function () {
                var banner = document.getElementById('pwa-install-banner');
                if (banner) banner.remove();
                deferredPrompt.prompt();
                deferredPrompt.userChoice.finally(function () {
                    deferredPrompt = null;
                });
            }
        );
    });

    window.addEventListener('appinstalled', dismiss);

    // Safari (iOS) löst "beforeinstallprompt" nie aus - eigener Hinweis mit
    // Anleitung fürs Teilen-Menü, das "Zum Home-Bildschirm" enthält.
    if (isIos() && isSafari()) {
        showBanner(
            'Dieses Backend lässt sich zum Home-Bildschirm hinzufügen: Teilen-Symbol antippen, dann "Zum Home-Bildschirm".',
            null,
            null
        );
    }
})();
