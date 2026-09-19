<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Support\Listing - Suche, Sortierung und Seitenaufteilung einer Übersicht.
 */
final class ListingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            ['name' => 'Aktuelles', 'sort' => 10, 'available' => 1],
            ['name' => 'Dokumente', 'sort' => 20, 'available' => 1],
            ['name' => 'Verein', 'sort' => 30, 'available' => 1],
            ['name' => 'Archiv', 'sort' => 40, 'available' => 0],
        );

        // Die Zahl der Einträge je Seite kommt aus der Website-Konfiguration
        $GLOBALS['config_values'] = (object)['page_limit' => 15];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['config_values']);
        parent::tearDown();
    }

    /** Bindet eine Anfrage mit den angegebenen Adressparametern. */
    private function request(array $query = []): void
    {
        Request::bind(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/admin/kategorien')
                ->withQueryParams($query),
            'cat'
        );
    }

    /** @return list<string> */
    private function names(Listing $list): array
    {
        return array_map(static fn(object $row): string => (string)$row->name, $list->rows);
    }

    private function listing(): Listing
    {
        return Listing::from('cat')
            ->searchIn(['name'])
            ->sortableBy(['id' => 'id', 'name' => 'name', 'sort' => 'sort'])
            ->orderedBy('sort, name');
    }

    public function testOhneParameterGiltDieGrundreihenfolge(): void
    {
        $this->request();
        $list = $this->listing()->load();

        self::assertSame(['Aktuelles', 'Dokumente', 'Verein', 'Archiv'], $this->names($list));
        self::assertSame(4, $list->total);
        self::assertTrue($list->isDefaultOrder());
        self::assertFalse($list->isFiltered());
    }

    public function testDieSucheSchraenktEin(): void
    {
        $this->request([Listing::SEARCH => 'ver']);
        $list = $this->listing()->load();

        self::assertSame(['Verein'], $this->names($list));
        self::assertSame(1, $list->total);
        self::assertTrue($list->isFiltered());
    }

    public function testNachEinerSpalteWirdInBeideRichtungenSortiert(): void
    {
        $this->request([Listing::ORDER => 'name', Listing::DIRECTION => 'asc']);
        self::assertSame(['Aktuelles', 'Archiv', 'Dokumente', 'Verein'], $this->names($this->listing()->load()));

        $this->request([Listing::ORDER => 'name', Listing::DIRECTION => 'desc']);
        self::assertSame(['Verein', 'Dokumente', 'Archiv', 'Aktuelles'], $this->names($this->listing()->load()));
    }

    public function testEineUnbekannteSpalteFaelltAufDieGrundreihenfolgeZurueck(): void
    {
        // Aus der Adresse kommt nur ein Schlüssel, nie ein Spaltenname
        $this->request([Listing::ORDER => 'name); DROP TABLE pms_cat; --']);
        $list = $this->listing()->load();

        self::assertSame(['Aktuelles', 'Dokumente', 'Verein', 'Archiv'], $this->names($list));
        self::assertTrue($list->isDefaultOrder());
    }

    public function testEinFilterWirktUndBleibtInDenLinksErhalten(): void
    {
        $this->request(['available' => '0']);
        $list = $this->listing()
            ->keep('available', '0')
            ->where('available = :available', ['available' => 0])
            ->load();

        self::assertSame(['Archiv'], $this->names($list));
        self::assertTrue($list->isFiltered());
        self::assertSame(['available' => '0'], $list->params());
    }

    public function testDieSeitenaufteilungRichtetSichNachDerKonfiguration(): void
    {
        $GLOBALS['config_values'] = (object)['page_limit' => 2];

        $this->request();
        $first = $this->listing()->load();

        self::assertSame(['Aktuelles', 'Dokumente'], $this->names($first));
        self::assertSame(4, $first->total);
        self::assertSame(2, $first->pages);
        self::assertSame(1, $first->page);

        $this->request([Listing::PAGE => '2']);
        self::assertSame(['Verein', 'Archiv'], $this->names($this->listing()->load()));
    }

    public function testEineZuHoheSeitenzahlLandetAufDerLetztenSeite(): void
    {
        $GLOBALS['config_values'] = (object)['page_limit' => 2];

        $this->request([Listing::PAGE => '99']);
        $list = $this->listing()->load();

        self::assertSame(2, $list->page);
        self::assertSame(['Verein', 'Archiv'], $this->names($list));
    }

    public function testUnpagedZeigtAllesAufEinerSeite(): void
    {
        $GLOBALS['config_values'] = (object)['page_limit' => 2];

        $this->request();
        $list = $this->listing()->unpaged()->load();

        self::assertCount(4, $list->rows);
        self::assertSame(1, $list->pages);
    }

    public function testDieParameterEinerAnsichtLassenSichUeberschreiben(): void
    {
        $this->request([Listing::SEARCH => 'e', Listing::ORDER => 'name', Listing::DIRECTION => 'desc']);
        $list = $this->listing()->load();

        self::assertSame(
            [Listing::SEARCH => 'e', Listing::ORDER => 'name', Listing::DIRECTION => 'desc'],
            $list->params()
        );
        // Eine leere Zeichenkette entfernt den Parameter
        self::assertArrayNotHasKey(Listing::SEARCH, $list->params([Listing::SEARCH => '']));
        self::assertSame(3, $list->params([Listing::PAGE => 3])[Listing::PAGE]);
    }

    public function testEineVerknuepfteAbfrageZaehltDatensaetzeStattGruppen(): void
    {
        $this->connection->exec('CREATE TABLE pms_item (id INTEGER PRIMARY KEY, cat INTEGER)');
        $this->connection->exec('INSERT INTO pms_item (id, cat) VALUES (1, 1), (2, 1), (3, 2)');

        $this->request();
        $list = Listing::from('cat')
            ->alias('c')
            ->join('LEFT JOIN pms_item i ON i.cat = c.id')
            ->groupBy('c.id', 'COUNT(DISTINCT c.id)')
            ->select('c.*, COUNT(i.id) AS items')
            ->orderedBy('c.sort')
            ->load();

        self::assertSame(4, $list->total);
        self::assertSame(2, $list->rows[0]->items);
    }
}
