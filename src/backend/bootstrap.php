<?php
/**
 * Startpunkt des neuen Backend-Codes.
 *
 * Registriert den Autoloader für den Namensraum Pms\Backend und stellt
 * sicher, dass nur admin.php diesen Code laden kann.
 */

if (!defined('PMS_ADMIN_ENTRY')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

define('PMS_BACKEND_DIR', __DIR__);

// Composer-Abhängigkeiten (Slim). Im Image liegt vendor/ außerhalb des
// Webroots, weil dieser bei der Entwicklung überlagert wird.
foreach ([
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    '/var/composer/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Pms\\Backend\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = PMS_BACKEND_DIR . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
