<?php

namespace Pms\Data;

use Pms\Support\Html;

/**
 * Die Ereignisse der Website in zeitlicher Reihenfolge.
 *
 * Anmeldungen, Registrierungen, Kommentare, Gästebucheinträge und neue
 * Inhalte liegen in vier Tabellen. Diese Klasse führt sie zusammen - für die
 * Ereignisseite und für die Startseite, die nur die jüngsten zeigt.
 */
final class EventFeed
{
    /** Kennzeichnung "alle Ereignisse" statt einer Zahl von Tagen. */
    public const RANGE_ALL = 'Alle';

    /**
     * Sammelt alle Ereignisse des Zeitraums.
     *
     * @param string $range Zahl der Tage oder RANGE_ALL
     * @return list<array{type: string, time: int, text: string}>
     */
    public static function collect(string $range = self::RANGE_ALL): array
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
}
