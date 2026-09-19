<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Data\Db;
use Pms\Support\Html;
use Pms\Support\Listing;
use Pms\Support\Request;
use Pms\Support\UserAgent;

/**
 * Website-Status: Kennzahlen und die letzten Zugriffe.
 *
 * Der Altbestand lud jeden Zugriff der letzten 24 Stunden und gab die rohen
 * Browser-Kennungen aus - bei einer belebten Website mehrere hundert Zeilen.
 * Die Liste ist jetzt durchsuchbar und seitenweise; die Kennungen werden
 * lesbar gemacht.
 */
final class ActivityController extends Controller
{
    /** Kennungen, die auf eine Suchmaschine deuten - für die Abfrage. */
    private const BOT_PATTERNS = ['%bot%', '%crawler%', '%spider%', '%slurp%', '%monitor%'];

    #[\Override]
    public function action(): string
    {
        return 'activity';
    }

    #[\Override]
    public function handle(): string
    {
        $hideBots = Request::queryInt('bots', 1) === 1;

        $list = Listing::from('visitors_counter')
            ->searchIn(['id', 'browser'])
            ->sortableBy(['id' => 'id', 'time' => 'time'])
            ->orderedBy('time DESC')
            ->keep('bots', $hideBots ? '' : '0');

        if ($hideBots) {
            foreach (self::BOT_PATTERNS as $index => $pattern) {
                $list->where('LOWER(browser) NOT LIKE :bot' . $index, ['bot' . $index => $pattern]);
            }
        }

        $list->load();

        return Components::pageHeader(
            'Website-Status',
            'Kennzahlen der Website und die zuletzt aufgezeichneten Zugriffe.',
            Components::secondary('Aktualisieren', $this->url($list->params()), 'refresh')
        )
            . $this->statistics()
            . Components::toolbar($this->action(), $list, [[
                'name' => 'bots',
                'label' => 'Suchmaschinen',
                'options' => [1 => 'Suchmaschinen ausblenden', 0 => 'Suchmaschinen anzeigen'],
                'value' => $hideBots ? 1 : 0,
            ]], 'IP oder Browser suchen')
            . $this->visitorTable($list)
            . Components::pagination($list, $this->action());
    }

    /** Die vier Besucherzahlen als Karten. */
    private function statistics(): string
    {
        $config = $GLOBALS['config_values'] ?? null;
        $lifetime = (int)($config->visitors_lifetime ?? 15);
        $online = Db::count('visitors_counter', 'time >= :since', ['since' => time() - 60 * $lifetime]);

        $cards = [
            ['Online', $online, 'In den letzten ' . $lifetime . ' Minuten aktiv', 'pulse'],
            ['Heute', (int)($config->visitors_today ?? 0), 'Besucher seit Mitternacht', 'globe'],
            ['Gestern', (int)($config->visitors_yesterday ?? 0), 'Besucher am Vortag', 'clock'],
            ['Gesamt', (int)($GLOBALS['number_visitors'] ?? 0), 'Seit Bestehen der Website', 'users'],
        ];

        $html = '<div class="stat-grid">';
        foreach ($cards as [$label, $value, $hint, $icon]) {
            $html .= '<div class="stat">'
                . '<div class="stat-label">' . \Pms\Backend\View\Icons::render($icon, 'icon icon-sm')
                . Html::e($label) . '</div>'
                . '<div class="stat-value">' . Html::e(number_format($value, 0, ',', '.')) . '</div>'
                . '<div class="stat-hint">' . Html::e($hint) . '</div>'
                . '</div>';
        }

        return $html . '</div>';
    }

    private function visitorTable(Listing $list): string
    {
        $rows = [];
        foreach ($list->rows as $visitor) {
            $user = 'Gast';
            if ($visitor->user) {
                $user = (string)make_link(
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

            $agent = (string)$visitor->browser;
            $rows[] = [
                '<code>' . Html::e((string)$visitor->id) . '</code>',
                // Die rohe Kennung bleibt als Tooltip erreichbar
                '<span title="' . Html::e($agent) . '">' . Html::e(UserAgent::describe($agent)) . '</span>'
                . (UserAgent::isBot($agent) ? ' ' . Components::chip('Suchmaschine', 'warn') : ''),
                $user,
                'vor ' . Html::e((string)time_diff($visitor->time)),
                // convert_action liefert bereits fertiges HTML mit Verlinkung
                (string)convert_action($visitor->typ, $visitor->content),
            ];
        }

        return Components::table(
            [
                ['key' => 'id', 'label' => 'Kennung', 'class' => 'cell-id'],
                ['label' => 'Browser', 'class' => 'cell-title'],
                ['label' => 'Benutzer'],
                ['key' => 'time', 'label' => 'Letzte Aktivität'],
                ['label' => 'Letzte Aktion'],
            ],
            $rows,
            $this->action(),
            $list,
            $list->isFiltered()
                ? 'Kein Zugriff passt zur Suche.'
                : 'Es wurden noch keine Zugriffe aufgezeichnet.'
        );
    }
}
