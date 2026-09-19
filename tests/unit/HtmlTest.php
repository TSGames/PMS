<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Support\Html;
use PHPUnit\Framework\TestCase;

/**
 * Support\Html - Bausteine der Ausgabe.
 *
 * Wichtigste Eigenschaft: Jeder dynamische Wert wird maskiert. Der
 * Altbestand setzte Werte aus der Datenbank roh in das Markup.
 */
final class HtmlTest extends TestCase
{
    public function testMaskiertJedenWert(): void
    {
        self::assertSame('&lt;script&gt;', Html::e('<script>'));
        self::assertSame('a &amp; b', Html::e('a & b'));
        self::assertSame('&quot;x&quot;', Html::e('"x"'));
        self::assertSame('&#039;x&#039;', Html::e("'x'"));
        self::assertSame('', Html::e(null));
    }

    public function testBautAdressenAusAktionUndParametern(): void
    {
        self::assertSame('/admin/kategorien', Html::url('cat'));
        self::assertSame('/admin/kategorien?edit=5', Html::url('cat', ['edit' => 5]));
        self::assertSame('/admin', Html::url('gibtesnicht'));
    }

    public function testEineAdresseMaskiertIhreParameter(): void
    {
        $url = Html::url('item', ['q' => 'a&b']);

        self::assertStringContainsString('q=a%26b', $url);
    }

    public function testEineTabelleBekommtKopfZeilenUndBeschriftungen(): void
    {
        $html = Html::table(['Name', 'Wert'], [['Verein', '1']]);

        self::assertStringContainsString('<table class="data-table">', $html);
        self::assertStringContainsString('<thead><tr><th>Name</th><th>Wert</th></tr></thead>', $html);
        // data-label trägt die Beschriftung für die Kartenansicht auf schmalen Geräten
        self::assertStringContainsString('<td data-label="Name">Verein</td>', $html);
    }

    public function testEineLeereTabelleZeigtDenHinweisstattEinesGeruests(): void
    {
        $html = Html::table(['Name'], [], 'Nichts da.');

        self::assertStringNotContainsString('<table', $html);
        self::assertStringContainsString('Nichts da.', $html);
    }

    public function testEinAuswahlfeldMarkiertDenGewaehltenWert(): void
    {
        $html = Html::select('cat', [1 => 'Aktuelles', 2 => 'Verein'], 2);

        self::assertStringContainsString('<option value="1">Aktuelles</option>', $html);
        self::assertStringContainsString('<option value="2" selected>Verein</option>', $html);
    }

    public function testEinAuswahlfeldMaskiertBeschriftungen(): void
    {
        $html = Html::select('cat', [1 => '<b>fett</b>'], 1);

        self::assertStringContainsString('&lt;b&gt;fett&lt;/b&gt;', $html);
        self::assertStringNotContainsString('<b>', $html);
    }

    public function testFormulareTragenEinTokenUndVerzichtenAufDieBrowserPruefung(): void
    {
        $html = Html::formOpen('cat');

        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('novalidate', $html);
        self::assertStringContainsString('name="pms_token"', $html);
    }

    public function testEinGetFormularBekommtKeinToken(): void
    {
        $html = Html::formOpen('cat', [], ['method' => 'get']);

        self::assertStringNotContainsString('pms_token', $html);
        self::assertStringNotContainsString('novalidate', $html);
    }

    public function testDatumUndJaNein(): void
    {
        self::assertSame('-', Html::date(0));
        self::assertSame('01.06.2024', Html::date(mktime(12, 0, 0, 6, 1, 2024)));
        self::assertSame('Ja', Html::yesNo(1));
        self::assertSame('Nein', Html::yesNo(0));
    }
}
