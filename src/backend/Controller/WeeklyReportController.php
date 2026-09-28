<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Http\Routes;
use Pms\Backend\Push\PushService;
use Pms\Backend\Report\WeeklyReport;
use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;
use Pms\Support\Auth;
use Pms\Support\Html;

/**
 * Der Wochenbericht: Push-Benachrichtigungen für dieses Gerät einschalten,
 * den zuletzt verschickten Bericht, die laufende Woche und frühere Berichte.
 *
 * Verschickt wird aus cron.php; hier wird nichts gespeichert außer dem Abo
 * eines Geräts, und das über Http\PushEndpoint.
 *
 * @psalm-import-type Report from WeeklyReport
 */
final class WeeklyReportController extends Controller
{
    private const HISTORY = 9;

    #[\Override]
    public function action(): string
    {
        return 'weekly_report';
    }

    #[\Override]
    public function handle(): string
    {
        $history = WeeklyReport::history(self::HISTORY);
        $latest = array_shift($history);

        return Components::pageHeader(
            'Wochenbericht',
            'Jeden Montag ab ' . WeeklyReport::SEND_HOUR . ' Uhr als Push-Nachricht: die Zahlen der Vorwoche.'
        )
            . $this->pushCard()
            . ($latest === null
                ? '<div class="notice notice-ok">' . Icons::render('info')
                    . '<span>Noch kein Wochenbericht verschickt - der erste kommt am nächsten Montag.</span></div>'
                : $this->reportCard($latest, 'KW ' . WeeklyReport::weekNumber($latest['period_start'])
                    . ' · ' . $this->period($latest['period_start'], $latest['period_end'] - 1)))
            . $this->reportCard(
                WeeklyReport::preview(time()),
                'Laufende Woche bis jetzt (KW ' . WeeklyReport::weekNumber(time()) . ')'
            )
            . $this->historyCard($history);
    }

    /** Ein- und Ausschalten für dieses Gerät; die Logik steht in js/admin-push.js. */
    private function pushCard(): string
    {
        $devices = PushService::countForUser(Auth::userId());

        return '<div class="card" id="push-settings"'
            . ' data-endpoint="' . Html::e(Routes::path('push_ajax')) . '"'
            . ' data-key="' . Html::e(PushService::publicKey()) . '">'
            . '<div class="card-header"><span class="card-title">' . Icons::render('bell', 'icon icon-sm')
            . ' Benachrichtigungen auf diesem Gerät</span></div>'
            . '<div class="card-body">'
            . '<p data-push-status>Wird geprüft…</p>'
            . '<div class="push-actions">'
            . '<button type="button" data-push-enable style="display:none">Benachrichtigungen aktivieren</button>'
            . '<button type="button" class="btn-secondary" data-push-test style="display:none">Testnachricht senden</button>'
            . '<button type="button" class="btn-secondary" data-push-disable style="display:none">Auf diesem Gerät abschalten</button>'
            . '</div>'
            . '<p class="field-hint">Für Ihr Konto ' . ($devices === 1 ? 'ist 1 Gerät' : 'sind ' . $devices . ' Geräte')
            . ' angemeldet. Auf dem iPhone funktioniert das nur, wenn das Backend als App auf dem Home-Bildschirm liegt.</p>'
            . '</div></div>'
            . '<script type="text/javascript" src="js/admin-push.js"></script>';
    }

    /** @param Report $report */
    private function reportCard(array $report, string $title): string
    {
        $data = $report['data'];
        $views = (int)($data['views'] ?? 0);
        $change = WeeklyReport::change($views, (int)($data['views_before'] ?? 0));
        $visitors = $data['visitors'] ?? null;

        $cards = [
            ['Aufrufe', number_format($views, 0, ',', '.'), $change === null ? 'kein Vergleich möglich' : $change . ' zur Vorwoche'],
            ['Besuche', $visitors === null ? '–' : number_format((int)$visitors, 0, ',', '.'), $visitors === null ? ($report['id'] === 0 ? 'erst im fertigen Bericht' : 'ab dem zweiten Bericht') : 'in dieser Woche'],
            ['Neue Kommentare', (string)(int)($data['comments'] ?? 0), ''],
            ['Neue Benutzer', (string)(int)($data['users'] ?? 0), ''],
        ];

        $stats = '<div class="stat-grid report-stats">';
        foreach ($cards as [$label, $value, $hint]) {
            $stats .= '<div class="stat">'
                . '<span class="stat-label">' . Html::e($label) . '</span>'
                . '<span class="stat-value">' . Html::e($value) . '</span>'
                . '<span class="stat-hint">' . Html::e($hint) . '</span>'
                . '</div>';
        }
        $stats .= '</div>';

        $top = is_array($data['top'] ?? null) ? $data['top'] : [];
        $list = $top === []
            ? '<p class="field-hint">Keine Aufrufe von Inhalten gezählt.</p>'
            : '<ol class="report-top">';
        foreach ($top as $entry) {
            $list .= '<li><a href="' . Html::e(Html::url('item', ['edit' => (int)$entry['id']])) . '">'
                . Html::e((string)$entry['name']) . '</a> <span class="field-hint">'
                . (int)$entry['views'] . '× aufgerufen</span></li>';
        }
        if ($top !== []) {
            $list .= '</ol>';
        }

        return '<div class="card">'
            . '<div class="card-header"><span class="card-title">' . Html::e($title) . '</span></div>'
            . '<div class="card-body">' . $stats
            . '<h4 class="report-subtitle">Meistgelesen</h4>' . $list
            . '</div></div>';
    }

    /** @param list<Report> $history */
    private function historyCard(array $history): string
    {
        if ($history === []) {
            return '';
        }

        $rows = [];
        foreach ($history as $report) {
            $data = $report['data'];
            $top = is_array($data['top'] ?? null) && isset($data['top'][0]['name']) ? (string)$data['top'][0]['name'] : '–';
            $rows[] = [
                Html::e('KW ' . WeeklyReport::weekNumber($report['period_start'])),
                Html::e($this->period($report['period_start'], $report['period_end'] - 1)),
                Html::e(number_format((int)($data['views'] ?? 0), 0, ',', '.')),
                Html::e(isset($data['visitors']) ? number_format((int)$data['visitors'], 0, ',', '.') : '–'),
                Html::e($top),
            ];
        }

        return '<div class="card"><div class="card-header"><span class="card-title">Frühere Berichte</span></div>'
            . '<div class="card-body">'
            . Html::table(['Woche', 'Zeitraum', 'Aufrufe', 'Besuche', 'Meistgelesen'], $rows)
            . '</div></div>';
    }

    private function period(int $start, int $end): string
    {
        return WeeklyReport::date($start, 'd.m.') . '–' . WeeklyReport::date($end, 'd.m.Y');
    }
}
