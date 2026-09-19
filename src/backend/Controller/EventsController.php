<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;

/**
 * Ereignisse der Website: Anmeldungen, Registrierungen, Kommentare,
 * Gästebucheinträge und neue Inhalte in zeitlicher Reihenfolge.
 */
final class EventsController extends Controller
{
    private const RANGES = [1, 7, 14, 30];
    private const RANGE_ALL = 'Alle';

    /** Art eines Ereignisses: Beschriftung, Symbol und Farbe der Markierung. */
    private const KINDS = [
        'Benutzer' => ['icon' => 'users', 'tone' => 'accent'],
        'Kommentar' => ['icon' => 'document', 'tone' => ''],
        'Gästebuch' => ['icon' => 'document', 'tone' => 'ok'],
        'Inhalt' => ['icon' => 'plus', 'tone' => 'warn'],
    ];

    /** Ereignisse je Seite - die Liste wächst sonst unbegrenzt. */
    private const PER_PAGE = 50;

    #[\Override]
    public function action(): string
    {
        return 'events';
    }

    #[\Override]
    public function handle(): string
    {
        $range = $this->range();
        $kind = Request::string('art');
        if (!isset(self::KINDS[$kind])) {
            $kind = '';
        }

        $events = $this->collect($range);
        if ($kind !== '') {
            $events = array_values(array_filter(
                $events,
                static fn(array $event): bool => $event['type'] === $kind
            ));
        }

        $page = max(1, Request::queryInt('page', 1));
        $pages = max(1, (int)ceil(count($events) / self::PER_PAGE));
        $page = min($page, $pages);
        $shown = array_slice($events, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        return Components::pageHeader(
            'Ereignisse',
            'Anmeldungen, Registrierungen, Kommentare und neue Inhalte in zeitlicher Reihenfolge.'
        )
            . $this->filterBar($range, $kind, count($events))
            . $this->timeline($shown)
            . $this->pagination($range, $kind, $page, $pages);
    }

    /** Der gewählte Zeitraum in Tagen, oder "Alle". */
    private function range(): string
    {
        $range = Request::string('tage', '1');
        $allowed = array_map('strval', self::RANGES);
        $allowed[] = self::RANGE_ALL;

        return in_array($range, $allowed, true) ? $range : '1';
    }

    /** Zeitraum und Art als Filter, die sich selbst absenden. */
    private function filterBar(string $range, string $kind, int $total): string
    {
        $ranges = [];
        foreach (self::RANGES as $days) {
            $ranges[(string)$days] = 'Letzte ' . $days . ' Tage';
        }
        $ranges['1'] = 'Letzter Tag';
        $ranges[self::RANGE_ALL] = 'Gesamter Zeitraum';

        $kinds = ['' => 'Alle Arten'];
        foreach (array_keys(self::KINDS) as $name) {
            $kinds[$name] = $name;
        }

        return '<form class="toolbar" method="get" action="' . Html::e(Html::url($this->action())) . '" x-data>'
            . '<div class="toolbar-group">' . Icons::render('clock', 'icon icon-sm')
            . '<label class="visually-hidden" for="filter-tage">Zeitraum</label>'
            . Html::select('tage', $ranges, $range, ['id' => 'filter-tage', '@change' => '$el.form.submit()'])
            . '</div>'
            . '<div class="toolbar-group">' . Icons::render('filter', 'icon icon-sm')
            . '<label class="visually-hidden" for="filter-art">Art des Ereignisses</label>'
            . Html::select('art', $kinds, $kind, ['id' => 'filter-art', '@change' => '$el.form.submit()'])
            . '</div>'
            . '<noscript><button type="submit" class="btn-secondary">Anwenden</button></noscript>'
            . '<span class="toolbar-spacer"></span>'
            . '<span class="toolbar-group toolbar-count">'
            . Html::e($total === 1 ? '1 Ereignis' : number_format($total, 0, ',', '.') . ' Ereignisse')
            . '</span></form>';
    }

    /**
     * Sammelt alle Ereignisse des Zeitraums.
     *
     * @return list<array{type: string, time: int, text: string}>
     */
    private function collect(string $range): array
    {
        $since = $range === self::RANGE_ALL ? 0 : time() - (int)$range * 86400;
        $events = [];

        $condition = static fn(string $column): string =>
            ($since > 0 ? $column . ' > :since AND ' : '') . $column . ' != 0';
        $params = $since > 0 ? ['since' => $since] : [];

        // Anmeldungen
        foreach (Db::select('SELECT name, login FROM ' . Db::table('user') . ' WHERE ' . $condition('login') . ' ORDER BY login DESC', $params) as $row) {
            $events[] = [
                'type' => 'Benutzer',
                'time' => (int)$row->login,
                'text' => Html::e((string)$row->name) . ' hat sich eingeloggt.',
            ];
        }

        // Registrierungen
        $siteName = Html::e((string)($GLOBALS['config_values']->name ?? ''));
        foreach (Db::select('SELECT name, register FROM ' . Db::table('user') . ' WHERE ' . $condition('register') . ' ORDER BY register DESC', $params) as $row) {
            $events[] = [
                'type' => 'Benutzer',
                'time' => (int)$row->register,
                'text' => Html::e((string)$row->name) . ' hat sich auf ' . $siteName . ' registriert.',
            ];
        }

        // Kommentare und Gästebucheinträge
        $guestbookItem = (int)($GLOBALS['gb_item'] ?? 0);
        foreach (Db::select('SELECT * FROM ' . Db::table('comments') . ' WHERE ' . $condition('date') . ' ORDER BY date DESC', $params) as $row) {
            $author = $row->user ? (string)from_db('user', (int)$row->user, 'name') : (string)$row->name;
            $title = (string)$row->title !== '' ? (string)$row->title : 'Kein Titel';
            $itemName = (string)from_db('item', (int)$row->item, 'name');
            $link = make_link_mark($itemName, '', '', '', (int)$row->item, 'comments');

            $isGuestbook = $guestbookItem > 0 && (int)$row->item === $guestbookItem;
            $events[] = [
                'type' => $isGuestbook ? 'Gästebuch' : 'Kommentar',
                'time' => (int)$row->date,
                'text' => $isGuestbook
                    ? Html::e($author) . ' hat einen neuen Gästebucheintrag mit dem Titel "' . Html::e($title) . '" im ' . $link . ' verfasst.'
                    : Html::e($author) . ' hat das Kommentar mit dem Titel "' . Html::e($title) . '" zu "' . $link . '" geschrieben.',
            ];
        }

        // Neue Inhalte
        $specialTypes = $GLOBALS['special_typ'] ?? [];
        foreach (Db::select('SELECT * FROM ' . Db::table('item') . ' WHERE ' . $condition('time') . ' ORDER BY time DESC', $params) as $row) {
            $author = (string)from_db('user', (int)$row->user, 'name');
            $special = (int)$row->special;

            if ($special > 0) {
                $text = 'Dem System wurde das Spezial-Modul "'
                    . make_link($specialTypes[$special] ?? '', '', '', '', (int)$row->id) . '" hinzugefügt.';
            } else {
                $text = Html::e($author) . ' hat ein neues Inhaltsobjekt "'
                    . make_link((string)$row->name, '', '', '', (int)$row->id) . '" erstellt';
            }

            if ($row->cat || $row->subcat) {
                $text .= ' (' . Html::e((string)from_db('cat', (int)$row->cat, 'name'))
                    . ' &rarr; ' . Html::e((string)from_db('subcat', (int)$row->subcat, 'name')) . ')';
            }

            $events[] = ['type' => 'Inhalt', 'time' => (int)$row->time, 'text' => $text];
        }

        usort($events, static fn(array $a, array $b): int => $b['time'] <=> $a['time']);

        return $events;
    }

    /**
     * Die Ereignisse als Zeitstrahl.
     *
     * @param list<array{type: string, time: int, text: string}> $events
     */
    private function timeline(array $events): string
    {
        if ($events === []) {
            return Components::emptyState(
                'Keine Ereignisse',
                'In diesem Zeitraum ist nichts passiert.'
            );
        }

        $html = '<div class="card"><div class="card-body"><ol class="timeline">';
        $lastDay = '';

        foreach ($events as $event) {
            $day = Html::date($event['time']);
            if ($day !== $lastDay) {
                $html .= '<li class="timeline-day">' . Html::e($day) . '</li>';
                $lastDay = $day;
            }

            $kind = self::KINDS[$event['type']] ?? ['icon' => 'info', 'tone' => ''];
            $html .= '<li>'
                . '<div class="timeline-head">'
                . Components::chip($event['type'], $kind['tone'])
                . '<span class="timeline-time">' . Html::e(Html::date($event['time'], 'H:i')) . ' Uhr</span>'
                . '</div>'
                // Der Text enthält bereits Verlinkungen und ist maskiert
                . '<div class="timeline-text">' . $event['text'] . '</div>'
                . '</li>';
        }

        return $html . '</ol></div></div>';
    }

    /** Seitenaufteilung des Zeitstrahls. */
    private function pagination(string $range, string $kind, int $page, int $pages): string
    {
        if ($pages < 2) {
            return '';
        }

        $link = function (int $target, string $label) use ($range, $kind): string {
            $params = ['tage' => $range];
            if ($kind !== '') {
                $params['art'] = $kind;
            }
            if ($target > 1) {
                $params['page'] = $target;
            }
            return '<a href="' . Html::e($this->url($params)) . '">' . $label . '</a>';
        };

        $html = '<nav class="pagination" aria-label="Seiten">'
            . '<span class="pagination-info">Seite ' . $page . ' von ' . $pages . '</span>'
            . '<span class="pagination-pages">';

        if ($page > 1) {
            $html .= $link($page - 1, Icons::render('chevron-left', 'icon icon-sm'));
        }
        $html .= '<span class="current">' . $page . '</span>';
        if ($page < $pages) {
            $html .= $link($page + 1, Icons::render('chevron-right', 'icon icon-sm'));
        }

        return $html . '</span></nav>';
    }
}
