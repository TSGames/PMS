<?php
/**
 * Zeitgesteuerte Aufgaben - derzeit der Wochenbericht.
 *
 * entrypoint.sh ruft das Skript im Container alle 15 Minuten auf. Es tut
 * nur etwas, wenn eine Aufgabe fällig ist, und darf deshalb beliebig oft
 * laufen. Zum Ausprobieren:
 *
 *   php cron.php           # verschickt nur, wenn fällig
 *   php cron.php --force   # erstellt und verschickt den Bericht der Vorwoche sofort
 *
 * Liegt wie init.php im Webroot und ist deshalb nur von der Kommandozeile
 * aus aufrufbar.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('PMS_FRONTEND', 0);
define('PMS_BACKEND', 1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/config.php';
require __DIR__ . '/functions_global.php';

use Pms\Backend\Http\Routes;
use Pms\Backend\Push\PushService;
use Pms\Backend\Report\WeeklyReport;

$force = in_array('--force', $argv, true);
$now = time();

if (!$force && !WeeklyReport::isDue($now)) {
    exit(0);
}

$report = WeeklyReport::create($now);
$summary = WeeklyReport::summary($report);
// Relativ statt Routes::path(): SCRIPT_NAME ist auf der Kommandozeile ein
// Dateipfad. Der Service Worker löst die Adresse gegen seinen eigenen Ort auf.
$result = PushService::sendToAll($summary + ['url' => ltrim(Routes::all()['weekly_report'], '/')]);

fwrite(STDOUT, sprintf(
    "[cron] %s: %s - an %d Gerät(e) verschickt, %d fehlgeschlagen, %d abgelaufene Abos entfernt\n",
    $summary['title'],
    $summary['body'],
    $result['sent'],
    $result['failed'],
    $result['removed']
));
