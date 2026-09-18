<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Website-Status: aktuelle Besucher und deren letzte Aktionen.
 */
final class ActivityController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'activity';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('send_bot_filter')) {
            $_SESSION['filter_bot'] = Request::checkbox('filter_bot');
        }

        $filterBots = !empty($_SESSION['filter_bot']);
        $stats = get_24_stats($filterBots);

        return $this->summary(count($stats), $filterBots) . $this->visitorTable($stats);
    }

    private function summary(int $accessCount, bool $filterBots): string
    {
        $config = $GLOBALS['config_values'] ?? null;
        $lifetime = (int)($config->visitors_lifetime ?? 15);
        $onlineSince = time() - 60 * $lifetime;

        $online = Db::count('visitors_counter', 'time >= :since', ['since' => $onlineSince]);

        $rows = [
            ['Besucher Online', (string)$online],
            ['Besucher Heute', (string)(int)($config->visitors_today ?? 0)],
            ['Besucher Gestern', (string)(int)($config->visitors_yesterday ?? 0)],
            ['Besucher Gesamt', (string)(int)($GLOBALS['number_visitors'] ?? 0)],
        ];

        $sentence = $accessCount === 1
            ? 'Innerhalb der letzten 24 Stunden war 1 Zugriff'
            : 'Innerhalb der letzten 24 Stunden waren ' . $accessCount . ' Zugriffe';

        return Html::heading('Website-Status')
            . '<p>Sie haben auf dieser Seite die Möglichkeit, alle aktuellen Benutzer- und '
            . 'Besucher-Aktivitäten einzusehen.</p>'
            . Html::table(['Kennzahl', 'Wert'], array_map(
                static fn(array $row): array => [Html::e($row[0]), Html::e($row[1])],
                $rows
            ))
            . '<p>' . Html::e($sentence) . '</p>'
            . '<div class="action-section">' . Html::button('Aktualisieren', $this->url()) . '</div>'
            . Html::formOpen($this->action())
            . '<div class="action-section">'
            . Html::checkbox('filter_bot', $filterBots, 'Suchmaschinen filtern')
            . ' <input type="submit" name="send_bot_filter" value="Speichern">'
            . '</div>'
            . Html::formClose();
    }

    /** @param list<object> $stats */
    private function visitorTable(array $stats): string
    {
        $rows = [];
        foreach ($stats as $visitor) {
            $user = 'Keiner / Gast';
            if ($visitor->user) {
                $user = make_link(
                    (string)from_db('user', (int)$visitor->user, 'name'),
                    'action=user&id=' . (int)$visitor->user,
                    0,
                    0,
                    0,
                    '',
                    0,
                    '_blank'
                );
            }

            $rows[] = [
                Html::e((string)$visitor->id),
                Html::e(browser($visitor->browser)),
                $user,
                'Vor ' . Html::e(time_diff($visitor->time)),
                Html::e(convert_action($visitor->typ, $visitor->content)),
            ];
        }

        return Html::table(
            ['IP', 'Browser', 'Benutzer', 'Letzte Aktivität', 'Typ der letzten Aktion'],
            $rows,
            'In den letzten 24 Stunden wurden keine Zugriffe aufgezeichnet.'
        );
    }
}
