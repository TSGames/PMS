<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Editor;
use Pms\Backend\Support\EntityImage;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;

/**
 * Inhaltsverwaltung.
 *
 * Der Bereich führt durch zwei Schritte: In der Vorauswahl werden Typ,
 * Kategorie und Unterkategorie festgelegt, danach folgt der eigentliche
 * Editor. Zusätzlich verwaltet er die Bilder eines Inhalts.
 */
final class ItemController extends Controller
{
    private const TYPE_NEWS = 1;
    private const TYPE_DOWNLOAD = 2;
    private const TYPE_SPECIAL = 3;

    private const SPECIAL_DOWNLOAD = 2;
    private const SPECIAL_GUESTBOOK = 4;
    private const SPECIAL_BANNED = 5;

    private const UPLOAD_DIR = 'images/uploads/';

    /** Bildpfad, der beim Rendern in den Inhalt eingesetzt wird. */
    private string $insertImage = '';

    #[\Override]
    public function action(): string
    {
        return 'item';
    }

    #[\Override]
    public function handle(): string
    {
        // --- Eingaben verarbeiten ------------------------------------------
        if (Request::submitted('item_filter')) {
            $this->applyFilter();
        }

        if (Request::submitted('item_step2')) {
            return $this->save();
        }

        if (Request::submitted('add_image')) {
            return $this->receiveUpload();
        }

        if (Request::submitted('add_image2') || Request::submitted('add_image2_abort')) {
            return $this->finishImageInsert();
        }

        if (Request::queryInt('do_copy') > 0) {
            $this->copy(Request::queryInt('do_copy'));
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            $this->delete($confirmed);
        }

        // --- Bildauswahl ----------------------------------------------------
        if (Request::string('action') === 'add_image') {
            return $this->imageAction();
        }

        // --- Anzeige entscheiden -------------------------------------------
        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            return $this->deleteConfirmation($delete);
        }

        if (Request::submitted('item_refresh')) {
            return $this->wizard($this->wizardValuesFromRequest());
        }

        if (Request::submitted('item_step1')) {
            $values = $this->wizardValuesFromRequest();
            return $this->selectionIsValid($values) ? $this->editor($values) : $this->wizard($values);
        }

