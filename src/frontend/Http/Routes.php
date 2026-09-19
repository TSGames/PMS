<?php

namespace Pms\Frontend\Http;

use Pms\Support\Url;

/**
 * Adressen des Frontends.
 *
 * PMS kennt zwei Adressformen. Ist "speciallinks" abgeschaltet, stehen alle
 * Angaben in der Abfragezeichenfolge (index.php?item=5). Ist die Einstellung
 * an, entstehen sprechende Pfade (/content/Satzung-7.html), die .htaccess
 * wieder auf index.php zurueckschreibt.
 *
 * Bisher war das Wissen darueber an zwei Stellen: der Kopf von index.php
 * zerlegte die Pfade, make_link_mark() in functions_ui.php setzte sie
 * zusammen - mit eigener, abweichender Logik. Hier steht beides beieinander.
 */
final class Routes
{
    /**
     * Die Aktionen des Frontends in fester Reihenfolge.
     *
     * Die Reihenfolge ist Teil der Datenhaltung: Der Besucherzaehler legt den
     * Index dieser Liste ab (counter.php, convert_action). Neue Aktionen
     * gehoeren deshalb ans Ende, nicht in die Mitte.
     *
     * @var list<string>
     */
    public const ACTIONS = [
        'download',
        'search',
        'register',
        'password_recover',
        'sitemap',
        'logout',
        'guestbook',
        'user',
        'topuser',
        'user_panel',
        'item',
        'register_finish',
    ];

    /** Kennbuchstabe am Ende eines sprechenden Pfades je Datensatzart. */
    private const SUFFIX = [
        'cat' => 'c',
        'subcat' => 's',
        'user' => 'u',
        'item' => '',
    ];

    public static function has(string $action): bool
    {
        return in_array($action, self::ACTIONS, true);
    }

    /**
     * Adresse einer Aktion.
     *
     * "logout" behaelt bewusst die Abfrageform: Der Altbestand haengt daran
     * weitere Parameter, und ein sprechender Pfad brachte dort nichts.
     *
     * @param array<string, string|int> $params
     */
    public static function action(string $action, array $params = []): string
    {
        if (!self::speakingLinks() || $action === 'logout') {
            return self::entry() . '?' . http_build_query(['action' => $action] + $params);
        }

        $path = self::base() . '/action/' . rawurlencode($action) . '.html';
        return $params === [] ? $path : $path . '?' . http_build_query($params);
    }

    /** Adresse eines Inhalts. */
    public static function item(int $id, string $name = ''): string
    {
        return self::record('item', $id, $name);
    }

    /** Adresse einer Kategorie. */
    public static function cat(int $id, string $name = ''): string
    {
        return self::record('cat', $id, $name);
    }

    /** Adresse einer Unterkategorie. */
    public static function subcat(int $id, string $name = ''): string
    {
        return self::record('subcat', $id, $name);
    }

    /** Adresse eines Benutzerprofils. */
    public static function user(int $id, string $name = ''): string
    {
        if (!self::speakingLinks()) {
            return self::entry() . '?action=user&id=' . $id;
        }
        return self::record('user', $id, $name);
    }

    /** Adresse eines Downloads. */
    public static function download(int $id, string $name = ''): string
    {
        if (!self::speakingLinks()) {
            return self::entry() . '?action=download&id=' . $id;
        }
        return self::base() . '/content/download/' . self::slug($name) . '-' . $id . '.html';
    }

    /**
     * Zerlegt die laufende Anfrage in die Angaben, die das Frontend braucht.
     *
     * @param callable(int): bool $itemExists Pruefung, ob eine Kennung zu
     *        einem Inhalt gehoert. Der sprechende Pfad /content/Name-7.html
     *        laesst sich sonst nicht von einer Suche nach "Name-7"
     *        unterscheiden.
     * @param array<array-key, mixed> $query
     */
    public static function resolve(array $query, callable $itemExists): Target
    {
        // Der Fehlerseiten-Aufruf aus .htaccess geht allen anderen Regeln vor.
        // Welcher Inhalt die Fehlerseite ist, steht in der Datenbank; das
        // loest der Kernel auf.
        if (($query['follow'] ?? '') === '404') {
            return new Target(notFound: true);
        }

        $explicit = new Target(
            action: self::pick($query, 'action'),
            cat: self::number($query, 'cat'),
            subcat: self::number($query, 'subcat'),
            item: self::number($query, 'item'),
            id: self::number($query, 'id'),
            page: max(1, self::number($query, 'page')),
        );

        if (!self::speakingLinks()) {
            return $explicit;
        }

        $decoded = new Target();
        if (($download = self::pick($query, 'download')) !== '') {
            $decoded = $decoded->with(action: 'download', id: self::trailingId($download));
        }
        if (($follow = self::pick($query, 'follow')) !== '') {
            $decoded = self::applyFollow($decoded, $follow, $itemExists);
        }

        // Ausdrueckliche Angaben in der Abfragezeichenfolge gehen dem
        // sprechenden Pfad vor; so haelt es auch der Altbestand.
        return $decoded->overlay($explicit);
    }

