<?php

namespace Pms\Backend\Http;

use Pms\Support\Auth;
use Pms\Support\Csrf;
use Pms\Support\Request;

/**
 * Schnittstelle für den Import von Tabellen in den Inhaltseditor.
 *
 * Liefert JSON und muss deshalb vor jeder HTML-Ausgabe laufen.
 */
final class XlsxEndpoint
{
    private const TEMP_DIR = 'images/uploads/temp/';

    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        header('Content-Type: application/json');

        if (!Auth::isLoggedIn()) {
            self::fail('Not authenticated');
        }
        if (!Csrf::check()) {
            self::fail('Ungültiges Sicherheitstoken');
        }
        if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
            self::fail('Upload fehlgeschlagen');
        }
        if (!function_exists('parse_xlsx_to_text')) {
            self::fail('XLSX function not available');
        }

        @mkdir(self::TEMP_DIR, 0755, true);
        $target = self::TEMP_DIR . uniqid('xlsx_') . '.xlsx';

        if (!move_uploaded_file($_FILES['xlsx_file']['tmp_name'], $target)) {
            self::fail('Datei konnte nicht gespeichert werden');
        }
        if (!file_exists($target)) {
            self::fail('Temp file not created');
        }

        $result = parse_xlsx_to_text($target);
        @unlink($target);
        cleanup_xlsx_temp_files(1);

        if (is_array($result) && isset($result['error'])) {
            self::fail((string)$result['error']);
        }

        echo json_encode(['content' => (string)$result]);
        exit;
    }

    private static function fail(string $message): never
    {
        echo json_encode(['error' => $message]);
        exit;
    }
}
