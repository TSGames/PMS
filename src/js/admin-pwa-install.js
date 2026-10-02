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
        // iPadOS meldet sich als Mac - erkennbar nur am Touchscreen
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent)
            || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    }

    /** Anleitung je nach Browser: Das Teilen-Symbol sitzt jeweils woanders. */
    function iosInstructions() {
        var ua = window.navigator.userAgent;
        var steps;
        if (/crios|edgios/i.test(ua)) {
            steps = 'Teilen-Symbol (Quadrat mit Pfeil) in der Adressleiste antippen, dann "Zum Home-Bildschirm".';
        } else if (/fxios/i.test(ua)) {
            steps = 'Menü (☰) → Teilen antippen, dann "Zum Home-Bildschirm".';
        } else {
            steps = 'Teilen-Symbol (Quadrat mit Pfeil) antippen - in neueren iOS-Versionen über "…" neben der Adresse - '
                + 'dann "Zum Home-Bildschirm" und "Als Web-App öffnen" eingeschaltet lassen.';
        }
        return 'Dieses Backend lässt sich als App auf den Home-Bildschirm legen: ' + steps;
    }

    function showBanner(text, actionLabel, onAction) {
        // Das Skript steht im <head>; dort gibt es document.body noch nicht
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', function () {
                showBanner(text, actionLabel, onAction);
            });
            return;
        }
        var existing = document.getElementById('pwa-install-banner');
        if (existing) {
            // Ein reiner Hinweis weicht dem echten Installieren-Knopf
            if (!actionLabel || existing.querySelector('.pwa-install-action')) {
                return;
            }
            existing.remove();
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

    // Service Worker: nur fürs Backend zuständig, rührt die öffentliche
    // Website unter derselben Adresse nicht an. Scope ".../admin" ohne
    // abschließenden Schrägstrich: Mit "/admin/" fiel ausgerechnet die
    // Start-Adresse des Manifests (/admin) nicht darunter.
    //
    // Sofort registrieren statt erst nach "load": Chrome prüft die
    // Installierbarkeit erst, wenn der Service Worker steht - je früher,
    // desto früher kann "beforeinstallprompt" kommen.
    if ('serviceWorker' in navigator) {
        var oldScope = window.location.origin + base() + '/admin/';
        navigator.serviceWorker.getRegistrations().then(function (registrations) {
            registrations.forEach(function (registration) {
                if (registration.scope === oldScope) {
                    registration.unregister();
                }
            });
        }).catch(function () {});
        navigator.serviceWorker.register(base() + '/sw.js', { scope: base() + '/admin' }).catch(function () {});
    }

    if (isStandalone() || isDismissed()) {
        return;
    }

    var INSTALL_TEXT = 'Dieses Backend lässt sich als App installieren - schneller Zugriff vom Homescreen, eigenes Fenster ohne Adressleiste.';
    var MENU_TEXT = 'Der Knopf funktioniert erst nach kurzer Nutzung der Seite. Sofort geht es über das Browser-Menü (⋮ oben rechts) → "App installieren".';

    // Den nativen Dialog darf eine Seite nur mit dem Ereignis
    // "beforeinstallprompt" öffnen, und das gibt Chrome auf Android erst
    // nach etwas Nutzung frei (ein Tippen, rund 30 Sekunden) - nach einem
    // Ablehnen lange gar nicht mehr. Der Knopf öffnet deshalb den Dialog,
    // sobald das Ereignis da ist, und erklärt bis dahin den Menü-Weg.
    var deferredPrompt = null;

    function setBannerText(text) {
        var element = document.querySelector('#pwa-install-banner .pwa-install-text');
        if (element) {
            element.textContent = text;
        }
    }

    function install() {
        if (!deferredPrompt) {
            setBannerText(MENU_TEXT);
            return;
        }
        var prompt = deferredPrompt;
        deferredPrompt = null;
        prompt.prompt();
        prompt.userChoice.then(function (choice) {
            if (choice.outcome === 'accepted') {
                dismiss();
            } else {
                // Einmal abgelehnt, liefert Chrome das Ereignis so bald nicht wieder
                setBannerText(MENU_TEXT);
            }
        }).catch(function () {});
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        showBanner(INSTALL_TEXT, 'Installieren', install);
        setBannerText(INSTALL_TEXT);
    });

    window.addEventListener('appinstalled', dismiss);

    // iOS kennt "beforeinstallprompt" in keinem Browser - eigener Hinweis
    // mit Anleitung fürs Teilen-Menü. Seit iOS 16.4 können das auch Chrome,
    // Edge und Firefox, nicht mehr nur Safari; früher sahen Nutzer dieser
    // Browser gar nichts.
    if (isIos()) {
        showBanner(iosInstructions(), null, null);
        return;
    }

    // Auf Android das Banner sofort zeigen, nicht erst wenn Chrome das
    // Ereignis liefert - installierbar ist die App über das Menü ja schon.
    // Desktop-Browser ohne Ereignis (z.B. Firefox) können gar nicht
    // installieren; dort bleibt es beim Banner auf das Ereignis hin.
    if (/android/i.test(window.navigator.userAgent)) {
        showBanner(INSTALL_TEXT, 'Installieren', install);
    }
})();
