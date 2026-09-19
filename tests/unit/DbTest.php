<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Data\Db;

/**
 * Data\Db - Datenbankzugriff mit vorbereiteten Anweisungen.
 *
 * Der Altbestand setzte SQL aus Zeichenketten zusammen und übergab Werte aus
 * $_POST teilweise ungeprüft. Hier werden Werte gebunden.
 */
final class DbTest extends DatabaseTestCase
{
    public function testDerTabellennameTraegtDasPraefix(): void
    {
        self::assertSame('pms_cat', Db::table('cat'));
    }

    public function testEinfuegenLiefertDieNeueId(): void
    {
        $id = Db::insert('cat', ['name' => 'Aktuelles', 'sort' => 10]);

        self::assertSame(1, $id);
        self::assertSame(2, Db::insert('cat', ['name' => 'Verein', 'sort' => 20]));
    }

    public function testAuslesenLiefertObjekte(): void
    {
        $this->seed(['name' => 'Aktuelles', 'sort' => 10]);

        $rows = Db::select('SELECT * FROM pms_cat');

        self::assertCount(1, $rows);
        self::assertSame('Aktuelles', $rows[0]->name);
    }

    public function testDieErsteZeileOderNull(): void
    {
        $this->seed(['name' => 'Aktuelles']);

        self::assertSame('Aktuelles', Db::first('SELECT * FROM pms_cat')?->name);
        self::assertNull(Db::first('SELECT * FROM pms_cat WHERE id = :id', ['id' => 99]));
    }

    public function testAendernUndLoeschen(): void
    {
        [$id] = $this->seed(['name' => 'Alt']);

        self::assertTrue(Db::update('cat', $id, ['name' => 'Neu']));
        self::assertSame('Neu', Db::first('SELECT * FROM pms_cat')?->name);

        self::assertTrue(Db::delete('cat', $id));
        self::assertSame(0, Db::count('cat'));
    }

    public function testEinAenderungsaufrufOhneDatenTutNichts(): void
    {
        [$id] = $this->seed(['name' => 'Alt']);

        self::assertTrue(Db::update('cat', $id, []));
        self::assertSame('Alt', Db::first('SELECT * FROM pms_cat')?->name);
    }

    public function testZaehlenMitUndOhneBedingung(): void
    {
        $this->seed(
            ['name' => 'A', 'available' => 1],
            ['name' => 'B', 'available' => 0],
            ['name' => 'C', 'available' => 1],
        );

        self::assertSame(3, Db::count('cat'));
        self::assertSame(2, Db::count('cat', 'available = :a', ['a' => 1]));
    }

    public function testWerteWerdenGebundenUndNichtEingesetzt(): void
    {
        $this->seed(['name' => 'Aktuelles']);

        // Ohne gebundene Werte würde das die Tabelle leeren
        $rows = Db::select(
            'SELECT * FROM pms_cat WHERE name = :name',
            ['name' => "x'; DELETE FROM pms_cat; --"]
        );

        self::assertSame([], $rows);
        self::assertSame(1, Db::count('cat'));
    }

    public function testNullUndZahlenBehaltenIhrenTyp(): void
    {
        $id = Db::insert('cat', ['name' => 'Test', 'sort' => 5, 'available' => 0]);
        $row = Db::first('SELECT * FROM pms_cat WHERE id = :id', ['id' => $id]);

        self::assertSame(5, $row?->sort);
        self::assertSame(0, $row?->available);
    }

    public function testEineFehlerhafteAbfrageLiefertLeereErgebnisse(): void
    {
        // Db meldet den Fehler ins Log; im Testlauf soll das nicht stören
        $previous = ini_set('error_log', '/dev/null');

        try {
            self::assertSame([], Db::select('SELECT * FROM gibtesnicht'));
            self::assertNull(Db::first('SELECT * FROM gibtesnicht'));
            self::assertFalse(Db::execute('KAPUTT'));
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }
    }
}
