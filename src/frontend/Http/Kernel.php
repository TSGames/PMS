<?php

namespace Pms\Frontend\Http;

use Pms\Data\Db;
use Pms\Support\Request;
use Pms\Support\Url;

/**
 * Richtet eine Frontend-Anfrage ein, bevor sie beantwortet wird.
 *
 * Der Kopf von index.php erledigte das bisher in rund hundert Zeilen: Er
 * zerlegte die Adresse, pruefte die Sperrliste, verwarf unbekannte Aktionen
 * und liess dabei ein Dutzend Variablen zurueck, an denen der Rest der Datei
 * haengt. Hier faellt stattdessen ein Ziel heraus.
 */
final class Kernel
{
    private static ?Ban $ban = null;

    /**
     * Ermittelt, worauf die Anfrage zielt.
     *
     * @param array<array-key, mixed>|null $query Abfragezeichenfolge; ohne
     *        Angabe die der laufenden Anfrage.
     */
    public static function boot(?array $query = null): Target
    {
        self::registerUrls();

        $target = Routes::resolve($query ?? $_GET, self::itemExists(...));

        self::$ban = Ban::check((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        if (self::$ban !== null) {
            // Eine gesperrte Adresse darf nichts ausloesen, auch nichts,
            // was schon im Formular steht
            $_POST = [];
            $target = self::$ban->target();
        }

        $target = self::withErrorPage(self::withKnownAction($target));

        // Der Altbestand liest seine Werte weiterhin aus $_GET; solange das
        // so ist, sieht er dort das aufgeloeste Ziel und nicht die Rohform.
        $_GET = $target->toQuery() + $_GET;

        Request::bind(self::request($target), $target->action);

        return $target;
    }

    /** Der gefundene Sperreintrag der laufenden Anfrage, falls es einen gibt. */
    public static function ban(): ?Ban
    {
        return self::$ban;
    }

    /**
     * Verwirft eine Aktion, die es nicht gibt.
     *
     * Der Besucher bekommt dann die Fehlerseite statt einer halb gefuellten
     * Seite - so hielt es auch der Altbestand.
     */
    private static function withKnownAction(Target $target): Target
    {
        if ($target->action === '' || Routes::has($target->action)) {
            return $target;
        }

        return $target->with(action: '', item: 0, notFound: true);
    }

    /**
     * Setzt die Fehlerseite ein, wenn die Anfrage ins Leere geht.
     *
     * Welcher Inhalt das ist, legt die Konfiguration fest; deshalb steht das
     * hier und nicht in Routes.
     */
    private static function withErrorPage(Target $target): Target
    {
        if (!$target->notFound || $target->item !== 0) {
            return $target;
        }

        return $target->with(item: (int)get_errorpage());
    }

    /** Gehoert diese Kennung zu einem Inhalt? */
    private static function itemExists(int $id): bool
    {
        return Db::value('SELECT id FROM ' . Db::table('item') . ' WHERE id = ?', [$id]) !== null;
    }

    /**
     * Meldet die Adressbildung des Frontends an.
     *
     * Support\Url weiss nicht, ob es im Frontend oder im Backend laeuft;
     * beide melden hier ihre eigene Aufloesung an.
     */
    private static function registerUrls(): void
    {
        Url::resolveWith(static fn(string $action): string => Routes::action($action));
    }

    /** Die laufende Anfrage als PSR-7-Objekt, mit dem aufgeloesten Ziel. */
    private static function request(Target $target): \Psr\Http\Message\ServerRequestInterface
    {
        return (new \Slim\Psr7\Factory\ServerRequestFactory())
            ->createFromGlobals()
            ->withQueryParams($target->toQuery() + $_GET)
            ->withParsedBody($_POST);
    }
}
