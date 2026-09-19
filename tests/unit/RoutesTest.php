<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Http\Routes;
use PHPUnit\Framework\TestCase;

/**
 * Http\Routes - die Adressen des Backends.
 *
 * Aus der Adresse kommt nur ein Schlüssel; unbekannte Werte landen auf der
 * Startseite statt irgendwo.
 */
final class RoutesTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/admin.php';
        $_SERVER['REQUEST_URI'] = '/admin';
        $_GET = [];
        $_POST = [];
    }

    public function testJedeAktionHatEinenSprechendenPfad(): void
    {
        self::assertSame('/admin', Routes::path('home'));
        self::assertSame('/admin/kategorien', Routes::path('cat'));
        self::assertSame('/admin/inhalte/wiederherstellen', Routes::path('item_restore'));
    }

    public function testEineUnbekannteAktionFuehrtZurStartseite(): void
    {
        self::assertSame('/admin', Routes::path('gibtesnicht'));
        self::assertFalse(Routes::has('gibtesnicht'));
        self::assertTrue(Routes::has('cat'));
    }

    public function testZuEinemPfadGehoertEineAktion(): void
    {
        self::assertSame('cat', Routes::actionFor('/admin/kategorien'));
        self::assertSame('', Routes::actionFor('/etwas/anderes'));
    }

    public function testDieAktionKommtAusDemPfad(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/benutzer?edit=2';

        self::assertSame('user', Routes::currentAction());
    }

    public function testDieFruehereAdressformWirdWeiterVerstanden(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin.php?action=cat';
        $_GET['action'] = 'cat';

        self::assertSame('cat', Routes::currentAction());
    }

    public function testEinUnbekannterParameterLandetAufDerStartseite(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin.php?action=../../etc/passwd';
        $_GET['action'] = '../../etc/passwd';

        self::assertSame('home', Routes::currentAction());
    }

    public function testDasModulStehtImPfadOderInDerAdresse(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/modul/update';
        self::assertSame('update', Routes::currentModule());

        $_SERVER['REQUEST_URI'] = '/admin.php?modul=update';
        $_GET['modul'] = 'update';
        self::assertSame('update', Routes::currentModule());
    }

    public function testInEinemUnterverzeichnisTragenAlleAdressenDenBasispfad(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/pms/admin.php';

        self::assertSame('/pms', Routes::basePath());
        self::assertSame('/pms/admin/kategorien', Routes::path('cat'));
        self::assertSame('/pms/', Routes::baseHref());

        $_SERVER['REQUEST_URI'] = '/pms/admin/kategorien';
        self::assertSame('cat', Routes::currentAction());
    }

    public function testImWurzelverzeichnisIstDerBasispfadLeer(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/admin.php';

        self::assertSame('', Routes::basePath());
        self::assertSame('/', Routes::baseHref());
    }
}
