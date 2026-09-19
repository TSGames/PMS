<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Frontend\Http\Routes;
use Pms\Frontend\Http\Target;
use Pms\Support\Url;
use PHPUnit\Framework\TestCase;

/**
 * Frontend\Http\Routes - die beiden Adressformen des Frontends.
 *
 * Die Prüfungen halten fest, was bisher nur im Kopf von index.php stand:
 * welcher sprechende Pfad zu welchem Datensatz gehört und wann aus einem
 * Pfad eine Suche wird.
 */
final class FrontendRoutesTest extends TestCase
{
    /** Kennungen, die im Test als vorhandener Inhalt gelten. */
    private const ITEMS = [7, 12];

    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET = [];
        $_POST = [];
        Url::reset();
        $this->speakingLinks(true);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['config_values']);
        Url::reset();
    }

    private function speakingLinks(bool $on): void
    {
        $GLOBALS['config_values'] = (object)['speciallinks' => $on];
    }

    /** @param array<string, mixed> $query */
    private function resolve(array $query): Target
    {
        return Routes::resolve($query, static fn(int $id): bool => in_array($id, self::ITEMS, true));
    }

    public function testSprechenderPfadFuehrtZumInhalt(): void
    {
        self::assertSame(7, $this->resolve(['follow' => 'Satzung-7'])->item);
    }

    public function testKennbuchstabeUnterscheidetDieDatensatzart(): void
    {
        self::assertSame(3, $this->resolve(['follow' => 'Aktuelles-3c'])->cat);
        self::assertSame(4, $this->resolve(['follow' => 'Termine-4s'])->subcat);

        $user = $this->resolve(['follow' => 'Anna-9u']);
        self::assertSame('user', $user->action);
        self::assertSame(9, $user->id);
    }

    public function testEinPfadOhneKennungIstEineSuche(): void
    {
        $target = $this->resolve(['follow' => 'Weihnachtskonzert']);

        self::assertSame('search', $target->action);
        self::assertSame('Weihnachtskonzert', $target->search);
    }

    public function testEineUnbekannteKennungFuehrtZurSuche(): void
    {
        // "Bericht-99" sieht aus wie ein Inhalt, ist aber keiner - dann war
        // der vordere Teil doch ein Suchbegriff
        $target = $this->resolve(['follow' => 'Bericht-99']);

        self::assertSame('search', $target->action);
        self::assertSame('Bericht', $target->search);
        self::assertSame(0, $target->item);
    }

    public function testDieFehlerseiteWirdAlsSolcheErkannt(): void
    {
        $target = $this->resolve(['follow' => '404']);

        self::assertTrue($target->notFound);
        // Welcher Inhalt die Fehlerseite ist, steht in der Datenbank; das
        // löst erst der Kernel auf
        self::assertSame(0, $target->item);
    }

    public function testDownloadNimmtDieKennungAusDemDateinamen(): void
    {
        $target = $this->resolve(['download' => 'Satzung-7']);

        self::assertSame('download', $target->action);
        self::assertSame(7, $target->id);
    }

    public function testAusdrueckicheAngabenGehenDemPfadVor(): void
    {
        $target = $this->resolve(['follow' => 'Satzung-7', 'item' => '12']);

        self::assertSame(12, $target->item);
    }

    public function testOhneSprechendeAdressenZaehltNurDieAbfrage(): void
    {
        $this->speakingLinks(false);

        $target = $this->resolve(['follow' => 'Satzung-7', 'cat' => '3']);

        self::assertSame(3, $target->cat);
        self::assertSame(0, $target->item, 'der Pfad darf dann nicht ausgewertet werden');
    }

    public function testDieSeitenzahlIstMindestensEins(): void
    {
        self::assertSame(1, $this->resolve([])->page);
        self::assertSame(1, $this->resolve(['page' => '0'])->page);
        self::assertSame(1, $this->resolve(['page' => '-4'])->page);
        self::assertSame(3, $this->resolve(['page' => '3'])->page);
    }

    public function testUnfugInDerAbfrageWirdZuNull(): void
    {
        $target = $this->resolve(['cat' => 'abc', 'item' => '', 'id' => '7x']);

        self::assertSame(0, $target->cat);
        self::assertSame(0, $target->item);
        self::assertSame(0, $target->id);
    }

    public function testAdressenTragenDenKennbuchstabenIhrerArt(): void
    {
        self::assertSame('/content/Satzung-7.html', Routes::item(7, 'Satzung'));
        self::assertSame('/content/Aktuelles-3c.html', Routes::cat(3, 'Aktuelles'));
        self::assertSame('/content/Termine-4s.html', Routes::subcat(4, 'Termine'));
        self::assertSame('/content/Anna-9u.html', Routes::user(9, 'Anna'));
        self::assertSame('/content/download/Satzung-7.html', Routes::download(7, 'Satzung'));
        self::assertSame('/action/guestbook.html', Routes::action('guestbook'));
    }

    public function testEinNameOhneBindestrichBleibtLesbar(): void
    {
        // Der Bindestrich trennt Name und Kennung; enthielte der Name selbst
        // einen, fände resolve() die Kennung nicht wieder
        $address = Routes::item(7, 'Jahres-Bericht 2024');

        self::assertStringNotContainsString('-Bericht', $address);
        self::assertSame(7, $this->resolve(['follow' => basename($address, '.html')])->item);
    }

    public function testOhneSprechendeAdressenBleibtDieAbfrageform(): void
    {
        $this->speakingLinks(false);

        self::assertSame('/index.php?item=7', Routes::item(7, 'Satzung'));
        self::assertSame('/index.php?cat=3', Routes::cat(3, 'Aktuelles'));
        self::assertSame('/index.php?action=user&id=9', Routes::user(9, 'Anna'));
        self::assertSame('/index.php?action=guestbook', Routes::action('guestbook'));
    }

    public function testAbmeldenBehaeltDieAbfrageform(): void
    {
        // Der Altbestand hängt an die Abmeldung weitere Parameter; ein
        // sprechender Pfad brächte dort nichts
        self::assertSame('/index.php?action=logout', Routes::action('logout'));
    }

    public function testNurBekannteAktionenGeltenAlsAktion(): void
    {
        self::assertTrue(Routes::has('guestbook'));
        self::assertFalse(Routes::has('drop_table'));
    }

    public function testDieReihenfolgeDerAktionenIstDatenhaltung(): void
    {
        // counter.php legt den Index dieser Liste ab - neue Aktionen gehören
        // ans Ende, nicht in die Mitte
        self::assertSame('download', Routes::ACTIONS[0]);
        self::assertSame('search', Routes::ACTIONS[1]);
        self::assertSame('register_finish', Routes::ACTIONS[array_key_last(Routes::ACTIONS)]);
    }
}
