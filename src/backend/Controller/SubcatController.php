<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\EntityImage;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Pms\Backend\View\Components;

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
            $this->save();
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
            return;
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

        $html = Html::formOpen($this->action(), [], ['upload' => true])
            . Html::heading($isEdit ? 'Unterkategorie bearbeiten' : 'Unterkategorie erstellen')
            . Html::hidden('id', $id)
            . '<table>'
            . Html::field('Bezeichnung', Html::input('name', $isEdit ? $subcat->name : '', ['size' => 36]))
            . Html::field('Beschreibung', Html::textarea('description', $isEdit ? $subcat->description : '', 5, 35))
            . Html::field('Bild (optional)', '<input type="file" name="image" size="36">');

        if ($image !== '') {
            $html .= '<tr><td>' . make_contentimg('subcat', $id, $image, 0) . '</td><td>'
                . Html::checkbox('image_delete', false, 'Aktuelles Bild löschen')
                . '</td></tr>';
        }

        $html .= Html::field('Sortierung', Html::input('sort', $isEdit ? (int)$subcat->sort : 1000, ['size' => 7]))
            . Html::field('In Kategorie', Html::select('uppcat', $this->categoryOptions(), $selectedCat));

        $lists = get_lists($isEdit ? $subcat->list : '');
        if ($lists) {
            $html .= Html::field('Listenansicht', $lists);
        }

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('available', !$isEdit || (bool)$subcat->available, 'Unterkategorie verfügbar')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('jump', $isEdit && (bool)$subcat->jump, 'Wenn nur 1 Inhalt vorhanden, sofort auf diesen springen')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="subcat" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
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
