<?php

namespace Pms\Backend\Http;

use Pms\Support\Auth;
use Pms\Support\Csrf;
use Pms\Support\EntityImage;
use Pms\Support\Request;

/**
 * Schnittstelle des Bild-Dialogs im Inhaltseditor.
 *
 * Bisher war die Bildauswahl eine Folge eigener Seiten: Editor verlassen,
 * Bild hochladen, Größe festlegen, zurück in den Editor. Dabei ging jede
 * ungespeicherte Änderung am Text verloren. Der Dialog erledigt das jetzt
 * ohne Seitenwechsel und benutzt dafür diese Schnittstelle.
 *
 * Liefert JSON und läuft deshalb vor jeder HTML-Ausgabe.
 */
final class ImageEndpoint
{
    private const UPLOAD_DIR = 'images/uploads/';

    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::isLoggedIn()) {
            self::send(['error' => 'Authentifizierung erforderlich'], 401);
        }

        $task = Request::string('do', 'list');

        // Nur das Lesen kommt ohne Token aus
        if ($task !== 'list' && !Csrf::check()) {
            self::send(['error' => 'Ungültiges Sicherheitstoken'], 403);
        }

        match ($task) {
            'upload' => self::upload(),
            'delete' => self::delete(),
            'scale' => self::scale(),
            default => self::send(['images' => self::images()]),
        };
    }

    /** Nimmt eine hochgeladene Datei entgegen. */
    private static function upload(): never
    {
        $file = $_FILES['image'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            self::send(['error' => 'Es wurde keine Datei übertragen.'], 400);
        }

        $name = link_name((string)$file['name'], '.');
        $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, EntityImage::supportedTypes(), true)) {
            self::send(['error' => 'Dieses Dateiformat wird nicht unterstützt.'], 400);
        }

        @mkdir(self::UPLOAD_DIR, 0755, true);
        $target = self::uniqueName($name, $extension);

        if (!@move_uploaded_file((string)$file['tmp_name'], self::UPLOAD_DIR . $target)) {
            self::send(['error' => 'Die Datei konnte nicht gespeichert werden.'], 500);
        }

        self::send(['image' => self::describe($target)]);
    }

    /** Entfernt ein hochgeladenes Bild. */
    private static function delete(): never
    {
        $name = basename(Request::string('name'));
        $path = self::UPLOAD_DIR . $name;

        if ($name === '' || !is_file($path)) {
            self::send(['error' => 'Das Bild wurde nicht gefunden.'], 404);
        }
        if (!@unlink($path)) {
            self::send(['error' => 'Das Bild konnte nicht entfernt werden.'], 500);
        }

        self::send(['ok' => true]);
    }

    /** Skaliert ein Bild auf die gewünschte Größe. */
    private static function scale(): never
    {
        $name = basename(Request::string('name'));
        $path = self::UPLOAD_DIR . $name;
        $width = Request::int('width');
        $height = Request::int('height');

        if ($name === '' || !is_file($path)) {
            self::send(['error' => 'Das Bild wurde nicht gefunden.'], 404);
        }
        if ($width < 1 || $height < 1) {
            self::send(['error' => 'Bitte geben Sie eine gültige Größe an.'], 400);
        }

        create_img($path, $width, $height, 0);

        self::send(['image' => self::describe($name)]);
    }

    /**
     * Alle hochgeladenen Bilder, das jüngste zuerst.
     *
     * @return list<array{name: string, url: string, width: int, height: int, size: int}>
     */
    private static function images(): array
    {
        $files = [];
        foreach (glob(self::UPLOAD_DIR . '*') ?: [] as $path) {
            if (is_file($path)) {
                $files[basename($path)] = (int)@filemtime($path);
            }
        }
        arsort($files);

        $images = [];
        foreach (array_keys($files) as $name) {
            $images[] = self::describe($name);
        }
        return $images;
    }

    /** @return array{name: string, url: string, width: int, height: int, size: int} */
    private static function describe(string $name): array
    {
        $path = self::UPLOAD_DIR . $name;
        $size = @getimagesize($path);

        return [
            'name' => $name,
            // Cache-Schlüssel, damit ein skaliertes Bild sofort neu geladen wird
            'url' => $path . '?v=' . (int)@filemtime($path),
            'width' => (int)($size[0] ?? 0),
            'height' => (int)($size[1] ?? 0),
            'size' => (int)@filesize($path),
        ];
    }

    /** Ein freier Dateiname, damit nichts überschrieben wird. */
    private static function uniqueName(string $name, string $extension): string
    {
        $base = (string)pathinfo($name, PATHINFO_FILENAME);
        $candidate = $base . '.' . $extension;

        $counter = 1;
        while (file_exists(self::UPLOAD_DIR . $candidate)) {
            $candidate = $base . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $candidate;
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
