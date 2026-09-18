<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Ereignisse der Website: Anmeldungen, Registrierungen, Kommentare,
 * Gästebucheinträge und neue Inhalte in zeitlicher Reihenfolge.
 */
final class EventsController extends Controller
{
    private const RANGES = [1, 7, 14, 30];
    private const RANGE_ALL = 'Alle';

    #[\Override]
    public function action(): string
    {
        return 'events';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('events')) {
            $_SESSION['last_events'] = Request::string('last_events', '1');
        }

        $range = (string)($_SESSION['last_events'] ?? '1');
        if ($range === '') {
            $range = '1';
            $_SESSION['last_events'] = $range;
        }

        return $this->filterForm($range) . $this->eventTable($this->collect($range));
    }

    /** Auswahl des Zeitraums. */
    private function filterForm(string $range): string
    {
        $options = [];
        foreach (self::RANGES as $days) {
            $options[(string)$days] = (string)$days;
        }
        $options[self::RANGE_ALL] = self::RANGE_ALL;

        return Html::heading('Ereignisse')
            . Html::formOpen($this->action())
            . 'Es werden die Ereignisse der letzten '
            . Html::select('last_events', $options, $range)
            . ' Tag(e) angezeigt. <input type="submit" name="events" value="OK">'
            . Html::formClose();
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

    /** @param list<array{type: string, time: int, text: string}> $events */
    private function eventTable(array $events): string
    {
        $rows = [];
        foreach ($events as $event) {
            $rows[] = [
                Html::e($event['type']),
                Html::date($event['time']),
                Html::date($event['time'], 'H:i'),
                $event['text'],
            ];
        }

        return Html::table(
            ['Ereignisstyp', 'Datum', 'Uhrzeit', 'Beschreibung'],
            $rows,
            'In diesem Zeitraum sind keine Ereignisse aufgetreten.'
        );
    }
}
