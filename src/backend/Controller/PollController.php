<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\Support\Sorting;
use Pms\Backend\View\Components;
use Pms\Backend\View\Form;

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
            $entered = $this->save();
            if ($entered !== null) {
                return $this->form($entered);
            }
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

    /** @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $data = [
            'question' => Request::string('question'),
            'sort' => Request::int('sort', 1000),
            'available' => Request::checkbox('available'),
        ];
        for ($i = 1; $i <= self::ANSWER_COUNT; $i++) {
            $data['answer' . $i] = Request::string('answer' . $i);
        }
        $entered = (object)($data + ['id' => Request::int('id')]);

        if ($data['question'] === '') {
            Errors::add('question', 'Bitte geben Sie eine Frage an.');
            return $entered;
        }
        if ($data['answer1'] === '' || $data['answer2'] === '') {
            Errors::add('answer1', 'Eine Umfrage braucht mindestens zwei Antworten.');
            return $entered;
        }

        $id = Request::int('id');
        $success = $id > 0 ? Db::update('poll', $id, $data) : Db::insert('poll', $data) > 0;

        if ($success) {
            Flash::success('Umfrage erfolgreich gespeichert!');
            $this->redirect();
        }

        Flash::error('Fehler beim Speichern der Umfrage!');
        return $entered;
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
        $id = $isEdit ? (int)$poll->id : 0;

        $question = Form::field(
            'Frage',
            Html::input('question', $isEdit ? $poll->question : '', ['id' => 'question']),
            ['name' => 'question', 'required' => true]
        )
            . Form::field(
                'Sortierung',
                Html::input('sort', $isEdit ? (int)$poll->sort : 1000, ['id' => 'sort', 'type' => 'number']),
                ['name' => 'sort']
            )
            . Form::check(
                Html::checkbox('available', !$isEdit || (bool)$poll->available),
                'Umfrage verfügbar'
            );

        $answers = '';
        for ($i = 1; $i <= self::ANSWER_COUNT; $i++) {
            $field = 'answer' . $i;
            $answers .= Form::field(
                $i . '. Antwort',
                Html::input($field, $isEdit ? $poll->$field : '', ['id' => $field]),
                ['name' => $field, 'required' => $i <= 2]
            );
        }

        return Components::pageHeader($id > 0 ? 'Umfrage bearbeiten' : 'Umfrage erstellen')
            . Html::formOpen($this->action())
            . Html::hidden('id', $id)
            . Form::card(
                Form::section('Frage', $question)
                . Form::section('Antworten', $answers),
                Form::actions('poll', 'Speichern', $this->url())
            )
            . Html::formClose();
    }

    private function overview(): string
    {
        $available = Request::queryInt('available', -1);

        $list = Listing::from('poll')
            ->searchIn(['question'])
            ->sortableBy(['id' => 'id', 'question' => 'question', 'sort' => 'sort', 'available' => 'available'])
            ->orderedBy('sort, question')
            ->keep('available', $available < 0 ? '' : (string)$available);

        if ($available >= 0) {
            $list->where('available = :available', ['available' => $available]);
        }

        $list->load();

        $rows = [];
        foreach ($list->rows as $index => $poll) {
            $sort = $list->isDefaultOrder()
                ? Sorting::cell($this->action(), $list->rows[$index - 1] ?? null, $poll, $list->rows[$index + 1] ?? null)
                : Html::e((string)(int)$poll->sort);

            $rows[] = [
                (string)(int)$poll->id,
                Html::e((string)$poll->question),
                $sort,
                Components::booleanChip($poll->available, 'Aktiv', 'Inaktiv'),
                $this->rowActions((int)$poll->id),
            ];
        }

        return Components::pageHeader(
            'Umfragen',
            'Fragen und Antworten für das Umfragen-Plugin.',
            Components::primary('Neue Umfrage', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, [[
                'name' => 'available',
                'label' => 'Status',
                'options' => [-1 => 'Alle', 1 => 'Nur aktive', 0 => 'Nur inaktive'],
                'value' => $available,
            ]], 'Frage suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'question', 'label' => 'Frage', 'class' => 'cell-title'],
                    ['key' => 'sort', 'label' => 'Sortierung'],
                    ['key' => 'available', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Keine Frage passt zur Suche.' : 'Es sind keine Umfragen angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
