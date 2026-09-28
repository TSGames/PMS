<?php

namespace Pms\Backend\Report;

use Pms\Data\Db;

/**
 * Der Wochenbericht: die Zahlen der vergangenen Kalenderwoche.
 *
 * Eine Woche läuft von Montag 00:00 bis zum nächsten Montag 00:00 deutscher
 * Zeit. cron.php prüft regelmäßig isDue() und verschickt den Bericht ab
 * SEND_HOUR per Push (Push\PushService); die Backend-Seite "Wochenbericht"
 * zeigt ihn und frühere Berichte an.
 *
 * @psalm-type Report = array{id: int, period_start: int, period_end: int, created: int, data: array<string, mixed>}
 */
final class WeeklyReport
{
    public const TIMEZONE = 'Europe/Berlin';

    /** Ab dieser Stunde am Montag wird verschickt. */
    public const SEND_HOUR = 8;

    private const TOP_ITEMS = 3;

    /** Montag 00:00 der Woche, in der $time liegt. */
    public static function weekStart(int $time): int
    {
        $date = self::local($time)->setTime(0, 0);
        $daysSinceMonday = (int)$date->format('N') - 1;

        return $date->modify('-' . $daysSinceMonday . ' days')->getTimestamp();
    }

    /** Ist der Bericht für die gerade abgeschlossene Woche fällig? */
    public static function isDue(int $now): bool
    {
        $monday = self::weekStart($now);
        $sendAt = self::local($monday)->setTime(self::SEND_HOUR, 0)->getTimestamp();

        if ($now < $sendAt) {
            return false;
        }

        return Db::count('weekly_reports', 'period_end >= :monday', ['monday' => $monday]) === 0;
    }

    /**
     * Erstellt und speichert den Bericht für die Woche vor der, in der $now
     * liegt.
     *
     * @return Report
     */
    public static function create(int $now): array
    {
        $end = self::weekStart($now);
        $start = self::weekStart($end - 1);

        $data = self::collect($start, $end);
        $total = self::visitorsTotal();

        // Besucher gibt es nur als laufende Summe. Die Woche ergibt sich aus
        // dem Unterschied zum Stand beim letzten Bericht - aber nur, wenn
        // der genau an diese Woche anschließt.
        $previous = Db::first(
            'SELECT visitors_total FROM ' . Db::table('weekly_reports')
            . ' WHERE period_end = :start ORDER BY id DESC LIMIT 1',
            ['start' => $start]
        );
        $data['visitors'] = $previous === null ? null : max(0, $total - (int)$previous->visitors_total);

        // Ein erneuter Lauf (cron.php --force) ersetzt den Bericht dieser Woche
        Db::execute('DELETE FROM ' . Db::table('weekly_reports') . ' WHERE period_end = :end', ['end' => $end]);

        $id = Db::insert('weekly_reports', [
            'period_start' => $start,
            'period_end' => $end,
            'created' => $now,
            'visitors_total' => $total,
            'data' => (string)json_encode($data),
        ]);

        return ['id' => $id, 'period_start' => $start, 'period_end' => $end, 'created' => $now, 'data' => $data];
    }

    /**
     * Die laufende Woche bis jetzt - für die Vorschau und die Testnachricht.
     *
     * @return Report
     */
    public static function preview(int $now): array
    {
        $start = self::weekStart($now);
        $data = self::collect($start, $now);
        $data['visitors'] = null;

        return ['id' => 0, 'period_start' => $start, 'period_end' => $now, 'created' => $now, 'data' => $data];
    }

    /**
     * Die zuletzt verschickten Berichte, der neueste zuerst.
     *
     * @return list<Report>
     */
    public static function history(int $limit): array
    {
        $rows = Db::select(
            'SELECT * FROM ' . Db::table('weekly_reports') . ' ORDER BY period_end DESC, id DESC LIMIT :limit',
            ['limit' => $limit]
        );

        return array_map(static fn(object $row): array => [
            'id' => (int)$row->id,
            'period_start' => (int)$row->period_start,
            'period_end' => (int)$row->period_end,
            'created' => (int)$row->created,
            'data' => (array)json_decode((string)$row->data, true),
        ], $rows);
    }

