<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Support\Request;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Support\Request - typisierter Zugriff auf die Eingaben.
 *
 * Der Altbestand rechnete mit Rohwerten; unter PHP 8 brach das bei leeren
 * Feldern ab ("" * 1). Hier kommt immer der erwartete Typ heraus.
 */
final class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
    }

    /** @param array<string, mixed> $body */
    private function bind(array $body = [], array $query = []): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/admin/kategorien')
            ->withParsedBody($body)
            ->withQueryParams($query);

        Request::bind($request, 'cat');
    }

    public function testLiestGanzzahlenAusFormularUndAdresse(): void
    {
        $this->bind(['sort' => '42'], ['edit' => '7']);

        self::assertSame(42, Request::int('sort'));
        self::assertSame(7, Request::int('edit'));
        self::assertSame(7, Request::queryInt('edit'));
    }

    public function testLeereUndUnsinnigeZahlenLiefernDenVorgabewert(): void
    {
        $this->bind(['leer' => '', 'text' => 'abc']);

        self::assertSame(0, Request::int('leer'));
        self::assertSame(1000, Request::int('leer', 1000));
        self::assertSame(1000, Request::int('text', 1000));
        self::assertSame(5, Request::int('fehlt', 5));
    }

    public function testDasFormularGehtVorDerAdresse(): void
    {
        $this->bind(['id' => '2'], ['id' => '9']);

        self::assertSame(2, Request::int('id'));
        // queryInt greift bewusst nur auf die Adresse zu
        self::assertSame(9, Request::queryInt('id'));
    }

    public function testKommazahlenAkzeptierenDasDeutscheKomma(): void
    {
        $this->bind(['zeit' => '1,5', 'leer' => '', 'text' => 'x']);

        self::assertSame(1.5, Request::float('zeit'));
        self::assertSame(0.0, Request::float('leer'));
        self::assertSame(3.0, Request::float('text', 3.0));
    }

    public function testZeichenkettenWerdenGetrimmtUndTextBleibtRoh(): void
    {
        $this->bind(['name' => '  Verein  ', 'inhalt' => "  Zeile\n\n  "]);

        self::assertSame('Verein', Request::string('name'));
        self::assertSame("  Zeile\n\n  ", Request::text('inhalt'));
        self::assertSame('Vorgabe', Request::string('fehlt', 'Vorgabe'));
    }

    public function testKontrollkaestchenLiefernNullOderEins(): void
    {
        $this->bind(['an' => '1', 'aus' => '0', 'leer' => '']);

        self::assertSame(1, Request::checkbox('an'));
        self::assertSame(0, Request::checkbox('aus'));
        self::assertSame(0, Request::checkbox('leer'));
        // Ein nicht angehaktes Kästchen wird gar nicht gesendet
        self::assertSame(0, Request::checkbox('fehlt'));
    }

    public function testListenEnthaltenNurZahlen(): void
    {
        $this->bind(['user_guestbook' => ['1', ' 2 ', 'x', '']]);

        self::assertSame([1, 2], Request::intList('user_guestbook'));
        self::assertSame([], Request::intList('fehlt'));
    }

    public function testAbgeschickteSchalterWerdenErkannt(): void
    {
        $this->bind(['cat' => 'Speichern']);

        self::assertTrue(Request::submitted('cat'));
        self::assertFalse(Request::submitted('delete'));
        self::assertTrue(Request::isPost());
        self::assertSame('cat', Request::action());
    }

    public function testOhneGebundeneAnfrageGreiftDerRueckfallAufPostUndGet(): void
    {
        Request::bind(
            (new ServerRequestFactory())->createServerRequest('GET', '/admin'),
            ''
        );
        $reflection = new \ReflectionClass(Request::class);
        $reflection->getProperty('current')->setValue(null, null);

        $_POST = ['name' => 'aus POST'];
        $_GET = ['seite' => '3'];

        self::assertSame('aus POST', Request::string('name'));
        self::assertSame(3, Request::queryInt('seite'));
    }
}
