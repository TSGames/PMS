<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Data\Db;
use Pms\Support\Errors;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Listing;
use Pms\Support\Request;

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
            $entered = $this->save();
            if ($entered !== null) {
                return $this->form($entered);
            }
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

    /** @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $id = Request::int('id');
        $data = [
            'searcher' => Request::text('search'),
            'replacer' => Request::text('replace'),
            'makebr' => Request::checkbox('makebr'),
        ];
        $entered = (object)($data + ['id' => $id]);

        if (trim($data['searcher']) === '') {
            Errors::add('search', 'Bitte geben Sie an, wonach gesucht werden soll.');
            return $entered;
        }

        $success = $id > 0 ? Db::update('dynamic', $id, $data) : Db::insert('dynamic', $data) > 0;

        if ($success) {
            Flash::success('Regel erfolgreich gespeichert!');
            $this->redirect();
        }

        Flash::error('Fehler beim Speichern der Regel!');
        return $entered;
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
        $id = $isEdit ? (int)$rule->id : 0;

        $fields = Form::field(
            'Suchen nach',
            Html::textarea('search', $isEdit ? $rule->searcher : '', 8, 70, ['data-editor' => '']),
            ['name' => 'search', 'for' => '', 'required' => true, 'hint' => 'Der Platzhalter, wie er im Inhalt steht.']
        )
            . Form::field(
                'Ersetzen mit',
                Html::textarea('replace', $isEdit ? $rule->replacer : '', 8, 70, ['data-editor' => '']),
                ['name' => 'replace', 'for' => '', 'hint' => 'Darf HTML enthalten.']
            )
            . Form::check(
                Html::checkbox('makebr', !$isEdit || (bool)$rule->makebr),
                'Zeilenumbrüche der Ersetzung als <br> ausgeben'
            );

        return Components::pageHeader(
            $id > 0 ? 'Regel bearbeiten' : 'Neue Regel erstellen',
            'Jedes Vorkommen des Suchtextes wird beim Anzeigen ersetzt.'
        )
            . Html::formOpen($this->action())
            . Html::hidden('id', $id)
            . Form::card(Form::section('', $fields), Form::actions('var', 'Speichern', $this->url()))
            . Html::formClose()
            . get_code_editor();
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
