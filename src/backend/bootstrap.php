<?php
/**
 * Einstieg in den Backend-Code.
 *
 * Der eigentliche Autoloader steht eine Ebene höher, weil Frontend und
 * Backend sich Pms\Support und Pms\Data teilen. Diese Datei sorgt nur
 * dafür, dass der Backend-Code nicht ohne admin.php geladen werden kann -
 * sie liegt wie alles unter src/ im Webroot und wäre sonst direkt
 * aufrufbar.
 */

if (!defined('PMS_ADMIN_ENTRY')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

require_once dirname(__DIR__) . '/bootstrap.php';
