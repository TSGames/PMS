<?php

namespace Pms\Backend\Support;

/**
 * Typisierter Zugriff auf die Eingabedaten einer Anfrage.
 *
 * Der Altbestand liest $_GET/$_POST direkt aus und rechnet anschließend mit
 * den Rohwerten. Unter PHP 8 führt das bei leeren Feldern zu Abbrüchen
 * (z.B. "" * 1). Diese Klasse liefert immer den erwarteten Typ.
 */
final class Request
{
    /** Ganzzahl aus POST, sonst GET. Leere oder ungültige Werte ergeben den Standardwert. */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::raw($key);
        if ($value === null || !is_scalar($value)) {
            return $default;
        }
        $value = trim((string)$value);
        if ($value === '' || !is_numeric($value)) {
            return $default;
        }
        return (int)$value;
    }

    /** Ganzzahl ausschließlich aus der Abfragezeichenfolge. */
    public static function queryInt(string $key, int $default = 0): int
    {
        $value = $_GET[$key] ?? null;
        if ($value === null || !is_scalar($value)) {
            return $default;
        }
        $value = trim((string)$value);
        return $value === '' || !is_numeric($value) ? $default : (int)$value;
    }

    /** Kommazahl, akzeptiert auch das deutsche Dezimalkomma. */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::raw($key);
        if ($value === null || !is_scalar($value)) {
            return $default;
        }
        $value = str_replace(',', '.', trim((string)$value));
        return $value === '' || !is_numeric($value) ? $default : (float)$value;
    }

    /** Zeichenkette ohne umschließende Leerzeichen. */
    public static function string(string $key, string $default = ''): string
    {
        $value = self::raw($key);
        if ($value === null || !is_scalar($value)) {
            return $default;
        }
        return trim((string)$value);
    }

    /** Rohe Zeichenkette (z.B. Inhalte mit bedeutsamen Leerzeilen). */
    public static function text(string $key, string $default = ''): string
    {
        $value = self::raw($key);
        return is_scalar($value) ? (string)$value : $default;
    }

    /** Checkbox-Wert: 1, wenn das Feld gesendet wurde und nicht leer ist. */
    public static function checkbox(string $key): int
    {
        $value = self::raw($key);
        if ($value === null || $value === '' || $value === '0') {
            return 0;
        }
        return 1;
    }

    /** Liste von Ganzzahlen (z.B. Checkbox-Gruppen name="feld[]"). */
    public static function intList(string $key): array
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        if (!is_array($value)) {
            return [];
        }
        $result = [];
        foreach ($value as $entry) {
            if (is_scalar($entry) && is_numeric(trim((string)$entry))) {
                $result[] = (int)trim((string)$entry);
            }
        }
        return $result;
    }

    /** Wurde das Formular mit diesem Schalter abgeschickt? */
    public static function submitted(string $key): bool
    {
        return array_key_exists($key, $_POST);
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Die Aktion der aktuellen Anfrage (POST hat Vorrang, wie bisher). */
    public static function action(string $default = ''): string
    {
        $action = self::string('action');
        return $action !== '' ? $action : $default;
    }

    private static function raw(string $key): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? null;
    }
}
