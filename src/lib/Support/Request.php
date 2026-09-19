<?php

namespace Pms\Support;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Typisierter Zugriff auf die Eingabedaten einer Anfrage.
 *
 * Die Klasse ist eine Fassade über die PSR-7-Anfrage, die Slim übergibt.
 * Ohne gebundene Anfrage (Kommandozeile, Tests) greift sie auf $_POST und
 * $_GET zurück.
 *
 * Der Altbestand rechnete mit Rohwerten; unter PHP 8 führt das bei leeren
 * Feldern zu Abbrüchen ("" * 1). Hier kommt immer der erwartete Typ heraus.
 */
final class Request
{
    private static ?ServerRequestInterface $current = null;
    private static string $action = '';

    /** Bindet die laufende Anfrage und die aufgelöste Aktion. */
    public static function bind(ServerRequestInterface $request, string $action = ''): void
    {
        self::$current = $request;
        self::$action = $action;
    }

    public static function current(): ?ServerRequestInterface
    {
        return self::$current;
    }

    /** Ganzzahl aus den Formulardaten, sonst aus der Abfragezeichenfolge. */
    public static function int(string $key, int $default = 0): int
    {
        return self::toInt(self::raw($key), $default);
    }

    /** Ganzzahl ausschließlich aus der Abfragezeichenfolge. */
    public static function queryInt(string $key, int $default = 0): int
    {
        return self::toInt(self::query()[$key] ?? null, $default);
    }

    /** Kommazahl, akzeptiert auch das deutsche Dezimalkomma. */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::raw($key);
        if (!is_scalar($value)) {
            return $default;
        }
        $value = str_replace(',', '.', trim((string)$value));
        return $value === '' || !is_numeric($value) ? $default : (float)$value;
    }

    /** Zeichenkette ohne umschließende Leerzeichen. */
    public static function string(string $key, string $default = ''): string
    {
        $value = self::raw($key);
        return is_scalar($value) ? trim((string)$value) : $default;
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
        return ($value === null || $value === '' || $value === '0') ? 0 : 1;
    }

    /** Liste von Ganzzahlen (z.B. Checkbox-Gruppen name="feld[]"). */
    public static function intList(string $key): array
    {
        $value = self::body()[$key] ?? self::query()[$key] ?? null;
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
        return array_key_exists($key, self::body());
    }

    public static function isPost(): bool
    {
        if (self::$current !== null) {
            return self::$current->getMethod() === 'POST';
        }
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Die Aktion der laufenden Anfrage. */
    public static function action(string $default = ''): string
    {
        if (self::$action !== '') {
            return self::$action;
        }
        $action = self::string('action');
        return $action !== '' ? $action : $default;
    }

    /** @return array<string, mixed> */
    private static function body(): array
    {
        if (self::$current !== null) {
            $body = self::$current->getParsedBody();
            return is_array($body) ? $body : [];
        }
        return $_POST;
    }

    /** @return array<string, mixed> */
    private static function query(): array
    {
        return self::$current !== null ? self::$current->getQueryParams() : $_GET;
    }

    private static function raw(string $key): mixed
    {
        $body = self::body();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }
        return self::query()[$key] ?? null;
    }

    private static function toInt(mixed $value, int $default): int
    {
        if (!is_scalar($value)) {
            return $default;
        }
        $value = trim((string)$value);
        return ($value === '' || !is_numeric($value)) ? $default : (int)$value;
    }
}