        $edit = Request::queryInt('edit');
        if ($edit > 0) {
            $item = $this->find($edit);
            if ($item === null) {
                Flash::error('Der Inhalt wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->wizard($this->wizardValuesFromItem($item));
        }

        if (Request::string('new') !== '') {
            return $this->wizard($this->wizardValuesForNew());
        }

        Sorting::handleRequest('item');

        return $this->overview();
    }

    // -----------------------------------------------------------------
    // Filter
    // -----------------------------------------------------------------

    private function applyFilter(): void
    {
        $cat = Request::int('uppcat');
        $subcat = Request::int('uppcat2');

        if ($subcat > 0) {
            $belongs = Db::first(
                'SELECT id FROM ' . Db::table('subcat') . ' WHERE id = :id AND cat = :cat',
                ['id' => $subcat, 'cat' => $cat]
            );
            if ($belongs === null) {
                $subcat = 0;
            }
        }

        $_SESSION['item_filter'] = $cat;
        $_SESSION['item_filter2'] = $subcat;
    }

    private function filterCat(): int
    {
        return (int)($_SESSION['item_filter'] ?? 0);
    }

    private function filterSubcat(): int
    {
        return (int)($_SESSION['item_filter2'] ?? 0);
    }

    // -----------------------------------------------------------------
    // Vorauswahl
    // -----------------------------------------------------------------

    /**
     * @return array{id: int, typ: int, typ2: int, cat: int, subcat: int, sort: int, name: string}
     */
    private function wizardValuesForNew(): array
    {
        $cat = Request::queryInt('cat', $this->filterCat());
        $subcat = Request::queryInt('subcat', $this->filterSubcat());
        if (Request::queryInt('subcat') > 0) {
            $cat = (int)from_db('subcat', Request::queryInt('subcat'), 'cat');
        }

        return [
            'id' => 0,
            'typ' => 0,
            'typ2' => 0,
            'cat' => $cat,
            'subcat' => $subcat,
            'sort' => Request::queryInt('sort'),
            'name' => Request::string('name'),
        ];
    }

    private function wizardValuesFromItem(object $item): array
    {
        return [
            'id' => (int)$item->id,
            'typ' => (int)$item->typ,
            'typ2' => (int)$item->special,
            'cat' => (int)$item->cat,
            'subcat' => (int)$item->subcat,
            'sort' => (int)$item->sort,
            'name' => (string)$item->name,
        ];
    }

    private function wizardValuesFromRequest(): array
    {
        return [
            'id' => Request::int('id'),
            'typ' => Request::int('typ'),
            'typ2' => Request::int('typ2'),
            'cat' => Request::int('cat'),
            'subcat' => Request::int('subcat'),
            'sort' => Request::int('sort'),
            'name' => Request::string('name'),
        ];
    }

    /** Passen Typ, Kategorie und Unterkategorie zusammen? */
    private function selectionIsValid(array $values): bool
    {
        if ($values['typ'] === self::TYPE_SPECIAL) {
            return $values['typ2'] > 0;
        }

        return Db::first(
            'SELECT id FROM ' . Db::table('subcat') . ' WHERE id = :id AND cat = :cat',
            ['id' => $values['subcat'], 'cat' => $values['cat']]
        ) !== null;
    }

    private function wizard(array $values): string
    {
        $isEdit = $values['id'] > 0;
        $contentTypes = $GLOBALS['content_typ'] ?? [];
        $specialTypes = $GLOBALS['special_typ'] ?? [];

        $html = Html::formOpen($this->action())
            . Html::heading('Inhalt ' . ($isEdit ? 'bearbeiten' : 'hinzufügen') . ' - Vorauswahl')
            . Html::hidden('id', $values['id'])
            . Html::hidden('sort', $values['sort'])
            . Html::hidden('name', $values['name'])
            . '<table>'
            . Html::field('Typ des Inhalts', Html::select('typ', $contentTypes, $values['typ']));

        $categories = $this->categoryOptions();
        $selectable = false;

        if ($values['typ'] === self::TYPE_SPECIAL) {
            $html .= Html::field(
                'Art des Spezialinhalts',
                Html::select('typ2', $specialTypes, $values['typ2'])
                . ' <input type="submit" name="item_refresh" value="Aktualisieren">'
            );
            $selectable = true;
        } elseif ($categories === []) {
            $html .= '<tr><td colspan="2">' . warning_box(
                'Sie können keinen Inhalt erstellen, da noch keine Kategorien existieren.<br>'
                . 'Wählen Sie als Typ "Spezialseite" oder <a href="' . Html::e(Html::url('cat', ['new' => 'yes']))
                . '">erstellen Sie eine Kategorie</a>.'
            ) . '</td></tr>';
        } else {
            $html .= Html::field(
                'In Kategorie',
                Html::select('cat', $categories, $values['cat'])
                . ' <input type="submit" name="item_refresh" value="Aktualisieren">'
            );

            $subcats = $this->subcategoryOptions($values['cat'] > 0 ? $values['cat'] : (int)array_key_first($categories));
            if ($subcats !== []) {
                $html .= Html::field('In Unterkategorie', Html::select('subcat', $subcats, $values['subcat']));
                $selectable = true;
            } else {
                $catName = (string)from_db('cat', $values['cat'], 'name');
                $html .= '<tr><td colspan="2">' . warning_box(
                    'Sie können in der Kategorie ' . Html::e($catName) . ' keinen Inhalt hinzufügen, da es noch '
                    . 'keine Unterkategorien gibt.<br>Wählen Sie eine andere Kategorie und klicken Sie auf '
                    . '"Aktualisieren", oder <a href="' . Html::e(Html::url('subcat', ['cat' => $values['cat'], 'new' => 'yes']))
                    . '">legen Sie eine neue Unterkategorie an</a>.'
                ) . '</td></tr>';
            }
        }

        if ($selectable) {
            $html .= '<tr><td colspan="2"><div class="action-section">'
                . Html::hidden('tinymce_vis', 1)
                . Html::checkbox('tinymce', Editor::isEnabled(), 'Grafischen HTML-Editor (TinyMCE) verwenden')
                . '</div></td></tr>'
                . '<tr><td colspan="2"><div class="action-section">'
                . '<input type="submit" name="item_step1" value="Weiter">'
                . '</div></td></tr>';
        }

        return $html . '</table>' . Html::formClose();
    }

    // -----------------------------------------------------------------
    // Editor
    // -----------------------------------------------------------------

    private function editor(array $values): string
    {
        $item = $values['id'] > 0 ? $this->find($values['id']) : null;
        $isEdit = $item !== null;

        $name = $isEdit ? (string)$item->name : $values['name'];
        $description = $isEdit ? (string)$item->description : '';
        $content = $isEdit ? (string)$item->content : '';
        $sort = $isEdit ? (int)$item->sort : ($values['sort'] > 0 ? $values['sort'] : 1000);
        $link = $isEdit ? (string)$item->link : '';
        $author = $isEdit ? (int)$item->user : Auth::userId();
        $created = $isEdit ? (int)$item->time : time();

        $available = $isEdit ? (bool)$item->available : true;
        $visible = $isEdit ? (bool)$item->visible : true;
        $showuser = $isEdit ? (bool)$item->showuser : true;
        $rate = $isEdit ? (bool)$item->rate : true;
        $comments = $isEdit ? (bool)$item->comments : true;

        $type = $values['typ'];
        $special = $values['typ2'];
        if (!$isEdit && $special === self::SPECIAL_GUESTBOOK && $name === '') {
            $name = 'Gästebuch';
        }

        $image = $isEdit ? (string)$item->image : '';
        $imageHidden = '';
        if ($isEdit && $image === '') {
            $detected = EntityImage::detect('item', (int)$item->id);
            if ($detected !== null) {
                $image = $detected;
                $imageHidden = Html::hidden('image_add', $detected);
            }
        }

        // Ein gerade ausgewähltes Bild ersetzt den Platzhalter im Inhalt
        if ($this->insertImage !== '') {
            $content = str_replace(
                '<img id="##pms_replace_image_temp"',
                '<img src="' . $this->insertImage . '"',
                $content
            );
        } else {
            $content = cleanup_content($content);
        }

        $html = Html::formOpen($this->action(), [], ['upload' => true])
            . Html::heading('Inhalt ' . ($isEdit ? 'bearbeiten' : 'erstellen'))
            . Html::hidden('action', 'item')
            . Html::hidden('id', $isEdit ? (int)$item->id : 0)
            . Html::hidden('cat', $values['cat'])
            . Html::hidden('subcat', $values['subcat'])
            . Html::hidden('typ', $type)
            . Html::hidden('typ2', $special)
            . $imageHidden
            . '<table>';

        if ($isEdit) {
            $html .= '<tr><td colspan="2" class="action-section">'
                . '<a href="index.php?item=' . (int)$item->id . '" target="_blank" rel="noopener">Seite in neuem Fenster anzeigen</a>';
            if (recover_item((int)$item->id)) {
                $html .= ' | <a href="' . Html::e(Html::url('item_recover', ['item' => (int)$item->id]))
                    . '">Version wiederherstellen...</a>';
            }
            $html .= '</td></tr>';
        }

        $html .= Html::field('Titel', Html::input('name', $name, ['size' => 36]))
            . Html::field('Kurzbeschreibung (Optional)', Html::textarea('description', $description, 5, 35))
            . Html::field('Erstellungsdatum', $this->createdAtFields($created, $isEdit));

        if ($special === self::SPECIAL_DOWNLOAD || $special === self::SPECIAL_BANNED) {
            $placeholders = $special === self::SPECIAL_DOWNLOAD
                ? '#id (ID des Downloads), #file (Name des Downloads), #button (Download-Button)'
                : '#ip (IP-Adresse des Nutzers), #reason (Begründung des Bans), #time (Zeitlimit des Bans)';
            $html .= Html::field('Folgende Platzhalter sind möglich', Html::e($placeholders));
        }

        if ($type === self::TYPE_DOWNLOAD) {
            $html .= Html::field('Download-Link', Html::input('link', $link, ['size' => 36]));
        }

        $html .= Html::field('Bild (optional)', '<input type="file" size="36" name="image">');

        if ($type !== self::TYPE_NEWS) {
            $html .= '<tr><td colspan="2">Mit #item_picture können Sie das gewählte Bild einfügen, '
                . 'andernfalls wird die Position automatisch bestimmt.</td></tr>'
                . '<tr><td></td><td><a href="#" id="pic_extended_toggle">Weitere Bild-Optionen</a>'
                . '<div id="pic_extended" style="display:none;">'
                . Html::checkbox('full_image', false, 'Originalbild speichern und verlinken')
                . '</div></td></tr>';
        }

        if ($image !== '' && $isEdit) {
            $html .= '<tr><td>' . make_contentimg('item', (int)$item->id, $image, 0) . '</td><td>'
                . Html::checkbox('image_delete', false, 'Aktuelles Bild löschen') . '</td></tr>';
        }

        $html .= Html::field('Sortierung', Html::input('sort', $sort, ['size' => 7]));

        if ($special !== self::SPECIAL_GUESTBOOK) {
            $html .= Html::field('Autor', Html::select('user', $this->userOptions(), $author));
        }

        $html .= '<tr><td colspan="2">Inhalt:</td></tr>'
            . '<tr><td colspan="2">'
            . '<textarea rows="22" cols="95" id="content" name="content">' . Html::e($content) . '</textarea>'
            . '</td></tr>'
            . $this->xlsxImportBlock();

        if (Editor::isEnabled() && $isEdit) {
            $html .= $this->imageInsertBlock((int)$item->id);
        }

        $html .= '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('available', $available, 'Inhalt verfügbar (Zugriff erlaubt, Administratoren haben immer Zugriff)')
            . '</div></td></tr>';

        if ($type !== self::TYPE_SPECIAL) {
            $html .= '<tr><td colspan="2"><div class="action-section">'
                . Html::checkbox('visible', $visible, 'Inhalt ist sichtbar (Inhalt wird in Liste gezeigt, Administratoren sehen alle Inhalte)')
                . '</div></td></tr>';
        }

        if ($special !== self::SPECIAL_GUESTBOOK) {
            $html .= '<tr><td colspan="2"><div class="action-section">'
                . Html::checkbox('showuser', $showuser, '"Geschrieben von..." anzeigen')
                . '</div></td></tr>';

            if ($special !== self::SPECIAL_BANNED) {
                $html .= '<tr><td colspan="2"><div class="action-section">'
                    . Html::checkbox('rate', $rate, 'Inhalt darf bewertet werden')
                    . '</div></td></tr>'
                    . '<tr><td colspan="2"><div class="action-section">'
                    . Html::checkbox('comments', $comments, 'Inhalt darf kommentiert werden')
                    . '</div></td></tr>';
            }
        }

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" id="item_button1" name="item_step2" value="Übernehmen" data-hide-on-submit> '
            . '<input type="submit" id="item_button2" name="item_step2" value="Übernehmen &amp; Schließen" data-hide-on-submit>'
            . '<div id="item_save" style="display:none;font-weight:bold;">Bitte warten, Inhalt wird gespeichert...</div>'
            . '</div></td></tr></table>'
            . Html::formClose()
            . '<script type="text/javascript" src="js/admin-item-editor.js"></script>';
    }

    private function createdAtFields(int $timestamp, bool $isEdit): string
    {
        return Html::input('create_at_date', date('d.m.Y', $timestamp), ['size' => 7, 'maxlength' => 10, 'id' => 'create_at_date', 'disabled' => 'disabled'])
            . ' '
            . Html::input('create_at_time', date('H:i', $timestamp), ['size' => 3, 'maxlength' => 5, 'id' => 'create_at_time', 'disabled' => 'disabled'])
            . ' <label><input type="checkbox" onclick="refresh_create()" id="create_at_use" name="create_at_use" value="1" checked> '
            . ($isEdit ? 'Nicht verändern' : 'Automatisch') . '</label>';
    }

    private function xlsxImportBlock(): string
    {
        return '<tr><td colspan="2"><fieldset class="import-box">'
            . '<legend>XLSX Datei importieren</legend>'
            . '<input type="file" id="xlsx_file_picker" accept=".xlsx" style="display:none">'
            . '<button type="button" onclick="document.getElementById(\'xlsx_file_picker\').click()">XLSX Inhalt importieren</button>'
            . '<span id="xlsx_status" data-token="' . Html::e(\Pms\Backend\Support\Csrf::token()) . '"></span>'
            . '<br><small>Zeichentabelle wird als Rohtext mit Leerzeichen als Trennzeichen eingefügt</small>'
            . '</fieldset></td></tr>'
            . '<script type="text/javascript" src="js/admin-xlsx-import.js"></script>';
    }

    /** Bereich zum Einfügen eines Bildes in den Text (nur mit TinyMCE). */
    private function imageInsertBlock(int $itemId): string
    {
        return '<tr><td colspan="2">'
            . Html::hidden('next', '')
            . Html::hidden('add_image', '')
            . Html::hidden('item', $itemId)
            . Html::hidden('drag_name', '')
            . Html::hidden('drag_data', '')
            . '<fieldset class="import-box"><legend>Bild einfügen</legend>'
            . '<input type="file" id="image_file_picker" accept=".jpg,.jpeg,.png,.gif" style="display:none">'
            . '<button type="button" onclick="document.getElementById(\'image_file_picker\').click()">Bild auswählen</button>'
            . '<span id="image_status"></span><br>'
            . '<div class="drop_zone" id="drop_zone">oder ziehen Sie eine Bild-Datei von Ihrem Explorer in dieses Feld</div>'
            . '</fieldset></td></tr>';
    }

    // -----------------------------------------------------------------
    // Speichern
    // -----------------------------------------------------------------

    private function save(): string
    {
        if (!$this->checkToken()) {
            return $this->overview();
        }

        $id = Request::int('id');
        $type = Request::int('typ');
        $special = Request::int('typ2');
        $cat = Request::int('cat');
        $subcat = Request::int('subcat');

        if ($type === self::TYPE_SPECIAL) {
            $cat = 0;
            $subcat = 0;
        } else {
            $_SESSION['item_filter'] = $cat;
            $_SESSION['item_filter2'] = $subcat;
        }

        $name = Request::string('name');
        if ($name === '') {
            Flash::error('Bitte geben Sie einen Titel an.');
            return $this->editor($this->wizardValuesFromRequest());
        }

        $data = [
            'cat' => $cat,
            'subcat' => $subcat,
            'name' => $name,
            'typ' => $type,
            'special' => $special,
            'showuser' => Request::checkbox('showuser'),
            'rate' => Request::checkbox('rate'),
            'comments' => Request::checkbox('comments'),
            'description' => Request::text('description'),
            'content' => Request::text('content'),
            'sort' => Request::int('sort', 1000),
            'user' => Request::int('user', Auth::userId()),
            'link' => Request::string('link'),
            'available' => Request::checkbox('available'),
            // Spezialseiten erscheinen nicht in Listen; das Feld wird dort nicht angeboten
            'visible' => Request::checkbox('visible'),
        ];

        // Ein eigenes Erstellungsdatum ersetzt die automatische Zeit
        $customTime = $this->requestedCreationTime();

        if ($type === self::TYPE_SPECIAL && $special > 0) {
            // Eine Spezialseite gibt es je Art nur einmal
            Db::execute(
                'UPDATE ' . Db::table('item') . ' SET special = 0 WHERE special = :special',
                ['special' => $special]
            );
        }

        if ($id > 0) {
            $data['time_changed'] = time();
            if ($customTime !== null) {
                $data['time'] = $customTime;
            }
            if (Request::checkbox('image_delete')) {
                del_contentimg('item', $id, (string)from_db('item', $id, 'image'));
                $data['image'] = '';
            }
            $saved = Db::update('item', $id, $data);
        } else {
            $data['time'] = $customTime ?? time();
            $data['image'] = '';
            $id = Db::insert('item', $data);
            $saved = $id > 0;
        }

        if (!$saved) {
            Flash::error('Fehler beim Speichern des Inhalts!');
            return $this->editor($this->wizardValuesFromRequest());
        }

        $this->storeImage($id, $type);

        Flash::success('Inhalt erfolgreich gespeichert! <a href="index.php?item=' . $id . '">Inhalt anzeigen</a>');

        // Nach dem Speichern zum Bild-Dialog, zurück in den Editor oder zur Liste
        if (Request::string('next') === 'image') {
            return $this->imagePicker($id);
        }
        if (Request::string('item_step2') === 'Übernehmen & Schließen') {
            $this->redirect();
        }

        $values = $this->wizardValuesFromRequest();
        $values['id'] = $id;
        return $this->editor($values);
    }

    /** Vom Benutzer gesetztes Erstellungsdatum, sonst null. */
    private function requestedCreationTime(): ?int
    {
        if (Request::checkbox('create_at_use')) {
            return null;
        }

        $date = explode('.', Request::string('create_at_date'));
        $time = explode(':', Request::string('create_at_time'));
        if (count($date) !== 3) {
            return null;
        }

        $timestamp = @mktime(
            (int)($time[0] ?? 0),
            (int)($time[1] ?? 0),
            0,
            (int)$date[1],
            (int)$date[0],
            (int)$date[2]
        );
        return $timestamp === false ? null : $timestamp;
    }

    /** Übernimmt ein hochgeladenes Titelbild in seinen Größen. */
    private function storeImage(int $id, int $type): void
    {
        $upload = $_FILES['image'] ?? null;
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['name'] === '') {
            return;
        }

        $extension = strtolower((string)pathinfo($upload['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, EntityImage::supportedTypes(), true)) {
            Flash::error('Das Bildformat wird nicht unterstützt.');
            return;
        }

        del_contentimg('item', $id, (string)from_db('item', $id, 'image'));

        $path = ($GLOBALS['image_path'] ?? 'images/') . 'item/';
        @mkdir($path, 0755, true);

        $thumb = $path . $id . '.' . $extension;
        @copy($upload['tmp_name'], $thumb);
        create_img($thumb, 256, 256);

        if ($type !== self::TYPE_NEWS) {
            $large = $path . $id . '_large.' . $extension;
            @copy($upload['tmp_name'], $large);
            create_img($large, 640, 480);

            if (Request::checkbox('full_image')) {
                @copy($upload['tmp_name'], $path . $id . '_full.' . $extension);
            }
        }

        Db::update('item', $id, ['image' => $extension]);
    }

    // -----------------------------------------------------------------
    // Bilder für den Inhalt
    // -----------------------------------------------------------------

    /** Aktionen der Bildauswahl (Adresse action=add_image). */
    private function imageAction(): string
    {
        $itemId = Request::queryInt('item');

        $delete = Request::string('delete');
        if ($delete !== '') {
            $file = self::UPLOAD_DIR . basename($delete);
            if (@unlink($file)) {
                Flash::success('Bild wurde entfernt');
            } else {
                Flash::error('Bild konnte nicht entfernt werden');
            }
            return $this->imagePicker($itemId);
        }

        $image = Request::string('image');
        if ($image !== '') {
            // Vorhandenes Bild übernehmen
            $this->insertImage = self::UPLOAD_DIR . basename($image);
            return $this->editorForItem($itemId);
        }

        if (Request::string('abort') !== '') {
            return $this->editorForItem($itemId);
        }

        return $this->imagePicker($itemId);
    }

    /** Nimmt ein hochgeladenes oder gezogenes Bild entgegen. */
    private function receiveUpload(): string
    {
        if (!$this->checkToken()) {
            return $this->overview();
        }

        $itemId = Request::int('item');
        if ($itemId <= 0) {
            Flash::error('Ungültige Element-ID');
            return $this->overview();
        }

        // Aus dem Zuschneide-Dialog kommt bereits eine fertige Datei
        $alreadyUploaded = Request::string('already_uploaded');
        if ($alreadyUploaded !== '') {
            $name = basename($alreadyUploaded);
            if (!file_exists(self::UPLOAD_DIR . $name)) {
                Flash::error('Bilddatei nicht gefunden');
                return $this->imagePicker($itemId);
            }
            return $this->imageScale($itemId, $name);
        }

        $name = Request::string('drag_name');
        $data = $name !== '' ? rawurldecode(Request::text('drag_data')) : '';
        if ($name === '') {
            $name = (string)($_FILES['image']['name'] ?? '');
        }

        if ($name === '') {
            Flash::error('Keine Datei ausgewählt');
            return $this->imagePicker($itemId);
        }

        $cleanName = link_name($name, '.');
        $extension = strtolower((string)pathinfo($cleanName, PATHINFO_EXTENSION));
        if (!in_array($extension, EntityImage::supportedTypes(), true)) {
            Flash::error('Nicht unterstütztes Dateiformat');
            return $this->imagePicker($itemId);
        }

        $target = $this->uniqueUploadName($cleanName, $extension);
        @mkdir(self::UPLOAD_DIR, 0755, true);

        $stored = $data !== ''
            ? (bool)@file_put_contents(self::UPLOAD_DIR . $target, $data)
            : @copy($_FILES['image']['tmp_name'], self::UPLOAD_DIR . $target);

        if (!$stored) {
            Flash::error('Fehler beim Anlegen der Datei');
            return $this->imagePicker($itemId);
        }

        return $this->imageScale($itemId, $target);
    }

    private function uniqueUploadName(string $name, string $extension): string
    {
        $base = substr($name, 0, strlen($name) - strlen($extension) - 1);
        $candidate = $name;
        for ($i = 1; file_exists(self::UPLOAD_DIR . $candidate); $i++) {
            $candidate = $base . $i . '.' . $extension;
        }
        return $candidate;
    }

    /** Größe festlegen und Bild in den Inhalt einfügen. */
    private function finishImageInsert(): string
    {
        if (!$this->checkToken()) {
            return $this->overview();
        }

        $itemId = Request::int('item');
        $image = basename(Request::string('image'));
        $file = self::UPLOAD_DIR . $image;

        if (Request::submitted('add_image2_abort')) {
            @unlink($file);
            return $this->editorForItem($itemId);
        }

        $width = Request::int('image_width');
        $height = Request::int('image_height');
        if ($width < 1 || $height < 1) {
            Flash::error('Ungültige Bildgröße');
            return $this->imageScale($itemId, $image);
        }

        create_img($file, $width, $height, 0);
        $this->insertImage = $file;

        return $this->editorForItem($itemId);
    }

    /** Öffnet den Editor eines gespeicherten Inhalts. */
    private function editorForItem(int $itemId): string
    {
        $item = $this->find($itemId);
        if ($item === null) {
            Flash::error('Der Inhalt wurde nicht gefunden.');
            return $this->overview();
        }
        return $this->editor($this->wizardValuesFromItem($item));
    }

    /** Auswahl eines vorhandenen oder neuen Bildes. */
    private function imagePicker(int $itemId): string
    {
        $html = Html::heading('Bild einfügen')
            . Html::formOpen($this->action(), [], ['upload' => true])
            . Html::hidden('item', $itemId)
            . '<table>'
            . Html::field('Bilddatei wählen (jpg, png, gif)', '<input type="file" name="image">')
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="add_image" value="Bild hinzufügen"> '
            . Html::button('Abbrechen', Html::url('add_image', ['item' => $itemId, 'abort' => 'yes']), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();

        $files = $this->uploadedImages();
        $rows = [];
        foreach ($files as $file) {
            $size = @getimagesize(self::UPLOAD_DIR . $file);
            $width = get_size($size, 320, 240, 1);
            $height = get_size($size, 320, 240, 2);

            $rows[] = [
                '<a href="' . Html::e(Html::url('add_image', ['image' => $file, 'item' => $itemId])) . '"'
                . ' data-preview-image="' . Html::e($file) . '"'
                . ' data-preview-width="' . (int)$width . '"'
                . ' data-preview-height="' . (int)$height . '">' . Html::e($file) . '</a>',
                '<a href="' . Html::e(Html::url('add_image', ['item' => $itemId, 'delete' => $file]))
                . '" data-delete-image="' . Html::e($file) . '">Löschen</a>',
            ];
        }

        return $html
            . Html::heading('Existierendes Bild verwenden')
            . '<p>Klicken Sie auf den Namen eines Bildes, um es zu verwenden.</p>'
            . '<div id="image_preview_div" style="display:none;position:absolute;background:#fff;border:1px solid #aaa;">'
            . '<img id="image_preview" alt=""></div>'
            . Html::table(['Bild', 'Löschen'], $rows, 'Es wurden noch keine Bilder hochgeladen.')
            . '<script type="text/javascript" src="js/admin-item-editor.js"></script>';
    }

    /** @return list<string> */
    private function uploadedImages(): array
    {
        $files = [];
        foreach (glob(self::UPLOAD_DIR . '*') ?: [] as $path) {
            if (is_file($path)) {
                $files[] = basename($path);
            }
        }
        usort($files, 'strcasecmp');
        return $files;
    }

    /** Größe des Bildes festlegen, bevor es eingefügt wird. */
    private function imageScale(int $itemId, string $image): string
    {
        $size = @getimagesize(self::UPLOAD_DIR . $image);
        $width = (int)($size[0] ?? 0);
        $height = (int)($size[1] ?? 0);
        $ratio = $height > 0 ? $width / $height : 1;

        return Html::heading('Bild anpassen')
            . Html::formOpen($this->action())
            . Html::hidden('item', $itemId)
            . Html::hidden('image', $image)
            . '<table>'
            . '<tr><td colspan="2"><div class="action-section">'
            . 'Breite: ' . Html::input('image_width', $width, ['size' => 3, 'id' => 'image_width'])
            . ' px - Höhe: ' . Html::input('image_height', $height, ['size' => 3, 'id' => 'image_height']) . ' px'
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<label><input type="checkbox" id="image_pro" value="1" checked> Proportionen beibehalten</label>'
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="add_image2" value="Speichern &amp; Einfügen"> '
            . '<input type="submit" name="add_image2_abort" value="Abbrechen" class="secondary">'
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<img id="image_scale" data-ratio="' . Html::e((string)$ratio) . '"'
            . ' src="' . Html::e(self::UPLOAD_DIR . $image) . '" alt="">'
            . '</div></td></tr></table>'
            . Html::formClose()
            . '<script type="text/javascript" src="js/admin-item-editor.js"></script>';
    }

    // -----------------------------------------------------------------
    // Kopieren und Löschen
    // -----------------------------------------------------------------

    private function copy(int $id): never
    {
        $source = $this->find($id);
        if ($source === null) {
            Flash::error('Der zu kopierende Inhalt wurde nicht gefunden.');
            $this->redirect();
        }

        $data = (array)$source;
        unset($data['id'], $data['rating'], $data['numratings']);
        $data['name'] = (string)$source->name . ' - Kopie';

        $newId = Db::insert('item', $data);
        if ($newId <= 0) {
            Flash::error('Fehler beim Kopieren des Inhalts (ID: ' . $id . ')');
            $this->redirect();
        }

        // Bilder mitkopieren
        $extension = (string)$source->image;
        if ($extension !== '') {
            $path = ($GLOBALS['image_path'] ?? 'images/') . 'item/';
            @copy($path . $id . '.' . $extension, $path . $newId . '.' . $extension);
            @copy($path . $id . '_large.' . $extension, $path . $newId . '_large.' . $extension);
        }

        Flash::success('Inhalt erfolgreich kopiert (Original ID: ' . $id . ', Kopie ID: ' . $newId . ')');
        $this->redirect();
    }

    private function delete(int $id): never
    {
        del_contentimg('item', $id, (string)from_db('item', $id, 'image'));
        Db::execute('DELETE FROM ' . Db::table('comments') . ' WHERE item = :item', ['item' => $id]);

        if (Db::delete('item', $id)) {
            Flash::success('Inhalt erfolgreich entfernt');
        } else {
            Flash::error('Inhalt konnte nicht entfernt werden');
        }
        $this->redirect();
    }

    private function deleteConfirmation(int $id): string
    {
        $item = $this->find($id);
        if ($item === null) {
            Flash::error('Der Inhalt wurde nicht gefunden.');
            return $this->overview();
        }

        return $this->confirmDelete(
            $id,
            'Soll der Inhalt "' . $item->name . '" gelöscht werden?',
            'Hinweis: Kommentare und Bilder dieses Inhalts werden mit entfernt.'
        );
    }

    // -----------------------------------------------------------------
    // Übersicht
    // -----------------------------------------------------------------

    private function overview(): string
    {
        $categories = $this->categoryOptions();
        $contentTypes = $GLOBALS['content_typ'] ?? [];
        $filterCat = $this->filterCat();
        $filterSubcat = $this->filterSubcat();

        $conditions = [];
        $params = [];
        if ($filterCat > 0) {
            $conditions[] = 'cat = :cat';
            $params['cat'] = $filterCat;
        }
        if ($filterSubcat > 0) {
            $conditions[] = 'subcat = :subcat';
            $params['subcat'] = $filterSubcat;
        }

        $sql = 'SELECT * FROM ' . Db::table('item');
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $items = Db::select($sql . ' ORDER BY sort, name', $params);

        $rows = [];
        foreach ($items as $index => $item) {
            $copyCell = (int)$item->typ === self::TYPE_SPECIAL
                ? '<span class="disabled">Kopie erstellen</span>'
                : '<a href="' . Html::e($this->url(['do_copy' => (int)$item->id])) . '">Kopie erstellen</a>';

            $rows[] = [
                (string)(int)$item->id,
                Html::e((string)$item->name),
                Html::e($contentTypes[(int)$item->typ] ?? ''),
                Html::e((string)from_db('cat', (int)$item->cat, 'name')),
                Html::e((string)from_db('subcat', (int)$item->subcat, 'name')),
                Sorting::cell($this->action(), $items[$index - 1] ?? null, $item, $items[$index + 1] ?? null),
                Html::date($item->time),
                Html::yesNo($item->available),
                $copyCell,
                $this->editLink((int)$item->id),
                $this->deleteLink((int)$item->id),
            ];
        }

        $backups = get_backups();
        $restoreButton = is_array($backups) && $backups !== []
            ? Html::button('Gelöschten Inhalt wiederherstellen', Html::url('item_restore'))
            : '<span class="button disabled">Gelöschten Inhalt wiederherstellen</span>';

        return Html::heading('Inhalte')
            . '<div class="action-section">'
            . Html::button('Inhalt hinzufügen', $this->url(['new' => 'yes'])) . ' ' . $restoreButton
            . '</div>'
            . '<div class="action-section">'
            . Html::formOpen($this->action())
            . 'Zeige nur Inhalte der Kategorie '
            . Html::select('uppcat', [0 => '[Alle]'] + $categories, $filterCat)
            . ($filterCat > 0
                ? ' und Unterkategorie ' . Html::select('uppcat2', [0 => '[Alle]'] + $this->subcategoryOptions($filterCat), $filterSubcat)
                : '')
            . ' <input type="submit" name="item_filter" value="OK">'
            . Html::formClose()
            . '</div>'
            . Html::table(
                ['ID', 'Name', 'Typ', 'In Kategorie', 'In Unterkategorie', 'Sortierung', 'Erstellt am', 'Verfügbar', 'Kopie erstellen', 'Bearbeiten', 'Löschen'],
                $rows,
                'In dieser Auswahl sind keine Inhalte vorhanden.'
            );
    }

    // -----------------------------------------------------------------
    // Hilfsmittel
    // -----------------------------------------------------------------

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('item') . ' WHERE id = :id', ['id' => $id]);
    }

    private function categoryOptions(): array
    {
        $options = [];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('cat') . ' ORDER BY sort, name') as $row) {
            $options[(int)$row->id] = (string)$row->name;
        }
        return $options;
    }

    private function subcategoryOptions(int $cat): array
    {
        $options = [];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('subcat') . ' WHERE cat = :cat ORDER BY sort, name', ['cat' => $cat]) as $row) {
            $options[(int)$row->id] = (string)$row->name;
        }
        return $options;
    }

    private function userOptions(): array
    {
        $options = [];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('user') . ' ORDER BY id') as $row) {
            $options[(int)$row->id] = (string)$row->name;
        }
        return $options;
    }
}
