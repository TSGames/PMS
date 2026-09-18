<?php
/**
 * Setzt nur die Datenbank auf den Ausgangszustand zurück.
 *
 * Aufruf:  php tests/mock/reset-db.php
 *
 * Die Tests rufen dieses Skript vor jedem Testfall auf. Es rührt weder die
 * PHP-Konfiguration noch das Template an, damit parallel laufende Anfragen
 * nicht gestört werden.
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Nur über die Kommandozeile aufrufbar.\n");
    exit(1);
}

require_once __DIR__ . '/database.php';

pms_build_mock_database();
