<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Csrf;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Support\Sorting - Verschieben eines Eintrags über die Pfeile.
 */
final class SortingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->seed(
            ['name' => 'Aktuelles', 'sort' => 10],
            ['name' => 'Dokumente', 'sort' => 20],
            ['name' => 'Verein', 'sort' => 30],
        );
    }

    private function request(array $query): void
    {
        Request::bind(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/admin/kategorien')
                ->withQueryParams($query),
            'cat'
        );
        $_GET = $query;
    }

    private function rows(): array
    {
        return Db::select('SELECT * FROM pms_cat ORDER BY sort, name');
    }

    public function testOhneAnfrageWirdNichtSortiert(): void
    {
        $this->request([]);

        self::assertFalse(Sorting::handleRequest('cat'));
    }

    public function testOhneTokenWirdNichtSortiert(): void
    {
        $this->request(['sort' => 'yes', 'pos' => '5', 'id' => '2']);

        self::assertFalse(Sorting::handleRequest('cat'));
        self::assertSame(20, $this->rows()[1]->sort);
    }

    public function testMitTokenWirdDieSortiernummerGesetzt(): void
    {
        $this->request(['sort' => 'yes', 'pos' => '9', 'id' => '2', Csrf::FIELD => Csrf::token()]);

        self::assertTrue(Sorting::handleRequest('cat'));
        self::assertSame('Dokumente', $this->rows()[0]->name);
        self::assertSame(9, $this->rows()[0]->sort);
    }

    public function testDerErsteEintragHatKeinenPfeilNachOben(): void
    {
        $rows = $this->rows();
        $cell = Sorting::cell('cat', null, $rows[0], $rows[1]);

        self::assertStringNotContainsString('Nach oben', $cell);
        self::assertStringContainsString('Nach unten', $cell);
        self::assertStringContainsString('10', $cell);
    }

    public function testDerLetzteEintragHatKeinenPfeilNachUnten(): void
    {
        $rows = $this->rows();
        $cell = Sorting::cell('cat', $rows[1], $rows[2], null);

        self::assertStringContainsString('Nach oben', $cell);
        self::assertStringNotContainsString('Nach unten', $cell);
    }

    public function testDerPfeilZieltKnappVorDenNachbarn(): void
    {
        $rows = $this->rows();
        $cell = Sorting::cell('cat', $rows[0], $rows[1], $rows[2]);

        // Vor Aktuelles (10) bedeutet 9, hinter Verein (30) bedeutet 31
        self::assertStringContainsString('pos=9', $cell);
        self::assertStringContainsString('pos=31', $cell);
        self::assertStringContainsString('id=2', $cell);
        self::assertStringContainsString(Csrf::FIELD, $cell);
    }
}
