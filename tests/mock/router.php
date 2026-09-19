<?php
/**
 * Router für den eingebauten PHP-Server.
 *
 * Bildet die Rewrite-Regel aus src/.htaccess nach: Alle Adressen unter
 * /admin/... beantwortet admin.php, alles andere liefert der Server selbst.
 */

$path = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = (string)$_SERVER['DOCUMENT_ROOT'];

// Der eingebaute Server setzt das Arbeitsverzeichnis auf den Ordner des
// Router-Skripts; die Anwendung erwartet aber den Webroot (Sprachdateien,
// Module, Vorlagen werden relativ geladen).
chdir($root);

if (preg_match('#^/admin(/|$)#', $path)) {
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/admin.php';
    $_SERVER['PHP_SELF'] = '/admin.php';
    require $root . '/admin.php';
    return true;
}

// Vorhandene Dateien und die übrigen Skripte behandelt der Server selbst
return false;
