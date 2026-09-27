<?php

namespace Pms\Backend\Http;

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
 * Weiterleitung zurück in die Inhalte-Übersicht ist die einzig sinnvolle
 * Antwort. Aus demselben Grund läuft er, wie die anderen Einträge in
 * Kernel::ENDPOINTS, ohne CSRF-Prüfung: Das Betriebssystem kennt unser
 * Token nicht. Auth::isLoggedIn() bleibt trotzdem Pflicht.
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
        $stored = 0;

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
                if (ImageEndpoint::storeFile((string)$tmpName, $name, $extension) !== null) {
                    $stored++;
                }
            }
        }

        if ($stored > 0) {
            Flash::success($stored === 1
                ? 'Ein geteiltes Bild wurde hochgeladen - im Bild-Dialog eines Inhalts steht es jetzt zur Auswahl.'
                : $stored . ' geteilte Bilder wurden hochgeladen - im Bild-Dialog eines Inhalts stehen sie jetzt zur Auswahl.');
        } else {
            Flash::error('Das geteilte Bild konnte nicht übernommen werden.');
        }

        self::redirect(Routes::path('item'));
    }

    private static function redirect(string $target): never
    {
        header('Location: ' . $target);
        exit;
    }
}
