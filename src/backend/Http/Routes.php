<?php

namespace Pms\Backend\Http;

use Pms\Support\Url;

/**
 * Adressen des Backends.
 *
 * Jeder Bereich hat einen sprechenden Pfad. Die frühere Form
 * admin.php?action=cat bleibt als Weiterleitung erhalten, damit
 * Lesezeichen und Verweise aus Inhalten gültig bleiben.
 */
final class Routes
{
    /** Aktion => Pfad (ohne Basispfad einer Unterverzeichnis-Installation) */
    private const PATHS = [
        'home' => '/admin',
        'config' => '/admin/einstellungen',
        'menu' => '/admin/menue',
        'user' => '/admin/benutzer',
        'cat' => '/admin/kategorien',
        'subcat' => '/admin/unterkategorien',
        'item' => '/admin/inhalte',
        'item_restore' => '/admin/inhalte/wiederherstellen',
        'item_recover' => '/admin/inhalte/versionen',
        'var' => '/admin/variablen',
        'poll' => '/admin/umfragen',
        'bans' => '/admin/sperrungen',
        'events' => '/admin/ereignisse',
        'backup' => '/admin/sicherungen',
        'activity' => '/admin/status',
        'logout' => '/admin/abmelden',
        'load_last' => '/admin/fortsetzen',
        'update' => '/admin/aktualisierung',
        'xlsx_import_ajax' => '/admin/api/xlsx-import',
        'crop_image_ajax' => '/admin/api/bild-zuschneiden',
        'options_ajax' => '/admin/api/auswahl',
        'image_ajax' => '/admin/api/bilder',
    ];

    /** Pfad des Einstiegsskripts, das die Module anzeigt. */
    public const MODULE_PATH = '/admin/modul/{modul}';

    /** Die frühere Adresse; beantwortet alte Verweise. */
    public const LEGACY_PATH = '/admin.php';

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::PATHS;
    }

    public static function has(string $action): bool
    {
        return isset(self::PATHS[$action]);
    }

    /** Pfad einer Aktion, inklusive Basispfad. */
    public static function path(string $action): string
    {
        return self::basePath() . (self::PATHS[$action] ?? self::PATHS['home']);
    }

    /** Aktion zu einem Pfad, oder eine leere Zeichenkette. */
    public static function actionFor(string $path): string
    {
        $path = '/' . trim(substr($path, strlen(self::basePath())), '/');
        $found = array_search($path, self::PATHS, true);
        return is_string($found) ? $found : '';
    }

    /**
     * Die Aktion der laufenden Anfrage - aus dem Pfad, ersatzweise aus dem
     * Parameter "action" der alten Adressform.
     */
    public static function currentAction(string $default = 'home'): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);

        if (is_string($path)) {
            $action = self::actionFor($path);
            if ($action !== '') {
                return $action;
            }
        }

        $legacy = $_POST['action'] ?? $_GET['action'] ?? '';
        if (is_string($legacy) && self::has($legacy)) {
            return $legacy;
        }

        return $default;
    }

    /** Das über ?modul= oder /admin/modul/… gewählte Modul. */
    public static function currentModule(): string
    {
        $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $prefix = self::basePath() . '/admin/modul/';

        if (str_starts_with($path, $prefix)) {
            return trim(substr($path, strlen($prefix)), '/');
        }

        $legacy = $_GET['modul'] ?? '';
        return is_string($legacy) ? $legacy : '';
    }

    /** Basispfad der Installation; teilen sich Frontend und Backend. */
    public static function basePath(): string
    {
        return Url::base();
    }

    /** Basis für relative Verweise im HTML-Kopf. */
    public static function baseHref(): string
    {
        return Url::baseHref();
    }
}
