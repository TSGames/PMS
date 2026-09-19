<?php

namespace Pms\Backend\Http;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Request;

/**
 * Liefert die Einträge abhängiger Auswahlfelder als JSON.
 *
 * Im Menü- und im Inhalte-Formular hängen Kategorie, Unterkategorie und
 * Inhalt voneinander ab. Bisher musste dafür ein Schalter "Aktualisieren"
 * gedrückt werden, der die ganze Seite neu lud. Jetzt holt das Formular die
 * passenden Einträge nach.
 */
final class OptionsEndpoint
{
    /** Erlaubte Abfragen: Art => [Tabelle, Elternspalte] */
    private const SOURCES = [
        'subcat' => ['subcat', 'cat'],
        'item' => ['item', 'subcat'],
    ];

    /** Beantwortet die Anfrage und beendet das Skript. */
    public static function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::isLoggedIn()) {
            self::send(['error' => 'Authentifizierung erforderlich'], 401);
        }

        $kind = Request::string('typ');
        if (!isset(self::SOURCES[$kind])) {
            self::send(['error' => 'Unbekannte Auswahl'], 400);
        }

        [$table, $parentColumn] = self::SOURCES[$kind];
        $parent = Request::queryInt('parent');

        $options = [];
        if ($parent > 0) {
            $rows = Db::select(
                'SELECT id, name FROM ' . Db::table($table)
                . ' WHERE ' . $parentColumn . ' = :parent ORDER BY sort, name',
                ['parent' => $parent]
            );
            foreach ($rows as $row) {
                $options[] = ['value' => (int)$row->id, 'label' => (string)$row->name];
            }
        }

        self::send(['options' => $options]);
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
