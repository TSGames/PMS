<?php
/**
 * Baut das lokale Mock-System für das PMS-Admin-Backend auf.
 *
 * Aufruf:  php tests/mock/setup.php [--keep-db]
 *
 * Das Skript legt alles an, was die Anwendung zur Laufzeit erwartet:
 *   - /var/db            SQLite-Datenbank (fest im Code verdrahtet)
 *   - /var/template      Template-Dateien (fest im Code verdrahtet)
 *   - src/template_files Symlink auf /var/template
 *   - src/images/uploads Upload-Verzeichnis
 *   - tests/.runtime     Laufzeitdaten (PHP-Konfiguration, Logs, PID-Datei)
 *
 * Die Datenbank wird aus src/.db_layout.sql erzeugt und mit
 * tests/mock/seed.sql befüllt. Mit --keep-db bleibt eine bestehende
 * Datenbank unangetastet.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Nur über die Kommandozeile aufrufbar.\n");
    exit(1);
}

require_once __DIR__ . '/paths.php';

$keepDb = in_array('--keep-db', $argv, true);

$repo     = PMS_REPO_ROOT;
$src      = PMS_SRC_DIR;
$runtime  = PMS_RUNTIME_DIR;
$dbDir    = PMS_DB_DIR;
$tplDir   = PMS_TEMPLATE_DIR;
$dbFile   = PMS_DB_FILE;

function step(string $msg): void
{
    echo "  " . $msg . "\n";
}

echo "PMS Mock-System einrichten\n";
echo str_repeat('-', 60) . "\n";

// ---------------------------------------------------------------------------
// Verzeichnisse
// ---------------------------------------------------------------------------
foreach ([$dbDir, $tplDir, $runtime, $runtime . '/logs', $src . '/images/uploads'] as $dir) {
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        fwrite(STDERR, "Verzeichnis konnte nicht angelegt werden: $dir\n");
        fwrite(STDERR, "Hinweis: /var/db und /var/template sind im Anwendungscode fest verdrahtet\n");
        fwrite(STDERR, "und benötigen ggf. Root-Rechte (sudo php tests/mock/setup.php).\n");
        exit(1);
    }
}
step("Verzeichnisse bereit");

// ---------------------------------------------------------------------------
// Template
// ---------------------------------------------------------------------------
foreach (glob($repo . '/template/*') as $file) {
    copy($file, $tplDir . '/' . basename($file));
}
step("Template nach $tplDir kopiert");

$link = $src . '/template_files';
if (!is_link($link)) {
    @unlink($link);
    symlink($tplDir, $link);
}
step("Symlink src/template_files -> $tplDir");

// ---------------------------------------------------------------------------
// PHP-Konfiguration für den Testserver
//
// Der eingebaute Entwicklungsserver erbt sonst die Extensions des Systems.
// Das Produktions-Image (php:8.4-apache + gd + zip) kennt z.B. kein mysqli;
// Code, der sich auf dessen Fehlen verlässt, würde lokal anders laufen.
// Deshalb wird ein eigenes conf.d-Verzeichnis ohne diese Extensions erzeugt.
// ---------------------------------------------------------------------------
$blocked = ['mysqli', 'pdo_mysql', 'pdo_pgsql', 'pgsql', 'redis', 'igbinary', 'intl', 'ffi', 'opcache'];
$confDir = $runtime . '/conf.d';
if (!is_dir($confDir)) {
    mkdir($confDir, 0777, true);
}
array_map('unlink', glob($confDir . '/*.ini'));

$systemConfDir = trim((string)ini_get('PHP_INI_SCAN_DIR')) ?: php_ini_scanned_files();
$scanned = php_ini_scanned_files();
if ($scanned) {
    foreach (explode(',', $scanned) as $ini) {
        $ini = trim($ini);
        if ($ini === '' || !is_file($ini)) {
            continue;
        }
        $name = basename($ini);
        $ext  = preg_replace('/^\d+-|\.ini$/', '', $name);
        if (in_array($ext, $blocked, true)) {
            continue;
        }
        copy($ini, $confDir . '/' . $name);
    }
}

file_put_contents($confDir . '/99-pms-mock.ini', <<<INI
; Laufzeiteinstellungen des Mock-Systems (von tests/mock/setup.php erzeugt)
short_open_tag=On
display_errors=On
log_errors=On
error_log={$runtime}/logs/php-error.log
error_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED
upload_max_filesize=20M
post_max_size=20M
memory_limit=128M
date.timezone=Europe/Berlin
session.save_path={$runtime}/sessions

INI);
if (!is_dir($runtime . '/sessions')) {
    mkdir($runtime . '/sessions', 0777, true);
}
step("PHP-Konfiguration erzeugt (" . count(glob($confDir . '/*.ini')) . " Dateien, ohne: " . implode(', ', $blocked) . ")");

// ---------------------------------------------------------------------------
// Datenbank
// ---------------------------------------------------------------------------
if ($keepDb && file_exists($dbFile)) {
    step("Bestehende Datenbank beibehalten ($dbFile)");
} else {
    foreach ([$dbFile, $dbFile . '-wal', $dbFile . '-shm'] as $f) {
        if (file_exists($f)) {
            unlink($f);
        }
    }

    $db = new SQLite3($dbFile);
    $db->exec('PRAGMA journal_mode=WAL;');

    $layout = file_get_contents($src . '/.db_layout.sql');
    if (!$db->exec($layout)) {
        fwrite(STDERR, "Schema konnte nicht angelegt werden: " . $db->lastErrorMsg() . "\n");
        exit(1);
    }
    step("Schema aus src/.db_layout.sql angelegt");

    $seed = file_get_contents(__DIR__ . '/seed.sql');
    if (!$db->exec($seed)) {
        fwrite(STDERR, "Mock-Daten konnten nicht eingespielt werden: " . $db->lastErrorMsg() . "\n");
        exit(1);
    }

    $counts = [];
    foreach (['user', 'cat', 'subcat', 'item', 'menu', 'dynamic', 'poll', 'bans', 'comments'] as $table) {
        $counts[] = $table . '=' . $db->querySingle("SELECT COUNT(*) FROM $table");
    }
    $db->close();
    step("Mock-Daten eingespielt (" . implode(', ', $counts) . ")");
}

// Schreibrechte, falls das Setup als root für einen anderen Nutzer läuft
@chmod($dbFile, 0666);
@chmod($dbDir, 0777);

echo str_repeat('-', 60) . "\n";
echo "Fertig.\n\n";
echo "  Datenbank : $dbFile\n";
echo "  Template  : $tplDir\n";
echo "  Laufzeit  : $runtime\n\n";
echo "  Login     : admin / admin123 (Super-Administrator)\n";
echo "              redakteur / admin123 (Administrator)\n";
echo "              moderator / admin123 (Moderator, kein Backend-Zugriff)\n\n";
echo "  Start     : tests/mock/server.sh start\n";
echo "  Adresse   : http://" . PMS_HOST . ":" . PMS_PORT . "/admin.php\n";
