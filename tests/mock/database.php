<?php
/**
 * Legt die Mock-Datenbank an.
 *
 * Wird von setup.php (vollständige Einrichtung) und von reset-db.php
 * (Zurücksetzen zwischen Tests) benutzt.
 */

require_once __DIR__ . '/paths.php';

/**
 * Baut die Datenbank neu auf: Schema aus src/.db_layout.sql, Daten aus
 * tests/mock/seed.sql.
 *
 * @return array<string, int> Anzahl der Datensätze je Tabelle
 */
/** Loescht ein Verzeichnis samt Inhalt. */
function pms_remove_directory(string $path): void
{
    if (!is_dir($path)) {
        @unlink($path);
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            pms_remove_directory($path . '/' . $entry);
        }
    }

    @rmdir($path);
}

function pms_build_mock_database(): array
{
    foreach ([PMS_DB_FILE, PMS_DB_FILE . '-wal', PMS_DB_FILE . '-shm'] as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Auch die Sicherungen zuruecksetzen: Sie gehoeren zum Datenbestand.
    // Sonst sieht ein Test, der "noch keine Sicherung" erwartet, die
    // Sicherung, die ein frueherer Testlauf angelegt hat.
    foreach (glob(dirname(PMS_DB_FILE) . '/backup*') ?: [] as $folder) {
        pms_remove_directory($folder);
    }

    $db = new SQLite3(PMS_DB_FILE);
    $db->exec('PRAGMA journal_mode=WAL;');

    if (!$db->exec(file_get_contents(PMS_SRC_DIR . '/.db_layout.sql'))) {
        throw new RuntimeException('Schema konnte nicht angelegt werden: ' . $db->lastErrorMsg());
    }
    if (!$db->exec(file_get_contents(__DIR__ . '/seed.sql'))) {
        throw new RuntimeException('Mock-Daten konnten nicht eingespielt werden: ' . $db->lastErrorMsg());
    }

    $counts = [];
    foreach (['user', 'cat', 'subcat', 'item', 'menu', 'dynamic', 'poll', 'bans', 'comments'] as $table) {
        $counts[$table] = (int)$db->querySingle("SELECT COUNT(*) FROM $table");
    }
    $db->close();

    @chmod(PMS_DB_FILE, 0666);

    return $counts;
}
