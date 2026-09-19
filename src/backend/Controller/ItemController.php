<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Http\Routes;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Editor;
use Pms\Backend\Support\EntityImage;
use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Backend\View\Icons;

/**
 * Inhaltsverwaltung.
 *
 * Typ, Kategorie und Unterkategorie stehen im Kopf des Editors und sind
 * dort änderbar; die frühere Vorauswahl als eigener Schritt entfällt.
 * Zusätzlich verwaltet der Bereich die Bilder eines Inhalts.
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

        $edit = Request::queryInt('edit');
        if ($edit > 0) {
            $item = $this->find($edit);
            if ($item === null) {
                Flash::error('Der Inhalt wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->editor($this->valuesFromItem($item));
        }

        if (Request::string('new') !== '') {
            return $this->editor($this->valuesForNew());
        }

        Sorting::handleRequest('item');

        return $this->overview();
    }

    // -----------------------------------------------------------------
    // Filter
    // -----------------------------------------------------------------

    private function filterCat(): int
    {
        return Request::queryInt('cat');
    }

    /**
     * Die gewählte Unterkategorie, sofern sie zur gewählten Kategorie gehört.
     */
    private function filterSubcat(): int
    {
        $subcat = Request::queryInt('subcat');
        $cat = $this->filterCat();
        if ($subcat <= 0 || $cat <= 0) {
            return 0;
        }

        $belongs = Db::first(
            'SELECT id FROM ' . Db::table('subcat') . ' WHERE id = :id AND cat = :cat',
            ['id' => $subcat, 'cat' => $cat]
        );
        return $belongs === null ? 0 : $subcat;
    }

    // -----------------------------------------------------------------
    // Einordnung: Typ, Kategorie, Unterkategorie
    // -----------------------------------------------------------------

    /**
     * @return array{id: int, typ: int, typ2: int, cat: int, subcat: int, sort: int, name: string}
     */
    private function valuesForNew(): array
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

    private function valuesFromItem(object $item): array
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

    private function valuesFromRequest(): array
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

        $body = Form::section('Einordnung', $this->placementFields($values))
            . Form::section('Inhalt', $this->contentSection($name, $description, $content, $link, $type))
            . Form::section('Bild', $this->imageSection($item, $image, $type))
            . Form::section('Veröffentlichung', $this->publishingSection(
                $sort,
                $author,
                $created,
                $isEdit,
                $special,
                $available,
                $visible,
                $showuser,
                $rate,
                $comments
            ));

        $actions = '<div class="form-actions">'
            . '<input type="submit" id="item_button1" name="item_step2" value="Übernehmen" data-hide-on-submit>'
            . '<input type="submit" id="item_button2" name="item_step2" value="Übernehmen &amp; Schließen"'
            . ' class="btn-secondary" data-hide-on-submit>'
            . Html::button('Abbrechen', $this->url(), 'btn btn-secondary')
            . '<span id="item_save" class="form-actions-end" style="display:none;">'
            . 'Bitte warten, Inhalt wird gespeichert…</span>'
            . '</div>';

        return Components::pageHeader(
            $isEdit ? 'Inhalt bearbeiten' : 'Inhalt erstellen',
            $isEdit ? (string)$item->name : '',
            $this->editorActions($item)
        )
            . $this->missingStructureNotice()
            . Html::formOpen($this->action(), [], ['upload' => true])
            . Html::hidden('action', 'item')
            . Html::hidden('id', $isEdit ? (int)$item->id : 0)
            . $imageHidden
            // Typ und Einordnung steuern, welche Felder sichtbar sind -
            // deshalb umschließt der Zustand das gesamte Formular
            . '<div x-data="' . Html::e($this->placementState($values)) . '">'
            . Form::card($body, $actions)
            . '</div>'
            . Html::formClose()
            . '<script type="text/javascript" src="js/admin-item-editor.js"></script>';
    }

    /**
     * Die Felder, die den Inhalt einordnen. Typ, Kategorie und
     * Unterkategorie standen früher in einem eigenen Schritt davor.
     *
     * @param array{id: int, typ: int, typ2: int, cat: int, subcat: int, sort: int, name: string} $values
     */
    private function placementFields(array $values): string
    {
        /** @var array<int, string> $contentTypes */
        $contentTypes = $GLOBALS['content_typ'] ?? [];
        /** @var array<int, string> $specialTypes */
        $specialTypes = $GLOBALS['special_typ'] ?? [];

        $html = Form::field(
            'Typ des Inhalts',
            Form::segmented('typ', $contentTypes, $values['typ'], 'typ'),
            ['for' => '', 'name' => 'typ']
        );

        $html .= '<div class="field-group" x-show="typ === ' . self::TYPE_SPECIAL . '" x-cloak>'
            . Form::field(
                'Art des Spezialinhalts',
                Html::select('typ2', [0 => 'Bitte wählen'] + $specialTypes, $values['typ2'], [
                    'id' => 'typ2',
                    'x-model.number' => 'typ2',
                ]),
                ['name' => 'typ2', 'hint' => 'Jede Art gibt es genau einmal.']
            )
            . '</div>';

        $html .= '<div class="field-group" x-show="typ !== ' . self::TYPE_SPECIAL . '" x-cloak>'
            . Form::field(
                'Kategorie',
                Html::select('cat', $this->categoryOptions(), $values['cat'], [
                    'id' => 'cat',
                    'x-model.number' => 'cat',
                    '@change' => 'catChanged()',
                ]),
                ['name' => 'cat', 'required' => true]
            )
            . Form::field(
                'Unterkategorie',
                self::subcatSelect(),
                ['name' => 'subcat', 'required' => true]
            )
            . '</div>';

        return $html;
    }

    /**
     * Ausgangszustand für das Skript, das Kategorie und Unterkategorie
     * verknüpft.
     *
     * @param array{typ: int, typ2: int, cat: int, subcat: int} $values
     */
    private function placementState(array $values): string
    {
        $subcats = [];
        if ($values['cat'] > 0) {
            foreach ($this->subcategoryOptions($values['cat']) as $id => $label) {
                $subcats[] = ['value' => (int)$id, 'label' => $label];
            }
        }

        return 'linkedSelects(' . (string)json_encode([
            'url' => Routes::basePath() . Routes::path('options_ajax'),
            'typ' => $values['typ'],
            'typ2' => $values['typ2'],
            'cat' => $values['cat'],
            'subcat' => $values['subcat'],
            'subcats' => $subcats,
        ]) . ')';
    }

    /**
     * Die Unterkategorien steuert das Skript bei; der Ausgangsbestand steht
     * im Zustand, den linkedSelects() bekommt.
     */
    private static function subcatSelect(): string
    {
        return '<select name="subcat" id="subcat" x-model.number="subcat">'
            . '<option value="0">Bitte wählen</option>'
            . '<template x-for="option in subcats" :key="option.value">'
            . '<option :value="option.value" x-text="option.label"></option>'
            . '</template>'
            . '</select>';
    }

    /** Titel, Kurzbeschreibung, Download-Link und der eigentliche Text. */
    private function contentSection(string $name, string $description, string $content, string $link, int $type): string
    {
        $html = Form::field(
            'Titel',
            Html::input('name', $name, ['id' => 'name']),
            ['name' => 'name', 'required' => true]
        )
            . Form::field(
                'Kurzbeschreibung',
                Html::textarea('description', $description, 4, 35),
                ['name' => 'description', 'for' => '', 'hint' => 'Optional. Erscheint in Listen und Suchergebnissen.']
            );

        $html .= '<div class="field-group" x-show="typ === ' . self::TYPE_DOWNLOAD . '" x-cloak>'
            . Form::field('Download-Link', Html::input('link', $link, ['id' => 'link']), ['name' => 'link'])
            . '</div>';

        $html .= '<div class="field-group" x-show="typ === ' . self::TYPE_SPECIAL
            . ' && (typ2 === ' . self::SPECIAL_DOWNLOAD . ' || typ2 === ' . self::SPECIAL_BANNED . ')" x-cloak>'
            . Form::wide(
                '<div class="notice notice-warn">'
                . '<span>Platzhalter für Download-Seiten: <code>#id</code>, <code>#file</code>, <code>#button</code>.'
                . ' Für Sperr-Seiten: <code>#ip</code>, <code>#reason</code>, <code>#time</code>.</span></div>'
            )
            . '</div>';

        return $html . Form::wide(
            '<label class="field-label" for="content">Inhalt</label>'
            . '<textarea rows="22" cols="95" id="content" name="content">' . Html::e($content) . '</textarea>'
            . $this->xlsxImportBlock()
        );
    }

    /** Bild des Inhalts: hochladen, einfügen, entfernen. */
    private function imageSection(?object $item, string $image, int $type): string
    {
        $isEdit = $item !== null;

        $html = Form::field(
            'Bild hochladen',
            '<input type="file" name="image" id="image" accept="image/*">',
            ['name' => 'image', 'for' => 'image', 'hint' => 'Mit #item_picture bestimmen Sie die Position im Text.']
        );

        if ($image !== '' && $isEdit) {
            $html .= Form::field(
                'Aktuelles Bild',
                (string)make_contentimg('item', (int)$item->id, $image, 0)
                . '<label class="field-check">' . Html::checkbox('image_delete', false) . ' Aktuelles Bild löschen</label>',
                ['for' => '']
            );
        }

        if ($type !== self::TYPE_NEWS) {
            $html .= '<div class="field-group" x-show="typ !== ' . self::TYPE_NEWS . '" x-cloak>'
                . Form::check(
                    Html::checkbox('full_image', false),
                    'Originalbild zusätzlich speichern und verlinken'
                )
                . '</div>';
        }

        if (Editor::isEnabled() && $isEdit) {
            $html .= Form::wide($this->imageInsertBlock((int)$item->id));
        }

        return $html;
    }

    /** Sortierung, Autor, Datum und die Schalter für die Veröffentlichung. */
    private function publishingSection(
        int $sort,
        int $author,
        int $created,
        bool $isEdit,
        int $special,
        bool $available,
        bool $visible,
        bool $showuser,
        bool $rate,
        bool $comments
    ): string {
        $html = Form::field(
            'Sortierung',
            Html::input('sort', $sort, ['id' => 'sort', 'type' => 'number', 'style' => 'width:8rem']),
            ['name' => 'sort', 'hint' => 'Kleinere Zahlen stehen weiter oben.']
        )
            . Form::field('Erstellungsdatum', $this->createdAtFields($created, $isEdit), ['for' => ''])
            . '<div class="field-group" x-show="typ2 !== ' . self::SPECIAL_GUESTBOOK . '" x-cloak>'
            . Form::field(
                'Autor',
                Html::select('user', $this->userOptions(), $author, ['id' => 'user']),
                ['name' => 'user']
            )
            . '</div>';

        $html .= Form::check(
            Html::checkbox('available', $available),
            'Inhalt ist verfügbar',
            ['hint' => 'Administratoren haben auch ohne Freigabe Zugriff.']
        )
            . '<div class="field-group" x-show="typ !== ' . self::TYPE_SPECIAL . '" x-cloak>'
            . Form::check(
                Html::checkbox('visible', $visible),
                'Inhalt erscheint in Listen'
            )
            . '</div>'
            . '<div class="field-group" x-show="typ2 !== ' . self::SPECIAL_GUESTBOOK . '" x-cloak>'
            . Form::check(Html::checkbox('showuser', $showuser), '"Geschrieben von ..." anzeigen')
            . '<div class="field-group" x-show="typ2 !== ' . self::SPECIAL_BANNED . '" x-cloak>'
            . Form::check(Html::checkbox('rate', $rate), 'Inhalt darf bewertet werden')
            . Form::check(Html::checkbox('comments', $comments), 'Inhalt darf kommentiert werden')
            . '</div></div>';

        return $html;
    }

    /** Aktionen im Kopf des Editors: Ansehen, Versionen, Editor umschalten. */
    private function editorActions(?object $item): string
    {
        $html = '';

        if ($item !== null) {
            $html .= Components::secondary('Seite ansehen', 'index.php?item=' . (int)$item->id, 'eye');
            if (recover_item((int)$item->id)) {
                $html .= Components::secondary(
                    'Frühere Fassungen',
                    Html::url('item_recover', ['item' => (int)$item->id]),
                    'clock'
                );
            }
        }

        // Der grafische Editor wird über die Adresse umgeschaltet, damit der
        // Kopfbereich die passenden Skripte lädt
        $params = $item !== null ? ['edit' => (int)$item->id] : ['new' => 'yes'];
        $params['editor'] = Editor::isEnabled() ? 0 : 1;

        return $html . Components::secondary(
            Editor::isEnabled() ? 'Grafischen Editor ausschalten' : 'Grafischen Editor einschalten',
            $this->url($params),
            'edit'
        );
    }

    /** Hinweis, wenn die Struktur für einen Inhalt noch fehlt. */
    private function missingStructureNotice(): string
    {
        if ($this->categoryOptions() !== []) {
            return '';
        }

        return '<div class="notice notice-warn">' . Icons::render('warning')
            . '<span>Es gibt noch keine Kategorie. Wählen Sie als Typ "Spezialseite" oder '
            . '<a href="' . Html::e(Html::url('cat', ['new' => 'yes'])) . '">legen Sie zuerst eine Kategorie an</a>.'
            . '</span></div>';
    }

    private function createdAtFields(int $timestamp, bool $isEdit): string
    {
        return '<span class="field-inline">'
            . Html::input('create_at_date', date('d.m.Y', $timestamp), [
                'id' => 'create_at_date',
                'maxlength' => 10,
                'style' => 'width:8rem',
                'disabled' => 'disabled',
            ])
            . Html::input('create_at_time', date('H:i', $timestamp), [
                'id' => 'create_at_time',
                'maxlength' => 5,
                'style' => 'width:6rem',
                'disabled' => 'disabled',
            ])
            . '<label class="field-check">'
            . '<input type="checkbox" onclick="refresh_create()" id="create_at_use" name="create_at_use" value="1" checked> '
            . ($isEdit ? 'Nicht verändern' : 'Automatisch') . '</label>'
            . '</span>';
    }

    private function xlsxImportBlock(): string
    {
        return '<fieldset class="import-box">'
            . '<legend>Tabelle übernehmen</legend>'
            . '<input type="file" id="xlsx_file_picker" accept=".xlsx" style="display:none">'
            . '<button type="button" class="btn-secondary"'
            . ' onclick="document.getElementById(\'xlsx_file_picker\').click()">XLSX-Inhalt importieren</button>'
            . '<span id="xlsx_status" data-token="' . Html::e(\Pms\Backend\Support\Csrf::token()) . '"></span>'
            . '<div class="field-hint">Die Tabelle wird als Rohtext eingefügt, Spalten durch Leerzeichen getrennt.</div>'
            . '</fieldset>'
            . '<script type="text/javascript" src="js/admin-xlsx-import.js"></script>';
    }

    /** Bereich zum Einfügen eines Bildes in den Text (nur mit TinyMCE). */
    private function imageInsertBlock(int $itemId): string
    {
        return Html::hidden('next', '')
            . Html::hidden('add_image', '')
            . Html::hidden('item', $itemId)
            . Html::hidden('drag_name', '')
            . Html::hidden('drag_data', '')
            . '<fieldset class="import-box"><legend>Bild in den Text einfügen</legend>'
            . '<input type="file" id="image_file_picker" accept=".jpg,.jpeg,.png,.gif" style="display:none">'
            . '<button type="button" class="btn-secondary"'
            . ' onclick="document.getElementById(\'image_file_picker\').click()">Bild auswählen</button>'
            . '<span id="image_status"></span>'
            . '<div class="drop_zone" id="drop_zone">oder eine Bilddatei hierher ziehen</div>'
            . '</fieldset>';
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
        }

        $name = Request::string('name');
        if ($name === '') {
            Errors::add('name', 'Bitte geben Sie einen Titel an.');
        }

        if ($type === self::TYPE_SPECIAL) {
            if ($special <= 0) {
                Errors::add('typ2', 'Bitte wählen Sie die Art des Spezialinhalts.');
            }
        } elseif (!$this->placementIsValid($cat, $subcat)) {
            Errors::add('subcat', 'Bitte wählen Sie eine Unterkategorie dieser Kategorie.');
        }

        if (Errors::has()) {
            return $this->editor($this->valuesFromRequest());
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
            return $this->editor($this->valuesFromRequest());
        }

        $this->storeImage($id, $type);

        Flash::success('Inhalt erfolgreich gespeichert! <a href="index.php?item=' . $id . '">Inhalt anzeigen</a>');

        // Nach dem Speichern zum Bild-Dialog, zurück in den Editor oder zur Liste
        if (Request::string('next') === 'image') {
            return $this->imagePicker($id);
        }
        if (Request::string('item_step2') === 'Übernehmen & Schließen') {
            // Zurück in dieselbe Auswahl, aus der der Inhalt geöffnet wurde
            $back = $cat > 0 ? ['cat' => $cat] : [];
            if ($cat > 0 && $subcat > 0) {
                $back['subcat'] = $subcat;
            }
            $this->redirect($back);
        }

        $values = $this->valuesFromRequest();
        $values['id'] = $id;
        return $this->editor($values);
    }

    /** Vom Benutzer gesetztes Erstellungsdatum, sonst null. */
    /** Gehört die Unterkategorie zur gewählten Kategorie? */
    private function placementIsValid(int $cat, int $subcat): bool
    {
        if ($cat <= 0 || $subcat <= 0) {
            return false;
        }

        return Db::first(
            'SELECT id FROM ' . Db::table('subcat') . ' WHERE id = :id AND cat = :cat',
            ['id' => $subcat, 'cat' => $cat]
        ) !== null;
    }

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
        return $this->editor($this->valuesFromItem($item));
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
        /** @var array<int, string> $contentTypes */
        $contentTypes = $GLOBALS['content_typ'] ?? [];
        $categories = $this->categoryOptions();
        $filterCat = $this->filterCat();
        $filterSubcat = $this->filterSubcat();
        $typ = Request::queryInt('typ', -1);

        $list = Listing::from('item')
            ->searchIn(['name', 'description'])
            ->sortableBy([
                'id' => 'id',
                'name' => 'LOWER(name)',
                'typ' => 'typ',
                'sort' => 'sort',
                'time' => 'time',
                'available' => 'available',
            ])
            ->orderedBy('sort, name')
            ->keep('cat', $filterCat > 0 ? $filterCat : '')
            ->keep('subcat', $filterSubcat > 0 ? $filterSubcat : '')
            ->keep('typ', $typ < 0 ? '' : (string)$typ);

        if ($filterCat > 0) {
            $list->where('cat = :cat', ['cat' => $filterCat]);
        }
        if ($filterSubcat > 0) {
            $list->where('subcat = :subcat', ['subcat' => $filterSubcat]);
        }
        if ($typ >= 0) {
            $list->where('typ = :typ', ['typ' => $typ]);
        }

        $list->load();

        $subcategories = $this->subcategoryNames();

        $rows = [];
        foreach ($list->rows as $index => $item) {
            $sort = $list->isDefaultOrder()
                ? Sorting::cell($this->action(), $list->rows[$index - 1] ?? null, $item, $list->rows[$index + 1] ?? null)
                : Html::e((string)(int)$item->sort);

            // Spezialseiten lassen sich nicht kopieren - es gibt sie je Art nur einmal
            $copy = (int)$item->typ === self::TYPE_SPECIAL
                ? []
                : [Components::action('copy', $this->url(['do_copy' => (int)$item->id]), 'Kopie erstellen')];

            $rows[] = [
                (string)(int)$item->id,
                Html::e((string)$item->name),
                Components::chip($contentTypes[(int)$item->typ] ?? '', 'accent'),
                Html::e($categories[(int)$item->cat] ?? ''),
                Html::e($subcategories[(int)$item->subcat] ?? ''),
                $sort,
                Html::date($item->time),
                Components::booleanChip($item->available, 'Verfügbar', 'Entwurf'),
                $this->rowActions((int)$item->id, $copy),
            ];
        }

        $filters = [[
            'name' => 'cat',
            'label' => 'Kategorie',
            'options' => [0 => 'Alle Kategorien'] + $categories,
            'value' => $filterCat,
        ]];

        // Die Unterkategorie ist erst sinnvoll, wenn eine Kategorie feststeht
        if ($filterCat > 0) {
            $filters[] = [
                'name' => 'subcat',
                'label' => 'Unterkategorie',
                'options' => [0 => 'Alle Unterkategorien'] + $this->subcategoryOptions($filterCat),
                'value' => $filterSubcat,
            ];
        }

        if ($contentTypes !== []) {
            $filters[] = [
                'name' => 'typ',
                'label' => 'Inhaltstyp',
                'options' => [-1 => 'Alle Typen'] + $contentTypes,
                'value' => $typ,
            ];
        }

        return Components::pageHeader(
            'Inhalte',
            'Alle Textseiten der Website.',
            Components::primary('Inhalt hinzufügen', $this->url($this->newItemParams($filterCat, $filterSubcat)))
            . $this->restoreAction()
        )
            . Components::toolbar($this->action(), $list, $filters, 'Titel oder Beschreibung suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'name', 'label' => 'Name', 'class' => 'cell-title'],
                    ['key' => 'typ', 'label' => 'Typ'],
                    ['label' => 'In Kategorie'],
                    ['label' => 'In Unterkategorie'],
                    ['key' => 'sort', 'label' => 'Sortierung'],
                    ['key' => 'time', 'label' => 'Erstellt am'],
                    ['key' => 'available', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'In dieser Auswahl sind keine Inhalte vorhanden.' : 'Es sind keine Inhalte angelegt.'
            )
            . Components::pagination($list, $this->action());
    }

    /**
     * Ein neuer Inhalt startet in der Auswahl, die gerade gefiltert ist.
     *
     * @return array<string, string|int>
     */
    private function newItemParams(int $cat, int $subcat): array
    {
        $params = ['new' => 'yes'];
        if ($cat > 0) {
            $params['cat'] = $cat;
        }
        if ($subcat > 0) {
            $params['subcat'] = $subcat;
        }
        return $params;
    }

    /** Wiederherstellen ist nur möglich, wenn es überhaupt Sicherungen gibt. */
    private function restoreAction(): string
    {
        $backups = get_backups();
        if (!is_array($backups) || $backups === []) {
            return '<span class="btn btn-secondary disabled" title="Es liegen keine Sicherungen vor">'
                . 'Gelöschten Inhalt wiederherstellen</span>';
        }
        return Components::secondary('Gelöschten Inhalt wiederherstellen', Html::url('item_restore'), 'refresh');
    }

    /**
     * Alle Unterkategorien als id => Name, damit die Übersicht nicht je Zeile
     * eine eigene Abfrage braucht.
     *
     * @return array<int, string>
     */
    private function subcategoryNames(): array
    {
        $names = [];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('subcat')) as $row) {
            $names[(int)$row->id] = (string)$row->name;
        }
        return $names;
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
