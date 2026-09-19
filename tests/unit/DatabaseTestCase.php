<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Grundlage für die Tests, die eine Datenbank brauchen.
 *
 * Jeder Testfall bekommt eine frische Datenbank im Arbeitsspeicher mit einer
 * kleinen Tabelle, die den echten Tabellen des Systems nachempfunden ist.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected \pms_db_class $connection;

    protected function setUp(): void
    {
        $this->connection = new \pms_db_class();
        $GLOBALS['pms_db_connection'] = $this->connection;
        $GLOBALS['pms_db_prefix'] = 'pms_';

        $this->connection->exec(
            'CREATE TABLE pms_cat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                sort INTEGER NOT NULL DEFAULT 1000,
                available INTEGER NOT NULL DEFAULT 1
            )'
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['pms_db_connection'], $GLOBALS['pms_db_prefix']);
        $_GET = [];
        $_POST = [];
    }

    /** Legt Kategorien an und liefert ihre IDs. */
    protected function seed(array ...$rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = \Pms\Backend\Data\Db::insert('cat', $row);
        }
        return $ids;
    }
}
