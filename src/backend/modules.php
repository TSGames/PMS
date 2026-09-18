<?php
/**
 * Lädt die optionalen Module des Backends.
 *
 * Jedes Modul gibt nur dann etwas aus, wenn es über ?modul=… aufgerufen
 * wurde; andernfalls meldet es lediglich seine Existenz. Der Einbindung
 * muss im globalen Gültigkeitsbereich erfolgen, weil die Module mit den
 * globalen Variablen der Anwendung arbeiten.
 *
 * Ergebnis:
 *   $modul_name    Liste der verfügbaren Module für die Navigation
 *   $modul_content Ausgabe des aufgerufenen Moduls
 */

if (!defined('PMS_ADMIN_ENTRY')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

$modul_name = [];

ob_start();

if (@include 'update.php') {
    $modul_name[] = ['action' => 'update', 'label' => 'Update'];
}
if (@include 'modules/upload.php') {
    $modul_name[] = ['action' => 'picture_upload', 'label' => 'Bilder-Upload'];
}
if (@include 'modules/newsletter.php') {
    $modul_name[] = ['action' => 'newsletter', 'label' => 'Newsletter'];
}

$modul_content = ob_get_clean();
