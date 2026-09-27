<?php

namespace Pms\Backend\Http;

use Pms\Backend\Controller\ShareLandingController;
use Pms\Support\Auth;
use Pms\Support\Flash;

/**
 * Ziel des Betriebssystem-Teilen-Menüs (Share Target API).
 *
 * Ist die App installiert, taucht sie im Teilen-Dialog anderer Apps auf
 * ("An PMS Admin senden") - ein Foto aus der Kamera-App landet so direkt
 * im Bild-Bestand, ohne den Umweg über Herunterladen und manuelles
 * Hochladen. Registriert ist das in manifest.php.
 *
 * Anders als die übrigen Schnittstellen unter Http\ liefert dieser
 * Endpunkt kein JSON: Der Aufruf ist eine echte Seiten-Navigation, die
 * das Betriebssystem auslöst, kein Ajax-Aufruf einer eigenen Seite - eine
 * Weiterleitung ist die einzig sinnvolle Antwort, und zwar zu
 * ShareLandingController: Dort wird entschieden, zu welchem Inhalt das
 * Bild gehört, statt es nur irgendwo in der allgemeinen Bilderliste
 * abzulegen. Aus demselben Grund läuft dieser Endpunkt, wie die anderen
 * Einträge in Kernel::ENDPOINTS, ohne CSRF-Prüfung: Das Betriebssystem
 * kennt unser Token nicht. Auth::isLoggedIn() bleibt trotzdem Pflicht.
 */
final class ShareTargetEndpoint
{
    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        if (!Auth::isLoggedIn()) {
            self::redirect(Routes::path('home'));
        }

        $files = $_FILES['images'] ?? null;
        $stored = [];

        if (is_array($files) && is_array($files['tmp_name'] ?? null)) {
            // Psalm kennt $_FILES nur in der Ein-Datei-Form; bei mehreren
            // Dateien unter demselben Feldnamen liefert PHP hier - anders als
            // der Stub annimmt - für jeden Schlüssel ein eigenes Array.
            /**
             * @psalm-suppress InvalidIterator
             * @psalm-suppress InvalidArrayAccess
             */
            foreach ($files['tmp_name'] as $index => $tmpName) {
                if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $name = link_name((string)($files['name'][$index] ?? ''), '.');
                $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, \Pms\Support\EntityImage::supportedTypes(), true)) {
                    continue;
                }
                $saved = ImageEndpoint::storeFile((string)$tmpName, $name, $extension);
                if ($saved !== null) {
                    $stored[] = $saved['name'];
                }
            }
        }

        if ($stored === []) {
            Flash::error('Das geteilte Bild konnte nicht übernommen werden.');
            self::redirect(Routes::path('item'));
        }

        // ShareLandingController fragt als Nächstes ab, zu welchem Inhalt
        // das Bild gehört - siehe dort SESSION_KEY.
        $pending = $_SESSION[ShareLandingController::SESSION_KEY] ?? [];
        $_SESSION[ShareLandingController::SESSION_KEY] = array_merge($stored, $pending);

        self::redirect(Routes::path('share_landing'));
    }

    private static function redirect(string $target): never
    {
        header('Location: ' . $target);
        exit;
    }
}
