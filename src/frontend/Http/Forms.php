<?php

namespace Pms\Frontend\Http;

use Pms\Support\Csrf;

/**
 * Erkennt abgeschickte Formulare - und prueft dabei das Token.
 *
 * Der Altbestand erkannte ein Formular an der Beschriftung seines Schalters
 * ($_POST['poll'] == language("POLL_VOTE")). Das hatte zwei Folgen: Wer eine
 * Beschriftung in der Sprachdatei aenderte, legte die Funktion still lahm,
 * und ein Token liess sich nirgends unterbringen.
 *
 * Hier zaehlt der Feldname, und die Pruefung des Tokens haengt daran fest:
 * Wer submitted() benutzt, kann sie nicht vergessen.
 */
final class Forms
{
    /** Formulare, deren Token nicht passte - fuer die Meldung an den Besucher. */
    private static bool $rejected = false;

    /**
     * Wurde dieses Formular abgeschickt und stammt es von dieser Seite?
     *
     * Ein fehlendes Feld ist der Normalfall und keine Meldung wert. Ein
     * vorhandenes Feld ohne gueltiges Token ist dagegen ein Versuch, die
     * Verarbeitung von aussen auszuloesen.
     */
    public static function submitted(string $field): bool
    {
        if (!array_key_exists($field, $_POST)) {
            return false;
        }

        if (!Csrf::check()) {
            self::$rejected = true;
            return false;
        }

        return true;
    }

    /**
     * Darf diese veraendernde Anfrage ausgefuehrt werden?
     *
     * Fuer die wenigen Stellen, die ueber einen Verweis statt ueber ein
     * Formular laufen - etwa das Loeschen eines Kommentars.
     */
    public static function allowed(): bool
    {
        if (Csrf::check()) {
            return true;
        }

        self::$rejected = true;
        return false;
    }

    /** Wurde in dieser Anfrage etwas wegen eines fehlenden Tokens abgelehnt? */
    public static function wasRejected(): bool
    {
        return self::$rejected;
    }

    /** Hinweis fuer den Besucher, oder eine leere Zeichenkette. */
    public static function notice(): string
    {
        if (!self::$rejected) {
            return '';
        }

        return '<div class="info_error">' . language('FORM_REJECTED') . '</div>';
    }

    /** Nur fuer Tests. */
    public static function reset(): void
    {
        self::$rejected = false;
    }
}
