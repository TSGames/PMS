<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;

/**
 * Menüeinträge der Website.
 *
 * Ein Eintrag verweist entweder auf einen Inhalt, auf ein eingebautes
 * Plugin, auf einen frei formulierten Link oder ist ein reiner Platzhalter.
 */
final class MenuController extends Controller
{
    private const TYPE_CONTENT = 0;
    private const TYPE_PLUGIN = 1;
    private const TYPE_LINK = 2;
    private const TYPE_PLACEHOLDER = 3;

    private const TYPE_LABELS = [
        self::TYPE_CONTENT => 'Content',
        self::TYPE_PLUGIN => 'Plugin',
        self::TYPE_LINK => 'Link-Code',
        self::TYPE_PLACEHOLDER => 'Platzhalter',
    ];

    public function action(): string
    {
        return 'menu';
    }

    public function handle(): string
    {
        if (Request::submitted('menu')) {
            $this->save();
        }

        // Die Auswahllisten hängen voneinander ab: erst Kategorie wählen,
        // dann "Aktualisieren", dann Unterkategorie und Inhalt
        if (Request::submitted('menu_refresh')) {
            return $this->form(null, true);
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            if (Db::delete('menu', $confirmed)) {
                Flash::success('Eintrag erfolgreich entfernt');
            } else {
                Flash::error('Eintrag konnte nicht entfernt werden');
            }
            $this->redirect();
        }

        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            $entry = $this->find($delete);
            if ($entry === null) {
                Flash::error('Der Menüeintrag wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->confirmDelete($delete, 'Soll der Menüeintrag "' . $entry->name . '" gelöscht werden?');
        }

        if (Request::queryInt('edit') > 0 || Request::string('new') !== '') {
            return $this->form($this->find(Request::queryInt('edit')));
        }

        Sorting::handleRequest('menu');

        return $this->overview();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $name = Request::string('name');
        if ($name === '') {
            Flash::error('Bitte geben Sie eine Bezeichnung an.');
            return;
        }

        $type = Request::int('typ');
        $cat = Request::int('cat');
        $subcat = Request::int('subcat');
        $item = Request::int('item');

        // Verweise nur übernehmen, wenn sie zusammenpassen
        if ($type === self::TYPE_CONTENT) {
            if ($subcat > 0 && !$this->belongsTo('subcat', $subcat, 'cat', $cat)) {
                Flash::error('Die gewählte Unterkategorie gehört nicht zur gewählten Kategorie.');
                return;
            }
            if ($item > 0 && !$this->belongsTo('item', $item, 'subcat', $subcat)) {
                Flash::error('Der gewählte Inhalt gehört nicht zur gewählten Unterkategorie.');
                return;
            }
        }

        $data = [
            'name' => $name,
            'sort' => Request::int('sort', 1000),
            'typ' => $type,
            'cat' => $cat,
            'subcat' => $subcat,
            'item' => $item,
            'usertyp' => Request::int('usertyp'),
            'plugin' => Request::string('plugin'),
            'extern' => Request::text('extern'),
            'visible' => Request::checkbox('visible'),
            'popup' => Request::checkbox('popup'),
        ];

        $id = Request::int('id');
        $success = $id > 0 ? Db::update('menu', $id, $data) : Db::insert('menu', $data) > 0;

        if ($success) {
            Flash::success('Menüeintrag erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern des Menü-Eintrags!');
    }

    private function belongsTo(string $table, int $id, string $column, int $parent): bool
    {
        return Db::first(
            'SELECT id FROM ' . Db::table($table) . ' WHERE id = :id AND ' . $column . ' = :parent',
            ['id' => $id, 'parent' => $parent]
        ) !== null;
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('menu') . ' WHERE id = :id', ['id' => $id]);
    }

    /**
     * Formular zum Anlegen und Bearbeiten.
     *
     * @param bool $fromRequest Werte aus der laufenden Anfrage übernehmen
     *                          (nach "Aktualisieren")
     */
    private function form(?object $entry, bool $fromRequest = false): string
    {
        $isEdit = $entry !== null || ($fromRequest && Request::int('id') > 0);

        if ($fromRequest) {
            $values = [
                'id' => Request::int('id'),
                'name' => Request::string('name'),
                'sort' => Request::int('sort', 1000),
                'typ' => Request::int('typ'),
                'cat' => Request::int('cat'),
                'subcat' => Request::int('subcat'),
                'item' => Request::int('item'),
                'usertyp' => Request::int('usertyp'),
                'plugin' => Request::string('plugin'),
                'extern' => Request::text('extern'),
                'visible' => Request::checkbox('visible'),
                'popup' => Request::checkbox('popup'),
            ];
        } elseif ($entry !== null) {
            $values = [
                'id' => (int)$entry->id,
                'name' => (string)$entry->name,
                'sort' => (int)$entry->sort,
                'typ' => (int)$entry->typ,
                'cat' => (int)$entry->cat,
                'subcat' => (int)$entry->subcat,
                'item' => (int)$entry->item,
                'usertyp' => (int)$entry->usertyp,
                'plugin' => (string)$entry->plugin,
                'extern' => (string)$entry->extern,
                'visible' => (int)$entry->visible,
                'popup' => (int)$entry->popup,
            ];
            // Verweist der Eintrag auf einen Inhalt, dessen Kategorie ableiten
            if ($values['item'] > 0) {
                $values['cat'] = (int)from_db('item', $values['item'], 'cat');
                $values['subcat'] = (int)from_db('item', $values['item'], 'subcat');
            } elseif ($values['subcat'] > 0) {
                $values['cat'] = (int)from_db('subcat', $values['subcat'], 'cat');
            }
        } else {
            $values = [
                'id' => 0, 'name' => '', 'sort' => 1000, 'typ' => self::TYPE_CONTENT,
                'cat' => 0, 'subcat' => 0, 'item' => 0, 'usertyp' => 0,
                'plugin' => '', 'extern' => '', 'visible' => 1, 'popup' => 0,
            ];
        }

        $html = Html::formOpen($this->action())
            . Html::heading($isEdit ? 'Menüeintrag bearbeiten' : 'Menüeintrag erstellen')
            . Html::hidden('id', $values['id'])
            . '<table>'
            . Html::field('Name', Html::input('name', $values['name']))
            . Html::field('Sortierung', Html::input('sort', $values['sort']))
            . Html::field('Sichtbar für', Html::select('usertyp', $this->userTypeOptions(), $values['usertyp']));

        // Verweis auf Kategorie/Unterkategorie/Inhalt
        $html .= '<tr><td colspan="2">' . $this->radio('typ', self::TYPE_CONTENT, $values['typ'])
            . ' Verlinkung zu Kategorie/Inhalt-Liste</td></tr>'
            . Html::field(
                'Kategorie',
                Html::select('cat', $this->options('cat'), $values['cat'], ['onclick' => "check_radio('typ', 0)"])
                . ' <input type="submit" onclick="check_radio(\'typ\', 0)" name="menu_refresh" value="Aktualisieren">'
            )
            . Html::field(
                'Unterkategorie',
                Html::select(
                    'subcat',
                    [0 => '[Keine]'] + $this->options('subcat', 'cat', $values['cat']),
                    $values['subcat'],
                    ['onclick' => "check_radio('typ', 0)"]
                )
            );

        if ($values['subcat'] > 0) {
            $html .= Html::field(
                'Inhalt-Objekt',
                Html::select(
                    'item',
                    [0 => '[Keins]'] + $this->options('item', 'subcat', $values['subcat']),
                    $values['item'],
                    ['onclick' => "check_radio('typ', 0)"]
                )
            );
        }

        if (!empty($GLOBALS['config_values']->menu_mode)) {
            $html .= '<tr><td></td><td>'
                . Html::checkbox('popup', (bool)$values['popup'], 'Aufklappen ermöglichen')
                . '</td></tr>';
        }

        // Verweis auf ein Plugin
        $html .= '<tr><td colspan="2">' . $this->radio('typ', self::TYPE_PLUGIN, $values['typ'])
            . ' Auf integriertes Plugin verweisen</td></tr>'
            . Html::field('Plugin', Html::select('plugin', $this->pluginOptions(), $values['plugin'], ['onclick' => "check_radio('typ', 1)"]));

        // Freier Link-Code
        $html .= '<tr><td colspan="2">' . $this->radio('typ', self::TYPE_LINK, $values['typ'])
            . ' Link-Code verwenden</td></tr>'
            . '<tr><td>Link-Code:</td><td>'
            . '<textarea onclick="check_radio(\'typ\', 2)" name="extern" rows="2" cols="60">'
            . Html::e(my_stripslashes($values['extern'])) . '</textarea>'
            . '<div class="example">Beispiel: a href="http://www.beispiel.de/" target="_blank"</div>'
            . '</td></tr>';

        // Platzhalter ohne Funktion
        $html .= '<tr><td colspan="2">' . $this->radio('typ', self::TYPE_PLACEHOLDER, $values['typ'])
            . ' Nur Platzhalter (Keine Link-Funktion)</td></tr>';

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('visible', (bool)$values['visible'], 'Eintrag ist sichtbar')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="menu" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();
    }

    private function radio(string $name, int $value, int $current): string
    {
        return '<input type="radio" name="' . Html::e($name) . '" value="' . $value . '"'
            . ($value === $current ? ' checked' : '') . '>';
    }

    /** Auswahlmöglichkeiten einer Tabelle, optional auf einen Elternwert begrenzt. */
    private function options(string $table, string $parentColumn = '', int $parentId = 0): array
    {
        $sql = 'SELECT id, name FROM ' . Db::table($table);
        $params = [];
        if ($parentColumn !== '') {
            $sql .= ' WHERE ' . $parentColumn . ' = :parent';
            $params['parent'] = $parentId;
        }

        $options = [];
        foreach (Db::select($sql . ' ORDER BY sort, name', $params) as $row) {
            $options[(int)$row->id] = (string)$row->name;
        }
        return $options;
    }

    private function userTypeOptions(): array
    {
        $options = [];
        foreach (($GLOBALS['user_typ'] ?? []) as $type => $label) {
            $options[$type + 1] = $label;
        }
        return $options;
    }

    private function pluginOptions(): array
    {
        $options = [];
        foreach (($GLOBALS['plugin_intern'] ?? []) as $index => $plugin) {
            $options[$index] = $plugin[0];
        }
        return $options;
    }

    private function overview(): string
    {
        $entries = Db::select('SELECT * FROM ' . Db::table('menu') . ' ORDER BY sort, name');

        $rows = [];
        foreach ($entries as $index => $entry) {
            $rows[] = [
                Html::e((string)$entry->name),
                Sorting::cell($this->action(), $entries[$index - 1] ?? null, $entry, $entries[$index + 1] ?? null),
                Html::e(self::TYPE_LABELS[(int)$entry->typ] ?? ''),
                Html::yesNo($entry->visible),
                $this->editLink((int)$entry->id),
                $this->deleteLink((int)$entry->id),
            ];
        }

        return Html::heading('Menüverwaltung')
            . '<div class="action-section">'
            . Html::button('Neuer Menüeintrag', $this->url(['new' => 'yes']))
            . '</div>'
            . Html::table(
                ['Name', 'Sortierung', 'Link auf', 'Sichtbar', 'Bearbeiten', 'Löschen'],
                $rows,
                'Es sind keine Menüeinträge angelegt.'
            );
    }
}
