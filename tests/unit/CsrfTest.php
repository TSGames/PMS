<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Support\Csrf;
use PHPUnit\Framework\TestCase;

/**
 * Support\Csrf - Schutz vor fremd ausgelösten Anfragen.
 */
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    public function testDasTokenBleibtInnerhalbDerSitzungGleich(): void
    {
        $token = Csrf::token();

        self::assertNotSame('', $token);
        self::assertSame($token, Csrf::token());
    }

    public function testEineNeueSitzungBekommtEinNeuesToken(): void
    {
        $first = Csrf::token();
        $_SESSION = [];

        self::assertNotSame($first, Csrf::token());
    }

    public function testDieAnfrageWirdOhneTokenAbgewiesen(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::check());
    }

    public function testEinFalschesTokenWirdAbgewiesen(): void
    {
        Csrf::token();
        $_POST[Csrf::FIELD] = 'falsch';

        self::assertFalse(Csrf::check());
    }

    public function testDasRichtigeTokenWirdAngenommen(): void
    {
        $_POST[Csrf::FIELD] = Csrf::token();

        self::assertTrue(Csrf::check());
    }

    public function testEinTokenAusDerAdresseZaehltEbenfalls(): void
    {
        $_GET[Csrf::FIELD] = Csrf::token();

        self::assertTrue(Csrf::check());
    }

    public function testDasFormularfeldTraegtDasToken(): void
    {
        $field = Csrf::field();

        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString(Csrf::token(), $field);
    }

    public function testDerLinkParameterTraegtDasToken(): void
    {
        self::assertSame([Csrf::FIELD => Csrf::token()], Csrf::queryParam());
    }
}
