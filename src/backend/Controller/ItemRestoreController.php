<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

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

        $html = Html::heading('Gelöschten Inhalt wiederherstellen');
        if (!is_array($found) || $found === []) {
            return $html . '<p>In den Backups wurden keine Inhalte gefunden, welche gelöscht wurden!</p>';
        }

        $options = [];
        foreach ($found as $entry) {
            $id = (int)$entry[2];
            if (!isset($options[$id])) {
                $options[$id] = '(ID: ' . $id . ') ' . (string)$entry[3];
            }
        }

        return $html
            . '<p>Bitte wählen Sie aus der folgenden Liste das Inhaltsobjekt:</p>'
            . Html::formOpen($this->action())
            . Html::select('item_select', $options)
            . ' <input type="submit" name="item_restore" value="Weiter">'
            . Html::formClose();
    }

    /** Schritt 2: Auswahl des Zeitpunkts. */
    private function chooseDate(int $itemId): string
    {
        $versions = recover_item($itemId, 0);

        $html = Html::heading('Gelöschten Inhalt wiederherstellen');
        if (!is_array($versions) || $versions === []) {
            return $html . '<p>Es ist ein Fehler aufgetreten.</p>';
        }

        $options = [];
        foreach ($versions as $index => $version) {
            $options[$index] = (string)$version[0];
        }

        return $html
            . '<p>Bitte wählen Sie ein Datum aus, um das Inhaltsobjekt <strong>'
            . Html::e((string)$versions[0][3]) . '</strong> (ID: ' . (int)$versions[0][2]
            . ') wiederherzustellen:</p>'
            . Html::formOpen($this->action())
            . Html::hidden('item_select', $itemId)
            . Html::hidden('do_restore', 1)
            . Html::select('date_select', $options)
            . ' <input type="submit" name="do_restore" value="Wiederherstellen">'
            . Html::formClose();
    }
}
