<?php
/**
 * Startpunkt des neuen Codes, gemeinsam für Frontend und Backend.
 *
 * Registriert den Autoloader für den Namensraum Pms\ und lädt die
 * Composer-Abhängigkeiten. Nur index.php und admin.php dürfen diese Datei
 * einbinden; beide setzen vorher PMS_FRONTEND oder PMS_BACKEND.
 *
 * Verzeichnisse:
 *   src/lib/       Pms\Support, Pms\Data - von beiden benutzt
 *   src/backend/   Pms\Backend\*
 */

if (!defined('PMS_FRONTEND') && !defined('PMS_BACKEND')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

// Composer-Abhängigkeiten (Slim). Im Image liegt vendor/ außerhalb des
// Webroots, weil dieser bei der Entwicklung überlagert wird.
$pms_autoload_paths = [
    dirname(__DIR__) . '/vendor/autoload.php',
    '/var/composer/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];

$pms_autoload_found = false;
foreach ($pms_autoload_paths as $pms_autoload) {
    if (is_file($pms_autoload)) {
        require_once $pms_autoload;
        $pms_autoload_found = true;
        break;
    }
}

// Ohne Autoloader lief das Skript bisher stillschweigend weiter und starb
// erst viel spaeter an einer fehlenden Slim-Klasse - bei abgeschaltetem
// log_errors ohne jede Spur, nur mit einer leeren Seite. Hier zu enden ist
// unangenehm, aber es sagt wenigstens, was fehlt.
if (!$pms_autoload_found) {
    $pms_message = 'Die Composer-Abhaengigkeiten fehlen. Gesucht wurde in:' . "\n  "
        . implode("\n  ", $pms_autoload_paths) . "\n\n"
        . 'Abhilfe: "composer install" im Projektverzeichnis ausfuehren, oder das'
        . ' Docker-Image neu bauen - dort liegt vendor/ unter /var/composer.';

    error_log('PMS: ' . str_replace("\n", ' ', $pms_message));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $pms_message . "\n");
    } else {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $pms_message, "\n";
    }
    exit(1);
}

/**
 * Namensraum-Präfix => Verzeichnis. Der längste passende Präfix gewinnt,
 * deshalb steht Pms\Backend\ vor Pms\.
 *
 * @var array<string, string>
 */
const PMS_NAMESPACES = [
    'Pms\\Backend\\' => __DIR__ . '/backend/',
    'Pms\\Frontend\\' => __DIR__ . '/frontend/',
    'Pms\\' => __DIR__ . '/lib/',
];

spl_autoload_register(static function (string $class): void {
    foreach (PMS_NAMESPACES as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
});
