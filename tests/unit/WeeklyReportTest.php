<?php

declare(strict_types=1);

namespace Pms\Tests\Unit;

use Pms\Backend\Report\WeeklyReport;
use Pms\Data\Db;

/**
 * Report\WeeklyReport - Wochengrenzen, Fälligkeit und Inhalt des
 * Wochenberichts.
 */
final class WeeklyReportTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'CREATE TABLE pms_item (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)',
            'CREATE TABLE pms_item_views (id INTEGER PRIMARY KEY AUTOINCREMENT, item INTEGER, time INTEGER)',
            'CREATE TABLE pms_comments (id INTEGER PRIMARY KEY AUTOINCREMENT, date INTEGER)',
            'CREATE TABLE pms_user (id INTEGER PRIMARY KEY AUTOINCREMENT, register INTEGER)',
            'CREATE TABLE pms_config (id INTEGER PRIMARY KEY, visitors INTEGER, mail TEXT)',
            'CREATE TABLE pms_weekly_reports (id INTEGER PRIMARY KEY AUTOINCREMENT, period_start INTEGER,'
                . ' period_end INTEGER, created INTEGER, visitors_total INTEGER, data TEXT)',
        ] as $sql) {
            $this->connection->exec($sql);
        }
        Db::insert('config', ['id' => 1, 'visitors' => 1000, 'mail' => 'chor@example.org']);
    }

    private static function berlin(string $time): int
    {
        return (new \DateTimeImmutable($time, new \DateTimeZone('Europe/Berlin')))->getTimestamp();
    }

    public function testDieWocheBeginntMontagsUmMitternachtDeutscherZeit(): void
    {
        $montag = self::berlin('2026-09-21 00:00');

        self::assertSame($montag, WeeklyReport::weekStart(self::berlin('2026-09-21 00:00')));
        self::assertSame($montag, WeeklyReport::weekStart(self::berlin('2026-09-23 15:30')));
        // Sonntag gehört noch zur Woche davor, nicht zur nächsten
        self::assertSame($montag, WeeklyReport::weekStart(self::berlin('2026-09-27 23:59')));
    }

    public function testDieZeitumstellungVerschiebtDenWochenbeginnNicht(): void
    {
        // Umstellung auf Winterzeit am Sonntag, 25.10.2026
        self::assertSame(
            self::berlin('2026-10-26 00:00'),
            WeeklyReport::weekStart(self::berlin('2026-10-28 12:00'))
        );
        self::assertSame(
            self::berlin('2026-10-19 00:00'),
            WeeklyReport::weekStart(self::berlin('2026-10-25 12:00'))
        );
    }

    public function testFaelligErstMontagsAbAchtUhrUndNurEinmalProWoche(): void
    {
        self::assertFalse(WeeklyReport::isDue(self::berlin('2026-09-28 07:59')));
        self::assertTrue(WeeklyReport::isDue(self::berlin('2026-09-28 08:00')));
        self::assertTrue(WeeklyReport::isDue(self::berlin('2026-09-30 10:00')), 'War der Server montags aus, wird nachgeholt');

        WeeklyReport::create(self::berlin('2026-09-28 08:00'));

        self::assertFalse(WeeklyReport::isDue(self::berlin('2026-09-28 08:15')));
        self::assertFalse(WeeklyReport::isDue(self::berlin('2026-10-04 23:00')));
        self::assertTrue(WeeklyReport::isDue(self::berlin('2026-10-05 08:00')));
    }

    public function testDerBerichtZaehltNurDieVorwoche(): void
    {
        $artikel = Db::insert('item', ['name' => 'Jahreshauptversammlung']);
        $andere = Db::insert('item', ['name' => 'Chorprobe']);

        foreach (['2026-09-21 09:00', '2026-09-24 18:00', '2026-09-27 23:00'] as $zeit) {
            Db::insert('item_views', ['item' => $artikel, 'time' => self::berlin($zeit)]);
        }
        Db::insert('item_views', ['item' => $andere, 'time' => self::berlin('2026-09-25 12:00')]);
        // Vorvorwoche und laufende Woche zählen nicht mit
        Db::insert('item_views', ['item' => $andere, 'time' => self::berlin('2026-09-15 12:00')]);
        Db::insert('item_views', ['item' => $andere, 'time' => self::berlin('2026-09-28 07:00')]);
        Db::insert('comments', ['date' => self::berlin('2026-09-22 10:00')]);

        $bericht = WeeklyReport::create(self::berlin('2026-09-28 08:00'));

        self::assertSame(self::berlin('2026-09-21 00:00'), $bericht['period_start']);
        self::assertSame(4, $bericht['data']['views']);
        self::assertSame(1, $bericht['data']['views_before']);
        self::assertSame('Jahreshauptversammlung', $bericht['data']['top'][0]['name']);
        self::assertSame(3, $bericht['data']['top'][0]['views']);
        self::assertSame(1, $bericht['data']['comments']);
        self::assertNull($bericht['data']['visitors'], 'Ohne Vorbericht gibt es keinen Ausgangswert');
    }

    public function testBesucherErgebenSichAusDemAbstandZumVorigenBericht(): void
    {
        WeeklyReport::create(self::berlin('2026-09-28 08:00'));
        Db::execute('UPDATE pms_config SET visitors = 1250 WHERE id = 1');

        $bericht = WeeklyReport::create(self::berlin('2026-10-05 08:00'));

        self::assertSame(250, $bericht['data']['visitors']);
    }

    public function testEinErneuterLaufErsetztDenBerichtDerWoche(): void
    {
        WeeklyReport::create(self::berlin('2026-09-28 08:00'));
        WeeklyReport::create(self::berlin('2026-09-28 09:00'));

        self::assertCount(1, WeeklyReport::history(10));
    }

    public function testDieNachrichtNenntDasWichtigste(): void
    {
        $zusammenfassung = WeeklyReport::summary([
            'period_start' => self::berlin('2026-09-21 00:00'),
            'data' => [
                'views' => 112,
                'views_before' => 100,
                'visitors' => 40,
                'top' => [['id' => 4, 'name' => 'Jahreshauptversammlung', 'views' => 30]],
                'comments' => 2,
                'users' => 0,
            ],
        ]);

        self::assertSame('Wochenbericht KW 39', $zusammenfassung['title']);
        self::assertSame(
            '112 Aufrufe (+12 % zur Vorwoche) · 40 Besuche · Meistgelesen: Jahreshauptversammlung · 2 neue Kommentare',
            $zusammenfassung['body']
        );
    }

    public function testOhneVorwocheKeinProzentvergleich(): void
    {
        self::assertNull(WeeklyReport::change(10, 0));
        self::assertSame('−50 %', WeeklyReport::change(5, 10));
        self::assertSame('±0 %', WeeklyReport::change(10, 10));
    }
}
