<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Variablen: Platzhalter, die beim Ausliefern der Seiten ersetzt werden.
 */
final class VarController extends Controller
{
    public function action(): string
    {
        return 'var';
    }

    public function handle(): string
    {
        if (Request::submitted('var')) {
            $this->save();
        }

        if (Request::submitted('poll_filter')) {
            $_SESSION['poll_search'] = Request::checkbox('poll_search');
            $_SESSION['poll_replace'] = Request::checkbox('poll_replace');
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
        $hideSearch = !empty($_SESSION['poll_search']);
        $hideReplace = !empty($_SESSION['poll_replace']);

        $headers = ['ID'];
        if (!$hideSearch) {
            $headers[] = 'Suche';
        }
        if (!$hideReplace) {
            $headers[] = 'Ersetzen mit';
        }
        $headers[] = 'Bearbeiten';
        $headers[] = 'Löschen';

        $rows = [];
        foreach (Db::select('SELECT * FROM ' . Db::table('dynamic') . ' ORDER BY LOWER(searcher)') as $rule) {
            $row = [(string)(int)$rule->id];
            if (!$hideSearch) {
                $row[] = nl2br(Html::e((string)$rule->searcher));
            }
            if (!$hideReplace) {
                $row[] = nl2br(Html::e((string)$rule->replacer));
            }
            $row[] = $this->editLink((int)$rule->id);
            $row[] = $this->deleteLink((int)$rule->id);
            $rows[] = $row;
        }

        return Html::heading('Regeln verwalten')
            . '<div class="action-section">' . Html::button('Neue Regel', $this->url(['new' => 'yes'])) . '</div>'
            . '<div class="action-section">'
            . Html::formOpen($this->action())
            . Html::checkbox('poll_search', $hideSearch, 'Zeige keine Such-Kriterien')
            . ' | '
            . Html::checkbox('poll_replace', $hideReplace, 'Zeige keine Ersetz-Kriterien')
            . ' <input type="submit" name="poll_filter" value="OK">'
            . Html::formClose()
            . '</div>'
            . Html::table($headers, $rows, 'Es sind keine Regeln angelegt.');
    }
}
