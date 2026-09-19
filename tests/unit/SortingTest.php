<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Controller\Controller;
use Pms\Backend\View\Components;
use Pms\Data\Db;
use Pms\Support\Csrf;
use Pms\Support\Request;
use Pms\Support\Url;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Verschieben eines Eintrags über die Pfeile.
 *
 * Das Verschieben selbst gehört zum Controller, die Zelle mit den Pfeilen
 * zu den Ausgabebausteinen.
 */
final class SortingTest extends DatabaseTestCase
{
    private Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Url::resolveWith(static fn(string $action): string => '/admin/' . $action);

        $this->seed(
            ['name' => 'Aktuelles', 'sort' => 10],
            ['name' => 'Dokumente', 'sort' => 20],
            ['name' => 'Verein', 'sort' => 30],
        );

        $this->controller = new class extends Controller {
            #[\Override]
            public function action(): string
            {
                return 'cat';
            }

            #[\Override]
            public function handle(): string
            {
                return '';
            }

            /** Macht die geschützte Methode für den Test erreichbar. */
            public function sort(string $table): bool
            {
                return $this->handleSorting($table);
            }
        };
    }

    protected function tearDown(): void
    {
        Url::reset();
        parent::tearDown();
    }

    /** @param array<string, string> $query */
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

    /** @return list<object> */
    private function rows(): array
    {
        return Db::select('SELECT * FROM pms_cat ORDER BY sort, name');
    }

    public function testOhneAnfrageWirdNichtSortiert(): void
    {
        $this->request([]);

        self::assertFalse($this->controller->sort('cat'));
    }

    public function testOhneTokenWirdNichtSortiert(): void
    {
        $this->request(['sort' => 'yes', 'pos' => '5', 'id' => '2']);

        self::assertFalse($this->controller->sort('cat'));
        self::assertSame(20, $this->rows()[1]->sort);
    }

    public function testOhneIdWirdNichtSortiert(): void
    {
        $this->request(['sort' => 'yes', 'pos' => '5', Csrf::FIELD => Csrf::token()]);

        self::assertFalse($this->controller->sort('cat'));
    }

    public function testMitTokenWirdDieSortiernummerGesetzt(): void
    {
        $this->request(['sort' => 'yes', 'pos' => '9', 'id' => '2', Csrf::FIELD => Csrf::token()]);

        self::assertTrue($this->controller->sort('cat'));
        self::assertSame('Dokumente', $this->rows()[0]->name);
        self::assertSame(9, $this->rows()[0]->sort);
    }

    public function testDerErsteEintragHatKeinenPfeilNachOben(): void
    {
        $rows = $this->rows();
        $cell = Components::sortCell('cat', null, $rows[0], $rows[1]);

        self::assertStringNotContainsString('Nach oben', $cell);
        self::assertStringContainsString('Nach unten', $cell);
        self::assertStringContainsString('10', $cell);
    }

    public function testDerLetzteEintragHatKeinenPfeilNachUnten(): void
    {
        $rows = $this->rows();
        $cell = Components::sortCell('cat', $rows[1], $rows[2], null);

        self::assertStringContainsString('Nach oben', $cell);
        self::assertStringNotContainsString('Nach unten', $cell);
    }

    public function testDerPfeilZieltKnappVorDenNachbarn(): void
    {
        $rows = $this->rows();
        $cell = Components::sortCell('cat', $rows[0], $rows[1], $rows[2]);

        // Vor Aktuelles (10) bedeutet 9, hinter Verein (30) bedeutet 31
        self::assertStringContainsString('pos=9', $cell);
        self::assertStringContainsString('pos=31', $cell);
        self::assertStringContainsString('id=2', $cell);
        self::assertStringContainsString(Csrf::FIELD, $cell);
    }
}
