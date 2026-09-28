<?php

namespace Pms\Backend\Http;

use Pms\Backend\Controller\ShareLandingController;
use Pms\Support\Auth;
use Pms\Support\EntityImage;
use Pms\Support\Flash;
use Pms\Support\Html;

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

        $stored = [];
        $problems = [];

        foreach (self::files() as $file) {
            $label = $file['name'] !== '' ? '"' . $file['name'] . '"' : 'Die Datei';

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $problems[] = $label . ': ' . self::uploadError($file['error']);
                self::log($file, 'Upload-Fehler ' . $file['error']);
                continue;
            }

            // Am Inhalt erkennen, nicht an der Endung: Apps liefern geteilte
            // Fotos teils ohne oder mit abweichender Endung
            $extension = self::imageExtension($file['tmp_name']);
            if ($extension === null) {
                $problems[] = $label . ' ist kein unterstütztes Bild (JPG, PNG oder GIF'
                    . ($file['type'] !== '' ? ', gesendet wurde ' . $file['type'] : '') . ').';
                self::log($file, 'Format nicht unterstützt');
                continue;
            }

            $base = (string)pathinfo(link_name($file['name'], '.'), PATHINFO_FILENAME);
            $name = ($base !== '' ? $base : 'geteiltes_bild') . '.' . $extension;
            $saved = ImageEndpoint::storeFile($file['tmp_name'], $name, $extension);
            if ($saved === null) {
                $problems[] = $label . ' konnte nicht gespeichert werden - ist images/uploads für den Webserver beschreibbar?';
                self::log($file, 'Ablegen in images/uploads fehlgeschlagen');
                continue;
            }
            $stored[] = $saved['name'];
        }

        if ($stored === []) {
            if ($problems === []) {
                $problems[] = self::nothingReceived();
                error_log('PMS Teilen: keine Datei empfangen, CONTENT_LENGTH=' . ($_SERVER['CONTENT_LENGTH'] ?? '?'));
            }
            Flash::error('Das geteilte Bild konnte nicht übernommen werden. ' . Html::e(implode(' ', $problems)));
            self::redirect(Routes::path('item'));
        }

        if ($problems !== []) {
            Flash::error(Html::e(implode(' ', $problems)));
        }

        // ShareLandingController fragt als Nächstes ab, zu welchem Inhalt
        // das Bild gehört - siehe dort SESSION_KEY.
        $pending = $_SESSION[ShareLandingController::SESSION_KEY] ?? [];
        $_SESSION[ShareLandingController::SESSION_KEY] = array_merge($stored, $pending);

        self::redirect(Routes::path('share_landing'));
    }

    /**
     * Die empfangenen Dateien, gleich ob der Browser das Feld als "images[]"
     * (mehrere) oder als einzelnes "images" geschickt hat.
     *
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private static function files(): array
    {
        // Psalm kennt $_FILES nur in der Ein-Datei-Form; bei "images[]"
        // liefert PHP für jeden Schlüssel ein eigenes Array
        /** @var mixed $field */
        $field = $_FILES['images'] ?? null;
        if (!is_array($field)) {
            return [];
        }
        /** @var array<string, mixed> $field */

        $keys = ['name', 'type', 'tmp_name', 'error', 'size'];
        $normalized = [];
        foreach ($keys as $key) {
            $value = $field[$key] ?? null;
            $normalized[$key] = is_array($value) ? array_values($value) : [$value];
        }

        $files = [];
        foreach (array_keys($normalized['tmp_name']) as $index) {
            $files[] = [
                'name' => (string)($normalized['name'][$index] ?? ''),
                'type' => (string)($normalized['type'][$index] ?? ''),
                'tmp_name' => (string)($normalized['tmp_name'][$index] ?? ''),
                'error' => (int)($normalized['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int)($normalized['size'][$index] ?? 0),
            ];
        }
        return $files;
    }

    /** Endung passend zum tatsächlichen Bildinhalt, oder null. */
    private static function imageExtension(string $path): ?string
    {
        $info = @getimagesize($path);
        $extension = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            default => null,
        };

        return $extension !== null && in_array($extension, EntityImage::supportedTypes(), true) ? $extension : null;
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'zu groß (höchstens ' . self::megabytes((string)ini_get('upload_max_filesize')) . ').',
            UPLOAD_ERR_PARTIAL => 'nur teilweise übertragen - bitte erneut teilen.',
            UPLOAD_ERR_NO_FILE => 'kam nicht an.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'konnte auf dem Server nicht zwischengespeichert werden.',
            default => 'Übertragungsfehler ' . $code . '.',
        };
    }

    /** Ohne jede Datei war die Anfrage meist größer als post_max_size. */
    private static function nothingReceived(): string
    {
        $limit = self::bytes((string)ini_get('post_max_size'));
        $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

        if ($limit > 0 && $length > $limit) {
            return 'Die Datei ist zu groß (' . round($length / 1048576, 1) . ' MB, höchstens '
                . self::megabytes((string)ini_get('post_max_size')) . ').';
        }
        return 'Es kam keine Datei an.';
    }

    private static function megabytes(string $iniValue): string
    {
        return round(self::bytes($iniValue) / 1048576, 1) . ' MB';
    }

    private static function bytes(string $value): int
    {
        $number = (int)$value;
        return match (strtoupper(substr(trim($value), -1))) {
            'G' => $number * 1073741824,
            'M' => $number * 1048576,
            'K' => $number * 1024,
            default => $number,
        };
    }

    /** @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
    private static function log(array $file, string $reason): void
    {
        error_log(sprintf(
            'PMS Teilen: %s - name="%s" type="%s" size=%d error=%d',
            $reason,
            $file['name'],
            $file['type'],
            $file['size'],
            $file['error']
        ));
    }

    private static function redirect(string $target): never
    {
        header('Location: ' . $target);
        exit;
    }
}