    /**
     * Wertet den sprechenden Pfad aus: "Satzung-7" ist ein Inhalt,
     * "Verein-3c" eine Kategorie, "Suchbegriff" eine Suche.
     *
     * @param callable(int): bool $itemExists
     */
    private static function applyFollow(Target $target, string $follow, callable $itemExists): Target
    {
        $parts = explode('-', $follow);

        // Ohne zweiten Teil bleibt nur der Name - das ist eine Suche.
        if (count($parts) < 2) {
            return $parts[0] === '' ? $target : $target->with(action: 'search', search: $parts[0]);
        }

        $tail = $parts[1];
        $suffix = substr($tail, -1);
        $id = (int)substr($tail, 0, -1);

        if ($suffix === 'c' && $id > 0) {
            return $target->with(cat: $id);
        }
        if ($suffix === 's' && $id > 0) {
            return $target->with(subcat: $id);
        }
        if ($suffix === 'u' && $id > 0) {
            return $target->with(action: 'user', id: $id);
        }

        $itemId = (int)$tail;
        if ($itemId > 0 && $itemExists($itemId)) {
            return $target->with(item: $itemId);
        }

        // Kein bekannter Datensatz: Der erste Teil war doch ein Suchbegriff.
        return $parts[0] === '' ? $target : $target->with(action: 'search', search: $parts[0]);
    }

    /** Gemeinsame Form der sprechenden Datensatz-Adressen. */
    private static function record(string $kind, int $id, string $name): string
    {
        if (!self::speakingLinks()) {
            return self::entry() . '?' . $kind . '=' . $id;
        }
        return self::base() . '/content/' . self::slug($name) . '-' . $id . self::SUFFIX[$kind] . '.html';
    }

    /**
     * Der Namensteil eines sprechenden Pfades.
     *
     * Er dient allein der Lesbarkeit; ausgewertet wird nur die Kennung
     * dahinter. Entscheidend ist, dass er keinen Bindestrich enthaelt - sonst
     * findet resolve() die Kennung nicht wieder. link_name() ersetzt dafuer
     * alles ausserhalb von [0-9A-Za-z] durch einen Unterstrich.
     */
    private static function slug(string $name): string
    {
        $slug = function_exists('link_name')
            ? (string)link_name($name)
            : (string)preg_replace('/[^0-9A-Za-z]/', '_', $name);
        return $slug === '' ? 'seite' : $slug;
    }

    /** Die Kennung hinter dem letzten Bindestrich, z.B. aus "Satzung-7". */
    private static function trailingId(string $value): int
    {
        $parts = explode('-', $value);
        return (int)end($parts);
    }

    /** Einstiegsskript fuer die Abfrageform der Adressen. */
    public static function entry(): string
    {
        return self::base() . '/index.php';
    }

    /** Basispfad der Installation; teilen sich Frontend und Backend. */
    public static function base(): string
    {
        return Url::base();
    }

    /** Sind sprechende Adressen eingeschaltet? */
    public static function speakingLinks(): bool
    {
        $config = $GLOBALS['config_values'] ?? null;
        return (bool)($config->speciallinks ?? false);
    }

    /** @param array<array-key, mixed> $query */
    private static function pick(array $query, string $key): string
    {
        $value = $query[$key] ?? null;
        return is_scalar($value) ? trim((string)$value) : '';
    }

    /** @param array<array-key, mixed> $query */
    private static function number(array $query, string $key): int
    {
        $value = self::pick($query, $key);
        return is_numeric($value) ? (int)$value : 0;
    }
}
