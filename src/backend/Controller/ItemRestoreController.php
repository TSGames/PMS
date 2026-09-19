<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Data\Db;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

/**
 * Wiederherstellung gelöschter Inhalte aus den Sicherungen.
 *
 * Schritt 1: Inhalt auswählen, Schritt 2: Zeitpunkt auswählen.
 */
final class ItemRestoreController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'item_restore';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('do_restore')) {
            return $this->restore();
        }

        if (Request::submitted('item_restore')) {
            return $this->chooseDate(Request::int('item_select'));
        }

        return $this->chooseItem();
    }

    /** Spielt die gewählte Sicherung ein. */
    private function restore(): string
    {
        if (!$this->checkToken()) {
            return $this->chooseItem();
        }

        $itemId = Request::int('item_select');
        $index = Request::int('date_select');
        $versions = recover_item($itemId, 0);

        if (!is_array($versions) || !isset($versions[$index][1])) {
            Flash::error('Die gewählte Sicherung wurde nicht gefunden.');
            return $this->chooseItem();
        }

        $name = (string)($versions[0][3] ?? 'Unbekannt');
        $statement = str_replace('\\r\\n', "\r\n", (string)$versions[$index][1]);

        if (!Db::execute($statement)) {
            Flash::error('Fehler beim Wiederherstellen des Inhalts (ID: ' . $itemId . ', Name: ' . Html::e($name) . ')');
            return $this->chooseItem();
        }

        Flash::success('Inhalt erfolgreich wiederhergestellt (ID: ' . $itemId . ', Name: ' . Html::e($name) . ')');
        header('Location: ' . Html::url('item'));
        exit;
    }

    /** Schritt 1: Auswahl des gelöschten Inhalts. */
    private function chooseItem(): string
    {
        $existing = array_map(
            static fn(object $row): int => (int)$row->id,
            Db::select('SELECT id FROM ' . Db::table('item') . ' ORDER BY id')
        );

        $found = recover_item(0, 1, $existing);

        $header = Components::pageHeader(
            'Gelöschten Inhalt wiederherstellen',
            'Inhalte, die es in den Sicherungen noch gibt, in der Website aber nicht mehr.',
            Components::secondary('Zurück zur Übersicht', Html::url('item'), 'chevron-left')
        );

        if (!is_array($found) || $found === []) {
            return $header . Components::emptyState(
                'Nichts zum Wiederherstellen',
                'In den Sicherungen findet sich kein Inhalt, der gelöscht wurde.'
            );
        }

        $options = [];
        foreach ($found as $entry) {
            $id = (int)$entry[2];
            if (!isset($options[$id])) {
                $options[$id] = (string)$entry[3] . ' (ID ' . $id . ')';
            }
        }

        return $header
            . Html::formOpen($this->action())
            . Form::card(
                Form::section('Schritt 1 von 2', Form::field(
                    'Gelöschter Inhalt',
                    Html::select('item_select', $options, null, ['id' => 'item_select']),
                    ['name' => 'item_select', 'hint' => 'Danach wählen Sie den Zeitpunkt der Sicherung.']
                )),
                Form::actions('item_restore', 'Weiter', Html::url('item'))
            )
            . Html::formClose();
    }

    /** Schritt 2: Auswahl des Zeitpunkts. */
    private function chooseDate(int $itemId): string
    {
        $versions = recover_item($itemId, 0);

        $header = Components::pageHeader('Gelöschten Inhalt wiederherstellen');

        if (!is_array($versions) || $versions === []) {
            return $header . Components::emptyState(
                'Keine Sicherung gefunden',
                'Zu diesem Inhalt liegt keine Fassung vor.'
            );
        }

        $options = [];
        foreach ($versions as $index => $version) {
            $options[$index] = (string)$version[0];
        }

        $name = (string)$versions[0][3];

        return Components::pageHeader(
            'Gelöschten Inhalt wiederherstellen',
            'Aus welcher Sicherung soll "' . $name . '" (ID ' . (int)$versions[0][2] . ') zurückkommen?'
        )
            . Html::formOpen($this->action())
            . Html::hidden('item_select', $itemId)
            . Form::card(
                Form::section('Schritt 2 von 2', Form::field(
                    'Zeitpunkt der Sicherung',
                    Html::select('date_select', $options, null, ['id' => 'date_select']),
                    ['name' => 'date_select', 'hint' => 'Die neueste Fassung steht oben.']
                )),
                Form::actions('do_restore', 'Wiederherstellen', $this->url())
            )
            . Html::formClose();
    }
}
