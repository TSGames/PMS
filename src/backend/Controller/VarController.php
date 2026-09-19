<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Components;

/**
 * Variablen: Platzhalter, die beim Ausliefern der Seiten ersetzt werden.
 */
final class VarController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'var';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('var')) {
            $this->save();
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            Db::delete('dynamic', $confirmed);
            Flash::success('Regel erfolgreich entfernt!');
            $this->redirect();
        }

        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            $rule = $this->find($delete);
            if ($rule === null) {
                Flash::error('Die Regel wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->confirmDelete($delete, 'Soll die Regel "' . $rule->searcher . '" gelöscht werden?');
        }

        if (Request::queryInt('edit') > 0 || Request::string('new') !== '') {
            return $this->form($this->find(Request::queryInt('edit')));
        }

        return $this->overview();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $search = Request::text('search');
        if (trim($search) === '') {
            Flash::error('Bitte geben Sie an, wonach gesucht werden soll.');
            return;
        }

        $id = Request::int('id');
        $data = [
            'searcher' => $search,
            'replacer' => Request::text('replace'),
            'makebr' => Request::checkbox('makebr'),
        ];

        $success = $id > 0 ? Db::update('dynamic', $id, $data) : Db::insert('dynamic', $data) > 0;

        if ($success) {
            Flash::success('Regel erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern der Regel!');
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('dynamic') . ' WHERE id = :id', ['id' => $id]);
    }

    private function form(?object $rule): string
    {
        $isEdit = $rule !== null;

        return Html::formOpen($this->action())
            . Html::heading($isEdit ? 'Regel bearbeiten' : 'Neue Regel erstellen')
            . Html::hidden('id', $isEdit ? (int)$rule->id : 0)
            . '<table>'
            . Html::field('Suchen', Html::textarea('search', $isEdit ? $rule->searcher : '', 10, 70))
            . Html::field('Ersetzen mit', Html::textarea('replace', $isEdit ? $rule->replacer : '', 10, 70))
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('makebr', !$isEdit || (bool)$rule->makebr, 'Umbrüche mit "<br>" ersetzen.')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="var" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose()
            . get_monaco();
    }

    private function overview(): string
    {
        // Der Altbestand bot an, die Spalten "Suche" und "Ersetzen mit"
        // auszublenden - ein Behelf, solange die Liste nicht durchsuchbar war.
        // Mit dem Suchfeld ist er entbehrlich.
        $list = Listing::from('dynamic')
            ->searchIn(['searcher', 'replacer'])
            ->sortableBy(['id' => 'id', 'searcher' => 'LOWER(searcher)', 'replacer' => 'LOWER(replacer)'])
            ->orderedBy('LOWER(searcher)')
            ->load();

        $rows = [];
        foreach ($list->rows as $rule) {
            $rows[] = [
                (string)(int)$rule->id,
                '<code>' . nl2br(Html::e((string)$rule->searcher)) . '</code>',
                nl2br(Html::e((string)$rule->replacer)),
                $this->rowActions((int)$rule->id),
            ];
        }

        return Components::pageHeader(
            'Variablen',
            'Platzhalter, die beim Anzeigen eines Inhalts ersetzt werden.',
            Components::primary('Neue Regel', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, [], 'Platzhalter oder Ersetzung suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'searcher', 'label' => 'Suche', 'class' => 'cell-title'],
                    ['key' => 'replacer', 'label' => 'Ersetzen mit'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Keine Regel passt zur Suche.' : 'Es sind keine Regeln angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
