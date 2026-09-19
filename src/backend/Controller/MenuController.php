<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Http\Routes;
use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Pms\Backend\View\Components;
use Pms\Backend\View\Form;

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

    #[\Override]
    public function action(): string
    {
        return 'menu';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('menu')) {
            $this->save();
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

        $subcats = $values['cat'] > 0 ? $this->options('subcat', 'cat', $values['cat']) : [];
        $items = $values['subcat'] > 0 ? $this->options('item', 'subcat', $values['subcat']) : [];

        $state = [
            'url' => Routes::path('options_ajax'),
            'typ' => $values['typ'],
            'cat' => $values['cat'],
            'subcat' => $values['subcat'],
            'item' => $values['item'],
            'subcats' => self::optionList($subcats),
            'items' => self::optionList($items),
        ];

        $general = Form::field(
            'Name',
            Html::input('name', $values['name'], ['id' => 'name']),
            ['name' => 'name', 'required' => true]
        )
            . Form::field(
                'Sortierung',
                Html::input('sort', $values['sort'], ['id' => 'sort', 'type' => 'number']),
                ['name' => 'sort', 'hint' => 'Kleinere Zahlen stehen weiter vorne.']
            )
            . Form::field(
                'Sichtbar für',
                Html::select('usertyp', $this->userTypeOptions(), $values['usertyp'], ['id' => 'usertyp']),
                ['name' => 'usertyp']
            );

        $target = Form::field(
            'Verweist auf',
            Form::segmented('typ', self::TYPE_LABELS, $values['typ'], 'typ'),
            ['for' => '', 'name' => 'typ']
        )
            . $this->contentFields($values)
            . $this->pluginField($values)
            . $this->linkField($values)
            . '<div class="field-row field-row-wide" x-show="typ === ' . self::TYPE_PLACEHOLDER . '" x-cloak>'
            . '<p class="field-hint">Ein Platzhalter erscheint im Menü, lässt sich aber nicht anklicken.</p>'
            . '</div>';

        $options = Form::check(Html::checkbox('visible', (bool)$values['visible']), 'Eintrag ist sichtbar');
        if (!empty($GLOBALS['config_values']->menu_mode)) {
            $options .= Form::check(
                Html::checkbox('popup', (bool)$values['popup']),
                'Aufklappen ermöglichen',
                ['attributes' => ['x-show' => 'typ === ' . self::TYPE_CONTENT, 'x-cloak' => '']]
            );
        }

        return Components::pageHeader($isEdit ? 'Menüeintrag bearbeiten' : 'Menüeintrag erstellen')
            . Html::formOpen($this->action())
            . Html::hidden('id', $values['id'])
            . '<div x-data="linkedSelects(' . Html::e((string)json_encode($state)) . ')">'
            . Form::card(
                Form::section('Allgemein', $general)
                . Form::section('Ziel', $target)
                . Form::section('Anzeige', $options),
                Form::actions('menu', 'Speichern', $this->url())
            )
            . '</div>'
            . Html::formClose();
    }

    /**
     * Die Felder für den Verweis auf Kategorie, Unterkategorie und Inhalt.
     *
     * Die beiden unteren Ebenen werden nachgeladen, sobald die darüber
     * gewechselt wird.
     *
     * @param array<string, mixed> $values
     */
    private function contentFields(array $values): string
    {
        $show = ' x-show="typ === ' . self::TYPE_CONTENT . '" x-cloak';

        return '<div class="field-group"' . $show . '>'
            . Form::field(
                'Kategorie',
                Html::select('cat', $this->options('cat'), (int)$values['cat'], [
                    'id' => 'cat',
                    'x-model.number' => 'cat',
                    '@change' => 'catChanged()',
                ]),
                ['name' => 'cat']
            )
            . Form::field(
                'Unterkategorie',
                self::linkedSelect('subcat', 'Keine Unterkategorie', 'subcatChanged()'),
                ['name' => 'subcat', 'hint' => 'Optional. Ohne Auswahl verweist der Eintrag auf die Kategorie.']
            )
            . Form::field(
                'Inhalt',
                self::linkedSelect('item', 'Kein einzelner Inhalt'),
                ['name' => 'item', 'hint' => 'Optional. Mit Auswahl verweist der Eintrag direkt auf diesen Inhalt.']
            )
            . '</div>';
    }

    /** @param array<string, mixed> $values */
    private function pluginField(array $values): string
    {
        return '<div class="field-group" x-show="typ === ' . self::TYPE_PLUGIN . '" x-cloak>'
            . Form::field(
                'Plugin',
                Html::select('plugin', $this->pluginOptions(), (string)$values['plugin'], ['id' => 'plugin']),
                ['name' => 'plugin']
            )
            . '</div>';
    }

    /** @param array<string, mixed> $values */
    private function linkField(array $values): string
    {
        return '<div class="field-group" x-show="typ === ' . self::TYPE_LINK . '" x-cloak>'
            . Form::field(
                'Link-Code',
                '<textarea name="extern" rows="2" cols="60">'
                . Html::e(my_stripslashes((string)$values['extern'])) . '</textarea>',
                [
                    'name' => 'extern',
                    'for' => '',
                    'hint' => 'Beispiel: a href="http://www.beispiel.de/" target="_blank"',
                ]
            )
            . '</div>';
    }

    /**
     * Ein Auswahlfeld, dessen Einträge das Skript beisteuert und beim
     * Wechsel der Ebene darüber austauscht. Der Ausgangsbestand steht im
     * Zustand, den linkedSelects() bekommt - deshalb stehen die Einträge
     * hier nicht zusätzlich im Markup.
     */
    private static function linkedSelect(string $name, string $emptyLabel, string $onChange = ''): string
    {
        $change = $onChange === '' ? '' : ' @change="' . Html::e($onChange) . '"';

        return '<select name="' . Html::e($name) . '" id="' . Html::e($name) . '"'
            . ' x-model.number="' . Html::e($name) . '"' . $change . '>'
            . '<option value="0">' . Html::e($emptyLabel) . '</option>'
            . '<template x-for="option in ' . Html::e($name) . 's" :key="option.value">'
            . '<option :value="option.value" x-text="option.label"></option>'
            . '</template>'
            . '</select>';
    }

    /**
     * Wandelt id => Name in die Form, die das Skript erwartet.
     *
     * @param array<int, string> $options
     * @return list<array{value: int, label: string}>
     */
    private static function optionList(array $options): array
    {
        $list = [];
        foreach ($options as $value => $label) {
            $list[] = ['value' => (int)$value, 'label' => $label];
        }
        return $list;
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
        $typ = Request::queryInt('typ', -1);

        $list = Listing::from('menu')
            ->searchIn(['name'])
            ->sortableBy(['name' => 'name', 'sort' => 'sort', 'typ' => 'typ', 'visible' => 'visible'])
            ->orderedBy('sort, name')
            ->keep('typ', $typ < 0 ? '' : (string)$typ);

        if ($typ >= 0) {
            $list->where('typ = :typ', ['typ' => $typ]);
        }

        $list->load();

        $rows = [];
        foreach ($list->rows as $index => $entry) {
            $sort = $list->isDefaultOrder()
                ? Sorting::cell($this->action(), $list->rows[$index - 1] ?? null, $entry, $list->rows[$index + 1] ?? null)
                : Html::e((string)(int)$entry->sort);

            $rows[] = [
                Html::e((string)$entry->name),
                $sort,
                Components::chip(self::TYPE_LABELS[(int)$entry->typ] ?? '', 'accent'),
                Components::booleanChip($entry->visible, 'Sichtbar', 'Verborgen'),
                $this->rowActions((int)$entry->id),
            ];
        }

        return Components::pageHeader(
            'Menüverwaltung',
            'Die Einträge der Hauptnavigation im Frontend.',
            Components::primary('Neuer Menüeintrag', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, [[
                'name' => 'typ',
                'label' => 'Verweistyp',
                'options' => [-1 => 'Alle Verweistypen'] + self::TYPE_LABELS,
                'value' => $typ,
            ]], 'Menüeintrag suchen')
            . Components::table(
                [
                    ['key' => 'name', 'label' => 'Name', 'class' => 'cell-title'],
                    ['key' => 'sort', 'label' => 'Sortierung'],
                    ['key' => 'typ', 'label' => 'Link auf'],
                    ['key' => 'visible', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Kein Menüeintrag passt zur Suche.' : 'Es sind keine Menüeinträge angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
