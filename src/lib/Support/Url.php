<?php

namespace Pms\Support;

/**
 * Adressen der Anwendung.
 *
 * Den Basispfad teilen sich Frontend und Backend: Er ergibt sich daraus, wo
 * die Installation liegt. Welche Adresse zu einer Aktion gehört, weiß
 * dagegen nur der jeweilige Bereich - der Einstiegspunkt hinterlegt dafür
 * eine Auflösung, bevor die erste Adresse gebaut wird.
 *
 *     Url::resolveWith(Routes::path(...));
 *     Url::to('cat', ['edit' => 5]);   // /admin/kategorien?edit=5
 */
final class Url
{
    /** @var (\Closure(string): string)|null Aktion => Pfad */
    private static ?\Closure $resolver = null;

    /** Hinterlegt, wie eine Aktion zu ihrem Pfad kommt. */
    public static function resolveWith(\Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Adresse einer Aktion, optional mit Parametern.
     *
     * @param array<string, string|int> $params
     */
    public static function to(string $action, array $params = []): string
    {
        $path = self::$resolver === null ? self::base() . '/' : (self::$resolver)($action);

        return $params === [] ? $path : $path . '?' . http_build_query($params);
    }

    /**
     * Basispfad der Installation.
     *
     * Liegt PMS in einem Unterverzeichnis (z.B. /pms/admin.php), liefert
     * diese Methode "/pms"; im Wurzelverzeichnis eine leere Zeichenkette.
     */
    public static function base(): string
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');

        return $directory === '/' ? '' : $directory;
    }

    /** Wert für <base href>, immer mit abschließendem Schrägstrich. */
    public static function baseHref(): string
    {
        return self::base() . '/';
    }

    /**
     * Adresse einer mitgelieferten Datei (Stylesheet, Bild, Skript).
     * Nötig, weil die Bereiche unter sprechenden Adressen liegen und
     * relative Pfade dort sonst ins Leere zeigen.
     */
    public static function asset(string $path): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    /** Nur für Tests: vergisst die hinterlegte Auflösung. */
    public static function reset(): void
    {
        self::$resolver = null;
    }
}
