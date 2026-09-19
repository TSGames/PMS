<?php

namespace Pms\Backend\Support;

/**
 * Anmeldung und Rechte im Backend.
 *
 * Kapselt die Sitzungslogik, die bisher offen in admin.php stand:
 * Anmeldung per Formular oder Cookie, Abmeldung, Wiederaufnahme
 * abgebrochener Vorgänge und die Prüfung der Berechtigungsstufe.
 */
final class Auth
{
    /** Benutzertypen */
    public const TYPE_USER = 0;
    public const TYPE_MODERATOR = 1;
    public const TYPE_ADMIN = 2;
    public const TYPE_SUPERADMIN = 3;

    /** Ab dieser Stufe ist das Backend zugänglich. */
    public const BACKEND_MINIMUM = self::TYPE_ADMIN;

    private static ?object $user = null;

    /**
     * Verarbeitet Abmeldung, Formular- und Cookie-Anmeldung.
     * Muss vor jeder Ausgabe laufen, weil Cookies gesetzt werden.
     */
    public static function handleRequest(): void
    {
        $cookieDomain = $GLOBALS['cookie_domain'] ?? '';

        if (\Pms\Backend\Http\Routes::currentAction('') === 'logout') {
            delete_sessions();
            Flash::success('Logout erfolgreich!');
            return;
        }

        $viaForm = Request::submitted('login');
        $viaCookie = !empty($_COOKIE['login_id']) && !empty($_COOKIE['login_pw']);

        if (!$viaForm && !$viaCookie) {
            return;
        }

        if ($viaForm) {
            $result = do_login(Request::string('login_name'), Request::text('login_password'), self::BACKEND_MINIMUM);
        } else {
            $name = from_db('user', (int)$_COOKIE['login_id'], 'name');
            $result = do_login($name, $_COOKIE['login_pw'], self::BACKEND_MINIMUM, 0);
        }

        if (!is_array($result)) {
            setcookie('login_id', '', time() - 3600, '/', $cookieDomain);
            setcookie('login_pw', '', time() - 3600, '/', $cookieDomain);
            if ($viaForm) {
                Flash::error(self::loginErrorMessage((int)$result));
            }
            return;
        }

        setcookie('login_id', (string)$result[3], time() + 60 * 60 * 24 * 1000, '/', $cookieDomain);
        if (Request::checkbox('save_login')) {
            setcookie('login_pw', md5(Request::text('login_password')), time() + 60 * 60 * 24 * 1000, '/', $cookieDomain);
        }

        // Vorgang, der bei der letzten Abmeldung offen blieb, kann fortgesetzt werden
        $GLOBALS['set_reloadable'] = reload_all(0);
        $_SESSION['reload_check'] = 1;
    }

    /** Nimmt einen bei der letzten Abmeldung gespeicherten Vorgang wieder auf. */
    public static function resumePendingAction(): void
    {
        if (\Pms\Backend\Http\Routes::currentAction('') === 'load_last') {
            unset($_SESSION['reload_check']);
            reload_all(1);
            return;
        }
        if ((int)($_SESSION['reload_check'] ?? 0) >= 2) {
            unset($_SESSION['reload_check']);
            reload_all(-1);
            return;
        }
        if (!empty($_SESSION['reload_check'])) {
            $_SESSION['reload_check']++;
        }
    }

    public static function isLoggedIn(): bool
    {
        return (int)($_SESSION['pmsglobal'] ?? 0) === 1 && self::userType() >= self::BACKEND_MINIMUM;
    }

    /** Beendet die Sitzung, wenn die Rechte nicht (mehr) ausreichen. */
    public static function enforceBackendAccess(): void
    {
        if (self::userId() > 0 && self::userType() < self::BACKEND_MINIMUM) {
            delete_sessions();
            self::$user = null;
        }
    }

    public static function userId(): int
    {
        return (int)($_SESSION['userid'] ?? 0);
    }

    public static function userType(): int
    {
        $user = self::user();
        return $user === null ? -1 : (int)$user->typ;
    }

    public static function userName(): string
    {
        $user = self::user();
        return $user === null ? '' : (string)$user->name;
    }

    /** Hat der angemeldete Benutzer mindestens diese Stufe? */
    public static function atLeast(int $level): bool
    {
        return self::userType() >= $level;
    }

    public static function isSuperAdmin(): bool
    {
        return self::atLeast(self::TYPE_SUPERADMIN);
    }

    /** Der angemeldete Benutzer, frisch aus der Datenbank. */
    public static function user(): ?object
    {
        $id = self::userId();
        if ($id === 0) {
            return null;
        }
        if (self::$user === null || (int)self::$user->id !== $id) {
            self::$user = \Pms\Backend\Data\Db::first(
                'SELECT * FROM ' . \Pms\Backend\Data\Db::table('user') . ' WHERE id = :id',
                ['id' => $id]
            );
        }
        return self::$user;
    }

    /** Vergisst den zwischengespeicherten Benutzer (nach Änderungen). */
    public static function forget(): void
    {
        self::$user = null;
    }

    private static function loginErrorMessage(int $code): string
    {
        return match ($code) {
            1 => 'Benutzer existiert nicht!',
            2 => 'Passwort ist ungültig!',
            3 => 'Der Benutzer ist gesperrt!',
            4 => 'Ihre Berechtigungen sind zu niedrig!',
            default => 'Anmeldung fehlgeschlagen!',
        };
    }
}