    /**
     * Titel und Text der Push-Nachricht.
     *
     * @param Report $report
     * @return array{title: string, body: string}
     */
    public static function summary(array $report): array
    {
        $data = $report['data'];
        $views = (int)($data['views'] ?? 0);
        $parts = [];

        $viewsText = $views === 1 ? '1 Aufruf' : number_format($views, 0, ',', '.') . ' Aufrufe';
        $change = self::change($views, (int)($data['views_before'] ?? 0));
        $parts[] = $viewsText . ($change === null ? '' : ' (' . $change . ' zur Vorwoche)');

        if (isset($data['visitors']) && $data['visitors'] !== null) {
            $visitors = (int)$data['visitors'];
            $parts[] = $visitors === 1 ? '1 Besuch' : number_format($visitors, 0, ',', '.') . ' Besuche';
        }

        $top = $data['top'] ?? [];
        if (is_array($top) && isset($top[0]['name'])) {
            $parts[] = 'Meistgelesen: ' . $top[0]['name'];
        }

        $comments = (int)($data['comments'] ?? 0);
        if ($comments > 0) {
            $parts[] = $comments === 1 ? '1 neuer Kommentar' : $comments . ' neue Kommentare';
        }

        $users = (int)($data['users'] ?? 0);
        if ($users > 0) {
            $parts[] = $users === 1 ? '1 neuer Benutzer' : $users . ' neue Benutzer';
        }

        return [
            'title' => 'Wochenbericht KW ' . self::weekNumber($report['period_start']),
            'body' => implode(' · ', $parts),
        ];
    }

    /** Kalenderwoche (ISO) zu einem Zeitpunkt. */
    public static function weekNumber(int $time): string
    {
        return self::local($time)->format('W');
    }

    /**
     * Datum in deutscher Zeit - unabhängig von der Zeitzone des Servers,
     * die im Container UTC ist.
     */
    public static function date(int $time, string $format = 'd.m.Y'): string
    {
        return self::local($time)->format($format);
    }

    /** Veränderung in Prozent, z.B. "+12 %"; ohne Vorwoche keine Angabe. */
    public static function change(int $now, int $before): ?string
    {
        if ($before <= 0) {
            return null;
        }
        $percent = (int)round(($now - $before) / $before * 100);

        return ($percent > 0 ? '+' : ($percent < 0 ? '−' : '±')) . abs($percent) . ' %';
    }

    /** @return array<string, mixed> */
    private static function collect(int $start, int $end): array
    {
        $views = Db::table('item_views');
        $range = ['start' => $start, 'end' => $end];
        $length = $end - $start;

        $top = Db::select(
            'SELECT v.item AS id, i.name AS name, COUNT(*) AS views FROM ' . $views . ' v'
            . ' JOIN ' . Db::table('item') . ' i ON i.id = v.item'
            . ' WHERE v.time >= :start AND v.time < :end'
            . ' GROUP BY v.item ORDER BY views DESC, v.item LIMIT :limit',
            $range + ['limit' => self::TOP_ITEMS]
        );

        return [
            'views' => Db::count('item_views', 'time >= :start AND time < :end', $range),
            // Gleich lange Spanne davor, damit auch die Vorschau einer
            // angefangenen Woche fair vergleicht
            'views_before' => Db::count('item_views', 'time >= :start AND time < :end', [
                'start' => $start - $length,
                'end' => $start,
            ]),
            'top' => array_map(static fn(object $row): array => [
                'id' => (int)$row->id,
                'name' => (string)$row->name !== '' ? (string)$row->name : '(ohne Titel)',
                'views' => (int)$row->views,
            ], $top),
            'comments' => Db::count('comments', 'date >= :start AND date < :end', $range),
            'users' => Db::count('user', 'register >= :start AND register < :end', $range),
        ];
    }

    private static function visitorsTotal(): int
    {
        return (int)Db::value('SELECT visitors FROM ' . Db::table('config') . ' WHERE id = 1');
    }

    private static function local(int $time): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $time))->setTimezone(new \DateTimeZone(self::TIMEZONE));
    }
}
