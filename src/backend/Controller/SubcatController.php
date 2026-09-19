<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\EntityImage;
use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Pms\Backend\View\Components;
use Pms\Backend\View\Form;

/**
 * Unterkategorien.
 */
final class SubcatController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'subcat';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('subcat')) {
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

        Sorting::handleRequest('subcat');

        return $this->overview();
    }

    /** @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $name = Request::string('name');
        $id = Request::int('id');
        $cat = Request::int('uppcat');

        $data = [
            'name' => $name,
            'description' => Request::text('description'),
            'sort' => Request::int('sort', 1000),
            'cat' => $cat,
            'available' => Request::checkbox('available'),
            'jump' => Request::checkbox('jump'),
            'list' => Request::string('list'),
        ];
        $entered = (object)($data + ['id' => $id, 'image' => '']);

        if ($name === '') {
            Errors::add('name', 'Bitte geben Sie eine Bezeichnung an.');
            return $entered;
        }
        if ($cat <= 0) {
            Errors::add('uppcat', 'Bitte wählen Sie eine Kategorie.');
            return $entered;
        }

        if ($id > 0 && Request::checkbox('image_delete')) {
            EntityImage::delete('subcat', $id, (string)from_db('subcat', $id, 'image'));
            $data['image'] = '';
        }

        if ($id > 0) {
            $success = Db::update('subcat', $id, $data);
            // Inhalte der Unterkategorie folgen ihr in die neue Kategorie
            Db::execute(
                'UPDATE ' . Db::table('item') . ' SET cat = :cat WHERE subcat = :subcat',
                ['cat' => $cat, 'subcat' => $id]
            );
        } else {
            $id = Db::insert('subcat', $data);
            $success = $id > 0;
        }

        if (!$success) {
            Flash::error('Fehler beim Speichern der Unterkategorie!');
            return $entered;
        }

        $extension = EntityImage::store('subcat', $id);
        if ($extension !== null) {
            Db::update('subcat', $id, ['image' => $extension]);
        }

        Flash::success('Unterkategorie erfolgreich gespeichert!');
        $this->redirect();
    }

    /** Entfernt eine Unterkategorie samt Inhalten und Kommentaren. */
    private function delete(int $id): never
    {
        if (!Auth::isSuperAdmin()) {
            Flash::error('Sie haben dafür nicht genügend Rechte!');
            $this->redirect();
        }

        foreach (Db::select('SELECT id, image FROM ' . Db::table('item') . ' WHERE subcat = :subcat', ['subcat' => $id]) as $item) {
            del_contentimg('item', (int)$item->id, $item->image);
            Db::execute('DELETE FROM ' . Db::table('comments') . ' WHERE item = :item', ['item' => (int)$item->id]);
        }

        EntityImage::delete('subcat', $id, (string)from_db('subcat', $id, 'image'));
        Db::execute('DELETE FROM ' . Db::table('item') . ' WHERE subcat = :subcat', ['subcat' => $id]);
        Db::delete('subcat', $id);

        Flash::success('Unterkategorie erfolgreich entfernt!');
        $this->redirect();
    }

    private function deleteConfirmation(int $id): string
    {
        if (!Auth::isSuperAdmin()) {
            Flash::error('Sie haben dafür nicht genügend Rechte!');
            return $this->overview();
        }

        $subcat = $this->find($id);
        if ($subcat === null) {
            Flash::error('Die Unterkategorie wurde nicht gefunden.');
            return $this->overview();
        }

        return $this->confirmDelete(
            $id,
            'Löschen von Unterkategorie bestätigen: ' . $subcat->name,
            'Hinweis: Es werden ALLE EINTRÄGE ENTFERNT!'
        );
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('subcat') . ' WHERE id = :id', ['id' => $id]);
    }

    /** Auswahlfeld aller Kategorien. */
    private function categoryOptions(): array
    {
        $options = [];
        foreach (Db::select('SELECT id, name FROM ' . Db::table('cat') . ' ORDER BY sort, name') as $cat) {
            $options[(int)$cat->id] = (string)$cat->name;
        }
        return $options;
    }

    private function form(?object $subcat): string
    {
        $isEdit = $subcat !== null;
        $id = $isEdit ? (int)$subcat->id : 0;

        $selectedCat = $isEdit ? (int)$subcat->cat : Request::queryInt('cat');
        $image = $isEdit ? (string)$subcat->image : '';
        if ($isEdit && $image === '') {
            $image = (string)EntityImage::detect('subcat', $id);
        }

        $general = Form::field(
            'Bezeichnung',
            Html::input('name', $isEdit ? $subcat->name : '', ['id' => 'name']),
            ['name' => 'name', 'required' => true]
        )
            . Form::field(
                'Beschreibung',
                Html::textarea('description', $isEdit ? $subcat->description : '', 5, 35),
                ['name' => 'description', 'for' => '']
            )
            . Form::field(
                'In Kategorie',
                Html::select('uppcat', $this->categoryOptions(), $selectedCat, ['id' => 'uppcat']),
                ['name' => 'uppcat', 'for' => 'uppcat', 'required' => true]
            )
            . Form::field(
                'Sortierung',
                Html::input('sort', $isEdit ? (int)$subcat->sort : 1000, ['id' => 'sort', 'type' => 'number']),
                ['name' => 'sort', 'hint' => 'Kleinere Zahlen stehen weiter oben.']
            );

        $lists = get_lists($isEdit ? $subcat->list : '');
        if ($lists) {
            $general .= Form::field('Listenansicht', (string)$lists, ['name' => 'list']);
        }

        $imageField = Form::field(
            'Bild',
            '<input type="file" name="image" id="image" accept="image/*">',
            ['name' => 'image', 'for' => 'image', 'hint' => 'Optional. Erscheint in der Übersicht der Unterkategorie.']
        );
        if ($image !== '') {
            $imageField .= Form::field(
                'Aktuelles Bild',
                (string)make_contentimg('subcat', $id, $image, 0)
                . '<label class="field-check">' . Html::checkbox('image_delete', false) . ' Aktuelles Bild löschen</label>',
                ['for' => '']
            );
        }

        $options = Form::check(
            Html::checkbox('available', !$isEdit || (bool)$subcat->available),
            'Unterkategorie verfügbar'
        )
            . Form::check(
                Html::checkbox('jump', $isEdit && (bool)$subcat->jump),
                'Bei nur einem Inhalt sofort auf diesen springen'
            );

        return Components::pageHeader($id > 0 ? 'Unterkategorie bearbeiten' : 'Unterkategorie erstellen')
            . Html::formOpen($this->action(), [], ['upload' => true])
            . Html::hidden('id', $id)
            . Form::card(
                Form::section('Allgemein', $general)
                . Form::section('Bild', $imageField)
                . Form::section('Verhalten', $options),
                Form::actions('subcat', 'Speichern', $this->url())
            )
            . Html::formClose();
    }

    private function overview(): string
    {
        $categories = $this->categoryOptions();

        $cat = Request::queryInt('cat');
        if ($cat > 0 && !isset($categories[$cat])) {
            $cat = 0;
        }

        $list = Listing::from('subcat')
            ->searchIn(['name', 'description'])
            ->sortableBy(['id' => 'id', 'name' => 'name', 'sort' => 'sort', 'available' => 'available'])
            ->orderedBy('sort, name')
            ->keep('cat', $cat > 0 ? $cat : '');

        if ($cat > 0) {
            $list->where('cat = :cat', ['cat' => $cat]);
        }

        $list->load();

        $rows = [];
        foreach ($list->rows as $index => $subcat) {
            $sort = $list->isDefaultOrder()
                ? Sorting::cell($this->action(), $list->rows[$index - 1] ?? null, $subcat, $list->rows[$index + 1] ?? null)
                : Html::e((string)(int)$subcat->sort);

            $rows[] = [
                (string)(int)$subcat->id,
                Html::e((string)$subcat->name),
                Html::e($categories[(int)$subcat->cat] ?? ''),
                $sort,
                Components::booleanChip($subcat->available, 'Verfügbar', 'Versteckt'),
                $this->rowActions((int)$subcat->id),
            ];
        }

        return Components::pageHeader(
            'Unterkategorien',
            'Die zweite Ebene: Unterkategorien gehören immer zu einer Kategorie.',
            Components::primary('Neue Unterkategorie', $this->url(['new' => 'yes'] + ($cat > 0 ? ['cat' => $cat] : [])))
        )
            . Components::toolbar($this->action(), $list, [[
                'name' => 'cat',
                'label' => 'Kategorie',
                'options' => [0 => 'Alle Kategorien'] + $categories,
                'value' => $cat,
            ]], 'Unterkategorie suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'name', 'label' => 'Name', 'class' => 'cell-title'],
                    ['label' => 'In Kategorie'],
                    ['key' => 'sort', 'label' => 'Sortierung'],
                    ['key' => 'available', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Keine Unterkategorie passt zur Suche.' : 'Es sind keine Unterkategorien angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
