<?php

namespace Pms\Backend\Support;

/**
 * Schutz vor Cross-Site-Request-Forgery.
 *
 * Jede Sitzung erhält ein Token, das in jedes Formular eingebettet und bei
 * verändernden Anfragen geprüft wird.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';
    public const FIELD = 'pms_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(16));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Verstecktes Formularfeld mit dem Token. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . Html::e(self::token()) . '">';
    }

    /** Token als Parameter für Links, die eine Aktion auslösen. */
    public static function queryParam(): array
    {
        return [self::FIELD => self::token()];
    }

    /** Prüft das mitgesendete Token. */
    public static function check(): bool
    {
        $sent = $_POST[self::FIELD] ?? $_GET[self::FIELD] ?? '';
        if (!is_string($sent) || $sent === '') {
            return false;
        }
        return hash_equals(self::token(), $sent);
    }

    /**
     * Prüft das Token und bricht die Verarbeitung bei Verstoß ab.
     * Der Aufrufer zeigt in diesem Fall eine Fehlermeldung an.
     */
    public static function verify(): bool
    {
        if (self::check()) {
            return true;
        }
        Flash::error('Die Sitzung ist abgelaufen oder die Anfrage stammt nicht von dieser Seite. Bitte erneut versuchen.');
        return false;
    }
}
