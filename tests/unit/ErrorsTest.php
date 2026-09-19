<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Support\Errors;
use PHPUnit\Framework\TestCase;

/**
 * Support\Errors - Meldungen am betroffenen Feld.
 */
final class ErrorsTest extends TestCase
{
    protected function setUp(): void
    {
        Errors::reset();
    }

    public function testEinFeldOhneMeldungIstLeer(): void
    {
        self::assertSame('', Errors::get('name'));
        self::assertFalse(Errors::has());
        self::assertFalse(Errors::has('name'));
    }

    public function testEineMeldungWirdDemFeldZugeordnet(): void
    {
        Errors::add('name', 'Bitte ausfüllen.');

        self::assertSame('Bitte ausfüllen.', Errors::get('name'));
        self::assertTrue(Errors::has());
        self::assertTrue(Errors::has('name'));
        self::assertFalse(Errors::has('mail'));
    }

    public function testDieErsteMeldungJeFeldGewinnt(): void
    {
        Errors::add('name', 'zu kurz');
        Errors::add('name', 'schon vergeben');

        self::assertSame('zu kurz', Errors::get('name'));
    }

    public function testAlleMeldungenLassenSichAuslesen(): void
    {
        Errors::add('name', 'zu kurz');
        Errors::add('mail', 'ungültig');

        self::assertSame(['name' => 'zu kurz', 'mail' => 'ungültig'], Errors::all());
    }
}
