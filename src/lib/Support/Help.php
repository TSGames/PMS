<?php

namespace Pms\Support;

/**
 * Die Erklaerungen zu den Feldern des Backends.
 *
 * Die Texte liegen als Dateien unter src/help/, nach Bereich geordnet:
 * src/help/config/visitors_lifetime.md gehoert zur Einstellung
 * "visitors_lifetime" des Konfigurators. Erste Zeile ist die Ueberschrift,
 * danach folgt der Text.
 *
 * Sie stammen aus dem frueheren Hilfe- und Referenzcenter, das seit PHP 7
 * nicht mehr lief. Dort waren sie nach Zahlen benannt (020113.txt) und an
 * eine eigene Anwendung gebunden; hier tragen sie den Namen des Feldes, zu
 * dem sie gehoeren.
 */
final class Help
{
    /** Verzeichnis der Texte. */
    private static ?string $directory = null;

    /** @var array<string, ?array{title: string, body: string}> */
    private static array $cache = [];

    /**
     * Der Text zu einem Feld, oder null.
     *
     * @param string $area  Bereich, etwa "config" oder "menu"
     * @param string $field Feldname, etwa "visitors_lifetime"
     * @return array{title: string, body: string}|null
     */
    public static function for(string $area, string $field): ?array
    {
        return self::load($area . '/' . $field);
    }

    /**
     * Der Text zu einem Verweis der Form "config/visitors_lifetime".
     *
     * @return array{title: string, body: string}|null
     */
    public static function load(string $reference): ?array
    {
        if (array_key_exists($reference, self::$cache)) {
            return self::$cache[$reference];
        }

        return self::$cache[$reference] = self::read($reference);
    }

    /** Gibt es einen Text zu diesem Verweis? */
    public static function has(string $reference): bool
    {
        return self::load($reference) !== null;
    }

    /**
     * Alle vorhandenen Verweise, alphabetisch.
     *
     * Der Test benutzt das, um jeden Verweis aus den Controllern gegen den
     * Bestand zu halten - sonst faellt ein Tippfehler erst im Browser auf.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $found = [];
        foreach (glob(self::directory() . '/*/*.md') ?: [] as $file) {
            $found[] = basename(dirname($file)) . '/' . basename($file, '.md');
        }
        sort($found);
        return $found;
    }

    /** Legt das Verzeichnis fest; nur fuer Tests. */
    public static function useDirectory(?string $directory): void
    {
        self::$directory = $directory;
        self::$cache = [];
    }

    /** @return array{title: string, body: string}|null */
    private static function read(string $reference): ?array
    {
        // Ein Verweis nennt Bereich und Feld, sonst nichts. Alles andere
        // koennte aus dem Verzeichnis herausfuehren.
        if (!preg_match('/^[a-z0-9_]+\/[a-z0-9_]+$/', $reference)) {
            return null;
        }

        $file = self::directory() . '/' . $reference . '.md';
        if (!is_file($file)) {
            return null;
        }

        $content = (string)file_get_contents($file);
        $lines = preg_split('/\R/', trim($content)) ?: [];
        $title = trim((string)array_shift($lines));

        return ['title' => $title, 'body' => trim(implode("\n", $lines))];
    }

    private static function directory(): string
    {
        return self::$directory ?? dirname(__DIR__, 2) . '/help';
    }
}
