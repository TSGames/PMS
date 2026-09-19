<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Support\Url;
use PHPUnit\Framework\TestCase;

/**
 * Support\Url - Adressen der Anwendung.
 *
 * Den Basispfad teilen sich Frontend und Backend; welche Adresse zu einer
 * Aktion gehört, hinterlegt der jeweilige Einstiegspunkt.
 */
final class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        Url::reset();
        $_SERVER['SCRIPT_NAME'] = '/admin.php';
    }

    protected function tearDown(): void
    {
        Url::reset();
    }

    public function testImWurzelverzeichnisIstDerBasispfadLeer(): void
    {
        self::assertSame('', Url::base());
        self::assertSame('/', Url::baseHref());
        self::assertSame('/admin.css', Url::asset('admin.css'));
    }

    public function testInEinemUnterverzeichnisTragenAlleAdressenDenBasispfad(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/pms/admin.php';

        self::assertSame('/pms', Url::base());
        self::assertSame('/pms/', Url::baseHref());
        self::assertSame('/pms/admin.css', Url::asset('admin.css'));
        self::assertSame('/pms/admin.css', Url::asset('/admin.css'));
    }

    public function testDieHinterlegteAufloesungBestimmtDenPfad(): void
    {
        Url::resolveWith(static fn(string $action): string => '/admin/' . $action);

        self::assertSame('/admin/kategorien', Url::to('kategorien'));
        self::assertSame('/admin/kategorien?edit=5', Url::to('kategorien', ['edit' => 5]));
    }

    public function testParameterWerdenMaskiert(): void
    {
        Url::resolveWith(static fn(string $action): string => '/admin/' . $action);

        self::assertStringContainsString('q=a%26b', Url::to('inhalte', ['q' => 'a&b']));
    }

    public function testOhneAufloesungBleibtNurDerBasispfad(): void
    {
        // Sollte nie vorkommen; besser die Startseite als eine falsche Adresse
        self::assertSame('/', Url::to('kategorien'));
    }
}
