<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Data\Db;
use Pms\Support\Auth;
use Pms\Support\Errors;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Listing;
use Pms\Support\Request;

/**
 * Hauptkategorien.
 */
final class CatController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'cat';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('cat')) {
            // save() leitet bei Erfolg weiter; sonst kommen die eingegebenen
            // Werte zurück und das Formular zeigt sie samt Fehlern erneut
            $entered = $this->save();
            if ($entered !== null) {
                return $this->form($entered);
            }
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            $this->delete($confirmed);
        }

        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            return $this->deleteConfirmation($delete);
        }

        if (Request::queryInt('edit') > 0 || Request::string('new') !== '') {
            return $this->form($this->find(Request::queryInt('edit')));
        }

        $this->handleSorting('cat');

        return $this->overview();
    }

    /** @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $data = [
            'name' => Request::string('name'),
            'sort' => Request::int('sort', 1000),
            'available' => Request::checkbox('available'),
            'list' => Request::string('list'),
        ];
        $entered = (object)($data + ['id' => Request::int('id')]);

        if ($data['name'] === '') {
            Errors::add('name', 'Bitte geben Sie einen Kategorienamen an.');
            return $entered;
        }

        $id = Request::int('id');
        $success = $id > 0 ? Db::update('cat', $id, $data) : Db::insert('cat', $data) > 0;

        if ($success) {
            Flash::success('Kategorie erfolgreich gespeichert!');
            $this->redirect();
        }

        Flash::error('Fehler beim Speichern der Kategorie!');
        return $entered;
    }

    /**
     * Entfernt eine Kategorie samt Unterkategorien, Inhalten und Kommentaren.
     */
    private function delete(int $id): never
    {
        if (!Auth::isSuperAdmin()) {
            Flash::error('Sie haben dafür nicht genügend Rechte!');
            $this->redirect();
        }

        $table = fn(string $name): string => Db::table($name);

        foreach (Db::select('SELECT id, image FROM ' . $table('subcat') . ' WHERE cat = :cat', ['cat' => $id]) as $subcat) {
            del_contentimg('subcat', (int)$subcat->id, $subcat->image);
        }
        foreach (Db::select('SELECT id, image FROM ' . $table('item') . ' WHERE cat = :cat', ['cat' => $id]) as $item) {
            del_contentimg('item', (int)$item->id, $item->image);
            Db::execute('DELETE FROM ' . $table('comments') . ' WHERE item = :item', ['item' => (int)$item->id]);
        }

        Db::execute('DELETE FROM ' . $table('item') . ' WHERE cat = :cat', ['cat' => $id]);
        Db::execute('DELETE FROM ' . $table('subcat') . ' WHERE cat = :cat', ['cat' => $id]);
        Db::delete('cat', $id);

        Flash::success('Kategorie erfolgreich entfernt!');
        $this->redirect();
    }

    private function deleteConfirmation(int $id): string
    {
        if (!Auth::isSuperAdmin()) {
            Flash::error('Sie haben dafür nicht genügend Rechte!');
            return $this->overview();
        }

        $cat = $this->find($id);
        if ($cat === null) {
            Flash::error('Die Kategorie wurde nicht gefunden.');
            return $this->overview();
        }

        return $this->confirmDelete(
            $id,
            'Löschen von Kategorie bestätigen: ' . $cat->name,
            'Hinweis: Es werden ALLE EINTRÄGE UND UNTERKATEGORIEN ENTFERNT!'
        );
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('cat') . ' WHERE id = :id', ['id' => $id]);
    }

    private function form(?object $cat): string
    {
        $isEdit = $cat !== null;
        $id = $isEdit ? (int)$cat->id : 0;

        $fields = Form::field(
            'Kategoriename',
            Html::input('name', $isEdit ? $cat->name : '', ['id' => 'name']),
            ['name' => 'name', 'required' => true]
        )
            . Form::field(
                'Sortierung',
                Html::input('sort', $isEdit ? (int)$cat->sort : 1000, ['id' => 'sort', 'type' => 'number']),
                ['name' => 'sort', 'hint' => 'Kleinere Zahlen stehen in der Navigation weiter oben.']
            );

        $lists = get_lists($isEdit ? $cat->list : '');
        if ($lists) {
            $fields .= Form::field('Listenansicht', (string)$lists, ['name' => 'list']);
        }

        $fields .= Form::check(
            Html::checkbox('available', !$isEdit || (bool)$cat->available),
            'Kategorie verfügbar',
            ['hint' => 'Versteckte Kategorien erscheinen im Frontend nicht.']
        );

        return Components::pageHeader($id > 0 ? 'Kategorie bearbeiten' : 'Kategorie erstellen')
            . Html::formOpen($this->action())
            . Html::hidden('id', $id)
            . Form::card(Form::section('', $fields), Form::actions('cat', 'Speichern', $this->url()))
            . Html::formClose();
    }

    private function overview(): string
    {
        $available = Request::queryInt('available', -1);

        $list = Listing::from('cat')
            ->searchIn(['name'])
            ->sortableBy(['id' => 'id', 'name' => 'name', 'sort' => 'sort', 'available' => 'available'])
            ->orderedBy('sort, name')
            ->keep('available', $available < 0 ? '' : (string)$available);

        if ($available >= 0) {
            $list->where('available = :available', ['available' => $available]);
        }

        $list->load();

        $rows = [];
        foreach ($list->rows as $index => $cat) {
            // Die Pfeile verschieben gegenüber dem Nachbarn - das ergibt nur
            // Sinn, solange die Liste nach der Sortiernummer geordnet ist
            $sort = $list->isDefaultOrder()
                ? Components::sortCell($this->action(), $list->rows[$index - 1] ?? null, $cat, $list->rows[$index + 1] ?? null)
                : Html::e((string)(int)$cat->sort);

            $rows[] = [
                (string)(int)$cat->id,
                Html::e((string)$cat->name),
                $sort,
                Components::booleanChip($cat->available, 'Verfügbar', 'Versteckt'),
                $this->rowActions((int)$cat->id),
            ];
        }

        return Components::pageHeader(
            'Kategorien',
            'Die oberste Ebene der Website-Struktur.',
            Components::primary('Neue Kategorie', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, [[
                'name' => 'available',
                'label' => 'Verfügbarkeit',
                'options' => [-1 => 'Alle', 1 => 'Nur verfügbare', 0 => 'Nur versteckte'],
                'value' => $available,
            ]], 'Kategorie suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'name', 'label' => 'Name', 'class' => 'cell-title'],
                    ['key' => 'sort', 'label' => 'Sortierung'],
                    ['key' => 'available', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Keine Kategorie passt zur Suche.' : 'Es sind keine Kategorien angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
