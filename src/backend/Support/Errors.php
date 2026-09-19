<?php

namespace Pms\Backend\Support;

/**
 * Feldbezogene Fehlermeldungen eines Formulars.
 *
 * Bisher landete jede Prüfung in einer Meldung am Seitenkopf ("Bitte geben
 * Sie einen Kategorienamen an") und das Formular war danach leer. Die
 * Meldungen stehen jetzt am betroffenen Feld, und der Controller zeigt das
 * Formular mit den eingegebenen Werten erneut an.
 *
 * Der Bestand gilt für die laufende Anfrage; über eine Weiterleitung hinweg
 * ist er nicht gedacht - nach einer erfolgreichen Verarbeitung gibt es keine
 * Fehler mehr.
 */
final class Errors
{
    /** @var array<string, string> Feldname => Meldung */
    private static array $messages = [];

    /** Hält eine Meldung zu einem Feld fest. Die erste je Feld gewinnt. */
    public static function add(string $field, string $message): void
    {
        if (!isset(self::$messages[$field])) {
            self::$messages[$field] = $message;
        }
    }

    public static function get(string $field): string
    {
        return self::$messages[$field] ?? '';
    }

    public static function has(string $field = ''): bool
    {
        return $field === '' ? self::$messages !== [] : isset(self::$messages[$field]);
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::$messages;
    }

    /** Nur für Tests und für den Beginn einer neuen Anfrage. */
    public static function reset(): void
    {
        self::$messages = [];
    }
}
