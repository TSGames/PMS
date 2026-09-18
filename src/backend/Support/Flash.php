<?php

namespace Pms\Backend\Support;

/**
 * Rückmeldungen an den Benutzer.
 *
 * Meldungen überdauern eine Weiterleitung (Post/Redirect/Get) und werden
 * beim Rendern einmalig ausgegeben. Der Altbestand benutzt weiterhin die
 * globalen Variablen $ok/$error; render() übernimmt beide Quellen.
 */
final class Flash
{
    private const SESSION_KEY = 'pms_flash';

    public static function success(string $message): void
    {
        self::add('ok', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    private static function add(string $type, string $message): void
    {
        $_SESSION[self::SESSION_KEY][] = ['type' => $type, 'message' => $message];
    }

    /** Gibt alle anstehenden Meldungen aus und leert den Speicher. */
    public static function render(): string
    {
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        // Meldungen des Altbestands übernehmen
        foreach (['ok' => 'ok', 'error' => 'error'] as $global => $type) {
            if (!empty($GLOBALS[$global]) && is_string($GLOBALS[$global])) {
                $messages[] = ['type' => $type, 'message' => $GLOBALS[$global]];
                $GLOBALS[$global] = '';
            }
        }

        $html = '';
        foreach ($messages as $message) {
            $class = $message['type'] === 'error' ? 'info_error' : 'info_ok';
            // Die Meldungen stammen aus dem Programm und dürfen Markup enthalten
            $html .= '<table class="' . $class . '"><tr><td>' . $message['message'] . '</td></tr></table><br>';
        }
        return $html;
    }

    public static function hasMessages(): bool
    {
        return !empty($_SESSION[self::SESSION_KEY]);
    }
}
