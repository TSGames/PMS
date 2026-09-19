<?php

namespace Pms\Backend\Http;

use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Csrf;
use Pms\Backend\Support\Request;

/**
 * Schnittstelle des Zuschneide-Dialogs.
 *
 * Nimmt ein Bild entgegen, schneidet es zu, skaliert es und meldet den
 * Dateinamen zurück. Liefert JSON und läuft deshalb vor jeder HTML-Ausgabe.
 */
final class CropEndpoint
{
    private const UPLOAD_DIR = 'images/uploads/';

    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::isLoggedIn()) {
            self::fail('Authentifizierung erforderlich');
        }
        if (!Csrf::check()) {
            self::fail('Ungültiges Sicherheitstoken');
        }

        $file = basename(Request::string('image_file'));
        $width = Request::int('crop_w');
        $height = Request::int('crop_h');

        if ($file === '' || $width < 10 || $height < 10) {
            self::fail('Ungültige Eingabeparameter');
        }

        $path = self::storeUpload($file);

        // Nur Dateien innerhalb des Upload-Verzeichnisses bearbeiten
        $real = realpath($path);
        $uploads = realpath(self::UPLOAD_DIR);
        if ($real === false || $uploads === false || !str_starts_with($real, $uploads)) {
            self::fail('Ungültiger Dateipfad');
        }

        $result = crop_image($path, Request::int('crop_x'), Request::int('crop_y'), $width, $height);
        if (!$result['success']) {
            self::fail((string)$result['error']);
        }

        $targetWidth = Request::int('resize_width');
        $targetHeight = Request::int('resize_height');
        if ($targetWidth > 0 && $targetHeight > 0) {
            create_img($path, $targetWidth, $targetHeight, 0);
            $size = @getimagesize($path);
            $result['width'] = $size ? $size[0] : $targetWidth;
            $result['height'] = $size ? $size[1] : $targetHeight;
        }

        echo json_encode([
            'success' => true,
            'width' => $result['width'],
            'height' => $result['height'],
            'filename' => basename($path),
            'message' => 'Bild erfolgreich zugeschnitten',
        ]);
        exit;
    }

    /**
     * Speichert die mitgesendete Bilddatei unter einem freien Namen und
     * liefert ihren Pfad. Ohne Upload wird die vorhandene Datei benutzt.
     */
    private static function storeUpload(string $fallbackName): string
    {
        $upload = $_FILES['image_data'] ?? null;
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
            $path = self::UPLOAD_DIR . $fallbackName;
            if (!file_exists($path)) {
                self::fail('Bilddatei nicht gefunden');
            }
            return $path;
        }

        @mkdir(self::UPLOAD_DIR, 0755, true);

        $name = link_name((string)$upload['name'], '.');
        $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        $base = substr($name, 0, max(0, strlen($name) - strlen($extension) - 1));

        $candidate = $name;
        for ($i = 1; file_exists(self::UPLOAD_DIR . $candidate); $i++) {
            $candidate = $base . $i . '.' . $extension;
        }

        if (!@copy($upload['tmp_name'], self::UPLOAD_DIR . $candidate)) {
            self::fail('Fehler beim Hochladen der Datei');
        }
        return self::UPLOAD_DIR . $candidate;
    }

    private static function fail(string $message): never
    {
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
