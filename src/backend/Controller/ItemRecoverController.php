<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Csrf;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Frühere Fassung eines vorhandenen Inhalts aus einer Sicherung einspielen.
 */
final class ItemRecoverController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'item_recover';
    }

    #[\Override]
    public function handle(): string
    {
        $itemId = Request::queryInt('item');
        if ($itemId <= 0 || from_db('item', $itemId, 'id') === null) {
            Flash::error('Der Inhalt wurde nicht gefunden.');
            header('Location: ' . Html::url('item'));
            exit;
        }

        if (Request::string('do_recover') !== '') {
            return $this->recover($itemId, Request::queryInt('recover_id'));
        }

        return $this->chooseVersion($itemId);
    }

    /** Ersetzt den Inhalt durch die gewählte Fassung. */
    private function recover(int $itemId, int $versionIndex): string
    {
        if (!$this->checkToken()) {
            return $this->chooseVersion($itemId);
        }

        $versions = recover_item($itemId);
        if (!is_array($versions) || !isset($versions[$versionIndex][1])) {
            Flash::error('Die gewählte Fassung wurde nicht gefunden.');
            return $this->chooseVersion($itemId);
        }

        if (!Db::delete('item', $itemId)) {
            Flash::error('Fehler beim Wiederherstellen des Inhalts!');
            return $this->chooseVersion($itemId);
        }

        $statement = str_replace('\\r\\n', "\r\n", (string)$versions[$versionIndex][1]);
        if (!Db::execute($statement)) {
            Flash::error('Fehler beim Wiederherstellen des Inhalts!');
            return $this->chooseVersion($itemId);
        }

        Flash::success('Inhalt erfolgreich zurückgesetzt. <a href="index.php?item=' . $itemId . '">Inhalt anzeigen</a>');
        header('Location: ' . Html::url('item'));
        exit;
    }

    private function chooseVersion(int $itemId): string
    {
        $name = (string)from_db('item', $itemId, 'name');
        $versions = recover_item($itemId);

        $rows = [];
        foreach (is_array($versions) ? $versions : [] as $index => $version) {
            $url = Html::url('item_recover', [
                'item' => $itemId,
                'do_recover' => 'yes',
                'recover_id' => $index,
            ] + Csrf::queryParam());

            $rows[] = [
                Html::e((string)$version[0]),
                '<a href="' . Html::e($url) . '">Wiederherstellen</a>',
            ];
        }

        return Html::heading('Inhalt wiederherstellen')
            . '<p>Wählen Sie aus der Liste unten eine Backup-Version für den Inhalt "'
            . '<a href="' . Html::e(Html::url('item', ['edit' => $itemId])) . '">' . Html::e($name) . '</a>" aus.</p>'
            . '<p><strong>Achtung!</strong> Die aktuelle Version wird verworfen! Falls Sie dies nicht möchten, '
            . 'legen Sie bitte vorher ein <a href="' . Html::e(Html::url('backup')) . '">Backup</a> an.</p>'
            . Html::table(
                ['Datum - Uhrzeit', 'Wiederherstellen'],
                $rows,
                'Für diesen Inhalt liegen keine gesicherten Fassungen vor.'
            );
    }
}
