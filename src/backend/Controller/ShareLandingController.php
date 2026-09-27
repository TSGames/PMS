<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Data\Db;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

/**
 * Landeseite nach einem Bild aus dem Teilen-Menü des Betriebssystems.
 *
 * ShareTargetEndpoint legt die Datei(en) schon ab und merkt sich die
 * Namen in der Sitzung (SESSION_KEY) - hier wird entschieden, wozu sie
 * gehören: zu welchem Inhalt. Der Sprung landet direkt im
 * Zuschneide-Dialog des gewählten Inhalts (siehe ItemController,
 * Parameter insert_image), nicht nur irgendwo in der allgemeinen
 * Bilderliste, wo man es erst wiederfinden müsste.
 */
final class ShareLandingController extends Controller
{
    public const SESSION_KEY = 'shared_images';

    #[\Override]
    public function action(): string
    {
        return 'share_landing';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('goto_item') && $this->checkToken()) {
            $this->gotoItem();
        }
        if (Request::submitted('discard_image') && $this->checkToken()) {
            $this->discard();
        }

        return $this->overview();
    }

    private function gotoItem(): void
    {
        $itemId = Request::int('item');
        $image = $this->takeFromSession(Request::string('image'));

        if ($image === null) {
            Flash::error('Dieses Bild ist nicht mehr in der Warteliste.');
            return;
        }
        if ($itemId <= 0 || from_db('item', $itemId, 'id') === null) {
            Flash::error('Bitte einen vorhandenen Inhalt wählen.');
            return;
        }

        header('Location: ' . Html::url('item', ['edit' => $itemId, 'insert_image' => $image]));
        exit;
    }

    private function discard(): void
    {
        $image = $this->takeFromSession(Request::string('image'));
        if ($image !== null) {
            @unlink('images/uploads/' . $image);
        }
    }

    /** Nimmt einen Dateinamen aus der Sitzungsliste, wenn er dort steht. */
    private function takeFromSession(string $image): ?string
    {
        $image = basename($image);
        /** @var list<string> $pending */
        $pending = $_SESSION[self::SESSION_KEY] ?? [];
        if (!in_array($image, $pending, true)) {
            return null;
        }
        $_SESSION[self::SESSION_KEY] = array_values(array_diff($pending, [$image]));
        return $image;
    }

    private function overview(): string
    {
        /** @var list<string> $pending */
        $pending = $_SESSION[self::SESSION_KEY] ?? [];
        // Dateien, die inzwischen von anderswo gelöscht wurden, nicht anzeigen
        $pending = array_values(array_filter($pending, static fn(string $name): bool => is_file('images/uploads/' . $name)));

        $header = Components::pageHeader(
            'Geteiltes Bild',
            'Aus dem Teilen-Menü des Geräts empfangen - wählen Sie, zu welchem Inhalt es gehört.'
        );

        if ($pending === []) {
            return $header . Components::emptyState(
                'Nichts mehr offen',
                'Alle geteilten Bilder sind bereits einem Inhalt zugeordnet oder verworfen.'
            ) . Components::secondary('Zu den Inhalten', Html::url('item'), 'chevron-left');
        }

        $options = ['' => '– Inhalt wählen –'];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('item') . ' ORDER BY id DESC LIMIT 300') as $item) {
            $name = (string)$item->name !== '' ? (string)$item->name : '(ohne Titel)';
            $options[(int)$item->id] = '#' . $item->id . ' ' . $name;
        }

        $rows = '';
        foreach ($pending as $image) {
            $rows .= $this->row($image, $options);
        }

        return $header . '<div class="share-image-list">' . $rows . '</div>';
    }

    /** @param array<int|string, string> $options */
    private function row(string $image, array $options): string
    {
        $path = 'images/uploads/' . $image;
        $size = @getimagesize($path);
        $dims = $size ? $size[0] . ' × ' . $size[1] . ' px' : '';

        return '<div class="card share-image-row">'
            . '<img src="' . Html::e($path) . '" alt="" class="share-image-preview" loading="lazy">'
            . '<div class="share-image-body">'
            . '<div class="share-image-name">' . Html::e($image) . '</div>'
            . '<div class="field-hint">' . Html::e($dims) . '</div>'
            . Html::formOpen($this->action())
            . Html::hidden('image', $image)
            . '<div class="share-image-actions">'
            . Html::select('item', $options, '', ['required' => 'required'])
            . '<button type="submit" name="goto_item" value="1">Weiter zum Inhalt</button>'
            . '<button type="submit" name="discard_image" value="1" class="btn-secondary">Verwerfen</button>'
            . '</div>'
            . Html::formClose()
            . '</div>'
            . '</div>';
    }
}
