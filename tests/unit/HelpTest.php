<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Support\Help;
use PHPUnit\Framework\TestCase;

/**
 * Support\Help - die Erklärungen zu den Feldern.
 *
 * Der wichtigste Test ist der letzte: Er hält jeden Verweis aus den
 * Controllern gegen den Bestand. Ohne ihn fiele ein Tippfehler erst im
 * Browser auf - und dort still, weil ein unbekannter Verweis einfach
 * keine Hilfe anzeigt.
 */
final class HelpTest extends TestCase
{
    private const SRC = __DIR__ . '/../../src';

    protected function tearDown(): void
    {
        Help::useDirectory(null);
    }

    public function testEinTextWirdInTitelUndKoerperZerlegt(): void
    {
        $text = Help::for('config', 'visitors_lifetime');

        self::assertNotNull($text);
        self::assertSame('Zeit (Minuten), die ein Besucher als "Online" gilt', $text['title']);
        self::assertStringContainsString('10 - 20 Minuten', $text['body']);
    }

    public function testEinUnbekanntesFeldHatKeinenText(): void
    {
        self::assertNull(Help::for('config', 'gibt_es_nicht'));
        self::assertFalse(Help::has('config/gibt_es_nicht'));
    }

    public function testEinVerweisKannNichtAusDemVerzeichnisFuehren(): void
    {
        // Der Verweis kommt zwar aus dem eigenen Code und nicht von außen -
        // trotzdem soll er nirgendwo anders hinzeigen können
        self::assertNull(Help::load('../../composer'));
        self::assertNull(Help::load('config/../../../etc/passwd'));
        self::assertNull(Help::load('config'));
    }

    public function testAlleTexteHabenTitelUndInhalt(): void
    {
        $verweise = Help::all();
        self::assertGreaterThan(50, count($verweise), 'es sollten alle übernommenen Texte da sein');

        foreach ($verweise as $verweis) {
            $text = Help::load($verweis);
            self::assertNotNull($text, $verweis);
            self::assertNotSame('', $text['title'], $verweis . ': Titel fehlt');
            self::assertNotSame('', $text['body'], $verweis . ': Text fehlt');
        }
    }

    public function testDieTexteSindUtf8(): void
    {
        foreach (Help::all() as $verweis) {
            $inhalt = (string)file_get_contents(self::SRC . '/help/' . $verweis . '.md');
            self::assertTrue(
                mb_check_encoding($inhalt, 'UTF-8'),
                $verweis . ' ist nicht UTF-8'
            );
        }
    }

    public function testJederVerweisAusDenControllernHatEinenText(): void
    {
        $fehlend = [];

        foreach (glob(self::SRC . '/backend/Controller/*.php') ?: [] as $datei) {
            $quelltext = (string)file_get_contents($datei);
            preg_match_all("/'help' => '([a-z0-9_]+\/[a-z0-9_]+)'/", $quelltext, $treffer);

            foreach ($treffer[1] as $verweis) {
                if (!Help::has($verweis)) {
                    $fehlend[] = basename($datei) . ' => ' . $verweis;
                }
            }
        }

        self::assertSame([], $fehlend, 'Verweise ohne Textdatei');
    }

    public function testDerKonfiguratorLeitetSeineVerweiseAusDemFeldnamenAb(): void
    {
        // ConfigController setzt 'help' => 'config/' . $field. Ein Feld ohne
        // Text bleibt dann still ohne Erklärung - deshalb wird hier geprüft,
        // dass zu jedem Feld einer da ist.
        $quelltext = (string)file_get_contents(self::SRC . '/backend/Controller/ConfigController.php');

        preg_match("/private const FLAGS = \[(.*?)\];/s", $quelltext, $flags);
        preg_match("/private const TEXTS = \[(.*?)\];/s", $quelltext, $texts);
        preg_match("/private const NUMBERS = \[(.*?)\];/s", $quelltext, $numbers);

        $felder = array_merge(
            self::namesIn($flags[1] ?? ''),
            self::namesIn($texts[1] ?? ''),
            self::namesIn($numbers[1] ?? '')
        );

        self::assertGreaterThan(25, count($felder), 'die Felder sollten gefunden werden');

        $ohneText = array_values(array_filter(
            $felder,
            static fn(string $feld): bool => !Help::has('config/' . $feld)
        ));

        self::assertSame([], $ohneText, 'Einstellungen ohne Erklärung');
    }

    /** @return list<string> */
    private static function namesIn(string $block): array
    {
        preg_match_all("/'([a-z_]+)'/", $block, $treffer);
        return $treffer[1];
    }
}
