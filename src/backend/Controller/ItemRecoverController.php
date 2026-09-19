<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;
use Pms\Data\Db;
use Pms\Support\Csrf;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\ItemVersion;
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

        Flash::success('Inhalt erfolgreich zurückgesetzt. <a href="'
            . Html::e(Html::asset('index.php') . '?item=' . $itemId) . '">Inhalt anzeigen</a>');
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

        $current = $this->find($itemId);

        $html = '<div class="card"><div class="card-body"><ol class="timeline">';
        foreach ($versions as $index => $version) {
            $url = Html::url('item_recover', [
                'item' => $itemId,
                'do_recover' => 'yes',
                'recover_id' => $index,
            ] + Csrf::queryParam());

            $differences = $current === null
                ? []
                : ItemVersion::differences(ItemVersion::values((string)$version[1]), $current);
            $unchanged = $current !== null && $differences === [];

            $html .= '<li>'
                . '<div class="timeline-head">'
                . '<strong>' . Html::e((string)$version[0]) . '</strong>'
                . ($index === 0 ? Components::chip('Neueste', 'ok') : '')
                . ($unchanged ? Components::chip('Unverändert', '') : '')
                . '<span class="timeline-actions">'
                . '<a class="btn btn-secondary" href="' . Html::e($url) . '">Diese Fassung einspielen</a>'
                . '</span></div>'
                . $this->differenceBlock($differences, $unchanged)
                . '</li>';
        }

        return $header . $warning . $html . '</ol></div></div>';
    }

    /** Den Datensatz, wie er jetzt in der Datenbank steht. */
    private function find(int $itemId): ?object
    {
        $rows = Db::select(
            'SELECT * FROM ' . Db::table('item') . ' WHERE id = :id',
            ['id' => $itemId]
        );

        return $rows[0] ?? null;
    }

    /**
     * Was diese Fassung von der aktuellen unterscheidet.
     *
     * @param list<array{field: string, label: string, long: bool, saved: string, now: string}> $differences
     */
    private function differenceBlock(array $differences, bool $unchanged): string
    {
        if ($unchanged) {
            return '<p class="field-hint">Diese Fassung stimmt mit der aktuellen überein.</p>';
        }

        if ($differences === []) {
            return '';
        }

        $namen = array_map(
            static fn(array $difference): string => $difference['label'],
            $differences
        );

        $body = '';
        foreach ($differences as $difference) {
            $body .= '<h4 class="version-diff-title">' . Html::e($difference['label']) . '</h4>'
                . ($difference['long']
                    ? $this->lineDiff($difference['saved'], $difference['now'])
                    : $this->valuePair($difference['saved'], $difference['now']));
        }

        return '<details class="version-diff">'
            . '<summary>Unterschiede: ' . Html::e(implode(', ', $namen)) . '</summary>'
            . $body
            . '</details>';
    }

    /** Ein kurzer Wert, vorher und jetzt. */
    private function valuePair(string $saved, string $now): string
    {
        return '<dl class="version-diff-values">'
            . '<dt>Sicherung</dt><dd>' . Html::e($saved === '' ? '(leer)' : $saved) . '</dd>'
            . '<dt>Jetzt</dt><dd>' . Html::e($now === '' ? '(leer)' : $now) . '</dd>'
            . '</dl>';
    }

    /** Zeilenweiser Vergleich eines langen Texts. */
    private function lineDiff(string $saved, string $now): string
    {
        $html = '<pre class="version-diff-lines">';

        foreach (ItemVersion::lineDiff($saved, $now) as [$sign, $text]) {
            $klasse = match ($sign) {
                '-' => 'diff-out',
                '+' => 'diff-in',
                '…' => 'diff-gap',
                default => 'diff-same',
            };
            $html .= '<span class="' . $klasse . '">'
                . Html::e($sign . ($sign === '…' ? '' : ' ' . $text))
                . "</span>\n";
        }

        return $html . '</pre>';
    }
}
