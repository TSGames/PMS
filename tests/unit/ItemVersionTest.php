<?php

namespace Pms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pms\Support\ItemVersion;

/**
 * Die Sicherung ist eine INSERT-Anweisung; hier wird sie wieder lesbar.
 */
final class ItemVersionTest extends TestCase
{
    private const INSERT = "INSERT INTO pms_item (id,name,description,content,sort) "
        . "VALUES ('5','Probenplan','Kurz, mit Komma','Zeile eins\\nZeile zwei','10');";

    public function testLiestFeldnamenUndWerte(): void
    {
        $values = ItemVersion::values(self::INSERT);

        $this->assertSame('5', $values['id']);
        $this->assertSame('Probenplan', $values['name']);
        $this->assertSame('Kurz, mit Komma', $values['description'], 'Ein Komma im Wert trennt nicht');
        $this->assertSame("Zeile eins\nZeile zwei", $values['content']);
        $this->assertSame('10', $values['sort']);
    }

    public function testEinHochkommaImTextIstVerdoppelt(): void
    {
        $insert = "INSERT INTO pms_item (id,name) VALUES ('1','Heut'' ist Probe');";

        $this->assertSame("Heut' ist Probe", ItemVersion::values($insert)['name']);
    }

    public function testEineAnweisungOhneWerteLiefertNichts(): void
    {
        $this->assertSame([], ItemVersion::values('DELETE FROM pms_item;'));
    }

    public function testNennnUrUnterschiedeMitBeschriftung(): void
    {
        $current = (object)['id' => 5, 'name' => 'Probenplan', 'description' => 'Neu', 'content' => "Zeile eins\nZeile zwei", 'sort' => 10];

        $differences = ItemVersion::differences(ItemVersion::values(self::INSERT), $current);

        $this->assertCount(1, $differences);
        $this->assertSame('description', $differences[0]['field']);
        $this->assertSame('Kurzbeschreibung', $differences[0]['label']);
        $this->assertSame('Neu', $differences[0]['now']);
    }

    public function testGleicheZeilenendenSindKeinUnterschied(): void
    {
        $insert = "INSERT INTO pms_item (id,content) VALUES ('5','a\\r\\nb');";
        $current = (object)['id' => 5, 'content' => "a\nb"];

        $this->assertSame([], ItemVersion::differences(ItemVersion::values($insert), $current));
    }

    public function testZeilenvergleichKennzeichnetWegUndDazu(): void
    {
        $diff = ItemVersion::lineDiff("eins\nzwei\ndrei", "eins\nZWEI\ndrei");

        $this->assertContains(['-', 'zwei'], $diff);
        $this->assertContains(['+', 'ZWEI'], $diff);
        $this->assertContains([' ', 'eins'], $diff);
    }

    public function testLangeGleicheStreckenWerdenGekuerzt(): void
    {
        $lines = array_map(static fn(int $i): string => 'Zeile ' . $i, range(1, 40));
        $changed = $lines;
        $changed[20] = 'geändert';

        $diff = ItemVersion::lineDiff(implode("\n", $lines), implode("\n", $changed));

        $this->assertLessThan(count($lines), count($diff));
        $this->assertContains(['…', ''], $diff);
    }
}
