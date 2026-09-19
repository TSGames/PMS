<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;
use Pms\Data\Db;
use Pms\Support\Csrf;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

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
        $versions = is_array($versions) ? $versions : [];

        $warning = '<div class="notice notice-warn">' . Icons::render('warning')
            . '<span>Die aktuelle Fassung wird dabei verworfen. Legen Sie vorher eine '
            . '<a href="' . Html::e(Html::url('backup')) . '">Sicherung</a> an, wenn Sie sie behalten möchten.'
            . '</span></div>';

        $header = Components::pageHeader(
            'Frühere Fassung einspielen',
            'Gesicherte Fassungen von "' . $name . '", die neueste zuerst.',
            Components::secondary('Zurück zum Inhalt', Html::url('item', ['edit' => $itemId]), 'chevron-left')
        );

        if ($versions === []) {
            return $header . Components::emptyState(
                'Keine gesicherte Fassung',
                'Für diesen Inhalt liegt in den Sicherungen nichts vor.'
            );
        }

        $html = '<div class="card"><div class="card-body"><ol class="timeline">';
        foreach ($versions as $index => $version) {
            $url = Html::url('item_recover', [
                'item' => $itemId,
                'do_recover' => 'yes',
                'recover_id' => $index,
            ] + Csrf::queryParam());

            $html .= '<li>'
                . '<div class="timeline-head">'
                . '<strong>' . Html::e((string)$version[0]) . '</strong>'
                . ($index === 0 ? Components::chip('Neueste', 'ok') : '')
                . '<span class="timeline-actions">'
                . '<a class="btn btn-secondary" href="' . Html::e($url) . '">Diese Fassung einspielen</a>'
                . '</span></div>'
                . '</li>';
        }

        return $header . $warning . $html . '</ol></div></div>';
    }
}
