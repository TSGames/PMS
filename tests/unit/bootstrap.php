<?php
/**
 * Startpunkt der Unit-Tests.
 *
 * Die Tests prüfen die Bausteine unter src/backend/ ohne Webserver und ohne
 * Browser. Sie laufen deshalb nicht über admin.php, sondern registrieren den
 * Autoloader selbst und stellen die wenigen globalen Dinge bereit, die der
 * Altbestand voraussetzt.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

const PMS_BACKEND_DIR = __DIR__ . '/../../src/backend';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Pms\\Backend\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = PMS_BACKEND_DIR . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/**
 * Datenbankzugriff für die Tests.
 *
 * Data\Db erwartet unter $GLOBALS['pms_db_connection'] ein pms_db_class und
 * benutzt davon prepare() und error(). Die Fassung aus functions_global.php
 * verbindet sich fest mit /var/db/…; hier steht stattdessen eine Datenbank
 * im Arbeitsspeicher, die jeder Testfall frisch aufbaut.
 */
class pms_db_class
{
    public function __construct(public readonly SQLite3 $sqlite = new SQLite3(':memory:'))
    {
    }

    public function prepare(string $sql): SQLite3Stmt|false
    {
        // SQLite3 meldet eine fehlerhafte Anweisung zusätzlich als Warnung;
        // Data\Db wertet den Rückgabewert aus und schreibt selbst ins Log
        return @$this->sqlite->prepare($sql);
    }

    public function error(): string
    {
        return $this->sqlite->lastErrorMsg();
    }

    public function query(string $sql): SQLite3Result|bool
    {
        return $this->sqlite->query($sql);
    }

    public function exec(string $sql): bool
    {
        return $this->sqlite->exec($sql);
    }
}

// Die Adressen leiten sich aus SCRIPT_NAME ab
$_SERVER['SCRIPT_NAME'] = '/admin.php';
$_SERVER['REQUEST_URI'] = '/admin';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Der Altbestand arbeitet mit einer Sitzung; im Test genügt ein Feld
if (!isset($_SESSION)) {
    $_SESSION = [];
}
