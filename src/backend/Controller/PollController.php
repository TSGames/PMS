<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;

/**
 * Umfragen mit bis zu zehn Antwortmöglichkeiten.
 */
final class PollController extends Controller
{
    private const ANSWER_COUNT = 10;

    #[\Override]
    public function action(): string
    {
        return 'poll';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('poll')) {
            $this->save();
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            Db::delete('poll', $confirmed);
            Flash::success('Umfrage erfolgreich gelöscht!');
            $this->redirect();
        }

        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            $poll = $this->find($delete);
            if ($poll === null) {
                Flash::error('Die Umfrage wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->confirmDelete($delete, 'Soll die Umfrage "' . $poll->question . '" gelöscht werden?');
        }

        if (Request::queryInt('edit') > 0 || Request::string('new') !== '') {
            return $this->form($this->find(Request::queryInt('edit')));
        }

        Sorting::handleRequest('poll');

        return $this->overview();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $question = Request::string('question');
        if ($question === '') {
            Flash::error('Bitte geben Sie eine Frage an.');
            return;
        }

        $data = [
            'question' => $question,
            'sort' => Request::int('sort', 1000),
            'available' => Request::checkbox('available'),
        ];
        for ($i = 1; $i <= self::ANSWER_COUNT; $i++) {
            $data['answer' . $i] = Request::string('answer' . $i);
        }

        $id = Request::int('id');
        $success = $id > 0 ? Db::update('poll', $id, $data) : Db::insert('poll', $data) > 0;

        if ($success) {
            Flash::success('Umfrage erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern der Umfrage!');
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('poll') . ' WHERE id = :id', ['id' => $id]);
    }

    private function form(?object $poll): string
    {
        $isEdit = $poll !== null;

        $html = Html::formOpen($this->action())
            . Html::heading($isEdit ? 'Umfrage bearbeiten' : 'Umfrage erstellen')
            . Html::hidden('id', $isEdit ? (int)$poll->id : 0)
            . '<table>'
            . Html::field('Frage', Html::input('question', $isEdit ? $poll->question : '', ['size' => 40]))
            . Html::field('Sortierung', Html::input('sort', $isEdit ? (int)$poll->sort : 1000));

        for ($i = 1; $i <= self::ANSWER_COUNT; $i++) {
            $field = 'answer' . $i;
            $html .= Html::field($i . '. Antwort', Html::input($field, $isEdit ? $poll->$field : '', ['size' => 40]));
        }

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('available', !$isEdit || (bool)$poll->available, 'Umfrage verfügbar')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="poll" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();
    }

    private function overview(): string
    {
        $polls = Db::select('SELECT * FROM ' . Db::table('poll') . ' ORDER BY sort, question');

        $rows = [];
        foreach ($polls as $index => $poll) {
            $rows[] = [
                (string)(int)$poll->id,
                Html::e((string)$poll->question),
                Sorting::cell($this->action(), $polls[$index - 1] ?? null, $poll, $polls[$index + 1] ?? null),
                Html::yesNo($poll->available),
                $this->editLink((int)$poll->id),
                $this->deleteLink((int)$poll->id),
            ];
        }

        return Html::heading('Umfragen')
            . '<div class="action-section">' . Html::button('Neue Umfrage', $this->url(['new' => 'yes'])) . '</div>'
            . Html::table(
                ['ID', 'Frage', 'Sortierung', 'Verfügbar', 'Bearbeiten', 'Löschen'],
                $rows,
                'Es sind keine Umfragen angelegt.'
            );
    }
}
