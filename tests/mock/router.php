<?php
/**
 * Router für den eingebauten PHP-Server.
 *
 * Bildet die Rewrite-Regeln aus src/.htaccess nach, damit das Mock-System
 * sich verhält wie eine Installation unter Apache: Der Adminbereich liegt
 * unter /admin/..., das Frontend benutzt sprechende Adressen
 * (/content/..., /action/..., /rss/...).
 *
 * Wichtig: Die Skripte werden im Dateigültigkeitsbereich eingebunden, nicht
 * aus einer Funktion heraus. Der Altbestand legt seine Variablen
 * ($pms_db_connection, $config_values, …) im globalen Bereich ab und holt
 * sie später mit "global" wieder; aus einer Funktion heraus eingebunden
 * wären sie lokal und die Anwendung liefe ins Leere.
 */

$pms_path = (string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pms_root = (string)$_SERVER['DOCUMENT_ROOT'];

// Der eingebaute Server setzt das Arbeitsverzeichnis auf den Ordner des
// Router-Skripts; die Anwendung erwartet aber den Webroot (Sprachdateien,
// Module, Vorlagen werden relativ geladen).
chdir($pms_root);

/** @var array<string, array{0: string, 1: string}> Muster => [Skript, Parameter] */
$pms_rules = [
    '#^/admin(/|$)#' => ['admin.php', ''],
    '#^/content/download/(.+)\.html$#' => ['index.php', 'download'],
    '#^/content/(.+)\.html$#' => ['index.php', 'follow'],
    '#^/action/(.+)\.html$#' => ['index.php', 'action'],
    '#^/rss/(.+)\.rss$#' => ['rss.php', 'follow'],
];

$pms_script = '';
foreach ($pms_rules as $pms_pattern => [$pms_target, $pms_parameter]) {
    if (!preg_match($pms_pattern, $pms_path, $pms_match)) {
        continue;
    }

    $pms_script = $pms_target;
    if ($pms_parameter !== '') {
        // Apache gibt den Wert über die Rewrite-Regel mit
        $_GET[$pms_parameter] = $pms_match[1];
        $_REQUEST[$pms_parameter] = $pms_match[1];
    }
    break;
}

// Die Regeln "^content/(.*)$ $1" und "^action/(.*)$ $1" aus der .htaccess:
// Eine Seite unter /content/... lädt ihre Bilder und Skripte relativ, also
// unter /content/bild.jpg. Apache entfernt den virtuellen Ordner; der
// eingebaute Server kennt ihn nicht und müsste die Datei sonst suchen.
if ($pms_script === '' && preg_match('#^/(?:content/download|content|action|rss)/(.+)$#', $pms_path, $pms_match)) {
    $pms_file = $pms_root . '/' . $pms_match[1];

    if (is_file($pms_file) && str_ends_with($pms_match[1], '.php')) {
        $pms_script = $pms_match[1];
    } elseif (is_file($pms_file)) {
        $pms_types = [
            'css' => 'text/css',
            'js' => 'text/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
        ];
        $pms_extension = strtolower((string)pathinfo($pms_file, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($pms_types[$pms_extension] ?? 'application/octet-stream'));
        readfile($pms_file);
        return true;
    }
}

// Vorhandene Dateien und die übrigen Skripte behandelt der Server selbst
if ($pms_script === '') {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/' . $pms_script;
$_SERVER['SCRIPT_FILENAME'] = $pms_root . '/' . $pms_script;
$_SERVER['PHP_SELF'] = '/' . $pms_script;

require $pms_root . '/' . $pms_script;
return true;
