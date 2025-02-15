<?php
if (php_sapi_name() !== 'cli') {
    die();
}
require "config.php";
require "functions_global.php";


function convertMysqlToSqlite($mysqlDump) {
    $sqliteDump = $mysqlDump;

    $sqliteDump = preg_replace('/AUTO_INCREMENT=[0-9]+/', '', $sqliteDump);
    $sqliteDump = preg_replace('/ENGINE=[a-zA-Z0-9]+/', '', $sqliteDump);
    $sqliteDump = preg_replace('/CHARSET=[a-zA-Z0-9]+/', '', $sqliteDump);
    $sqliteDump = preg_replace('/COLLATE=[a-zA-Z0-9_]+/', '', $sqliteDump);
    $sqliteDump = preg_replace('/COMMIT;/', '', $sqliteDump);
    
    $sqliteDump = preg_replace('/\bUNSIGNED\b/i', '', $sqliteDump);
    
    $sqliteDump = preg_replace('/\bTINYINT\b/i', 'INTEGER', $sqliteDump);
    $sqliteDump = preg_replace('/\bSMALLINT\b/i', 'INTEGER', $sqliteDump);
    $sqliteDump = preg_replace('/\bMEDIUMINT\b/i', 'INTEGER', $sqliteDump);
    $sqliteDump = preg_replace('/\bINT\b/i', 'INTEGER', $sqliteDump);
    $sqliteDump = preg_replace('/\bBIGINT\b/i', 'INTEGER', $sqliteDump);
    $sqliteDump = preg_replace('/\bDOUBLE\b/i', 'REAL', $sqliteDump);
    $sqliteDump = preg_replace('/\bFLOAT\b/i', 'REAL', $sqliteDump);
    $sqliteDump = preg_replace('/\bDECIMAL\([0-9,]+\)/i', 'REAL', $sqliteDump);
    $sqliteDump = preg_replace('/\bDATETIME\b/i', 'TEXT', $sqliteDump);
    $sqliteDump = preg_replace('/\bTIMESTAMP\b/i', 'TEXT', $sqliteDump);
    $sqliteDump = preg_replace('/\bTEXT CHARACTER SET [a-zA-Z0-9_]+/i', 'TEXT', $sqliteDump);
    $sqliteDump = preg_replace('/\bVARCHAR\(([0-9]+)\)/i', 'TEXT', $sqliteDump);

    $sqliteDump = preg_replace('/INSERT IGNORE INTO/i', 'INSERT OR IGNORE INTO', $sqliteDump);
    $sqliteDump = preg_replace('/REPLACE INTO/i', 'INSERT OR REPLACE INTO', $sqliteDump);
    $sqliteDump = str_replace("\\'", "''", $sqliteDump);
    $sqliteDump = str_replace("\\r", "", $sqliteDump);
    $sqliteDump = str_replace("\\\"", "\"", $sqliteDump);
    $sqliteDump = str_replace("\\n", "
", $sqliteDump);

    return $sqliteDump;
}

if(file_exists("/var/db/import.mysql.sql")) {
    $pms_db_connection->init(false);
    $pms_db_connection->exec(convertMysqlToSqlite(file_get_contents("/var/db/import.mysql.sql")));
    error_log("Importing from /var/db/import.sql");
    if($pms_db_connection->error() !== 'not an error') {
        error_log($pms_db_connection->error());
        file_put_contents("/var/db/import.sqlite.sql", convertMysqlToSqlite(file_get_contents("/var/db/import.mysql.sql")));
        exit(1);
    } else {
        rename("/var/db/import.mysql.sql", "/var/db/import.mysql.sql.bak");
    }
} else {
    error_log("no import.sql specified");
    $pms_db_connection->init(true);
}
exit(0);