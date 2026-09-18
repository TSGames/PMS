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
