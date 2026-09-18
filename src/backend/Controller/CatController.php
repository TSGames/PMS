<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;

/**
 * Hauptkategorien.
 */
final class CatController extends Controller
{
    public function action(): string
    {
        return 'cat';
    }

    public function handle(): string
    {
        if (Request::submitted('cat')) {
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

        Sorting::handleRequest('cat');

        return $this->overview();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $name = Request::string('name');
        if ($name === '') {
            Flash::error('Bitte geben Sie einen Kategorienamen an.');
            return;
        }

        $data = [
            'name' => $name,
            'sort' => Request::int('sort', 1000),
            'available' => Request::checkbox('available'),
            'list' => Request::string('list'),
        ];

        $id = Request::int('id');
        $success = $id > 0 ? Db::update('cat', $id, $data) : Db::insert('cat', $data) > 0;

        if ($success) {
            Flash::success('Kategorie erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern der Kategorie!');
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

        $html = Html::formOpen($this->action())
            . Html::heading($isEdit ? 'Kategorie bearbeiten' : 'Kategorie erstellen')
            . Html::hidden('id', $isEdit ? (int)$cat->id : 0)
            . '<table>'
            . Html::field('Kategoriename', Html::input('name', $isEdit ? $cat->name : ''))
            . Html::field('Sortierung', Html::input('sort', $isEdit ? (int)$cat->sort : 1000));

        $lists = get_lists($isEdit ? $cat->list : '');
        if ($lists) {
            $html .= Html::field('Listenansicht', $lists);
        }

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('available', !$isEdit || (bool)$cat->available, 'Kategorie verfügbar')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="cat" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();
    }

    private function overview(): string
    {
        $cats = Db::select('SELECT * FROM ' . Db::table('cat') . ' ORDER BY sort, name');

        $rows = [];
        foreach ($cats as $index => $cat) {
            $rows[] = [
                (string)(int)$cat->id,
                Html::e((string)$cat->name),
                Sorting::cell($this->action(), $cats[$index - 1] ?? null, $cat, $cats[$index + 1] ?? null),
                Html::yesNo($cat->available),
                $this->editLink((int)$cat->id),
                $this->deleteLink((int)$cat->id),
            ];
        }

        return Html::heading('Kategorien')
            . '<div class="action-section">' . Html::button('Neue Kategorie', $this->url(['new' => 'yes'])) . '</div>'
            . Html::table(
                ['ID', 'Name', 'Sortierung', 'Verfügbar', 'Bearbeiten', 'Löschen'],
                $rows,
                'Es sind keine Kategorien angelegt.'
            );
    }
}
