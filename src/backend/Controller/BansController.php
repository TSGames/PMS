<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Components;
use Pms\Backend\View\Form;

/**
 * Sperrungen von IP-Adressen.
 */
final class BansController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'bans';
    }

    #[\Override]
    protected function requiredLevel(): int
    {
        return Auth::TYPE_SUPERADMIN;
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('bans')) {
            $entered = $this->save();
            if ($entered !== null) {
                return $this->form($entered);
            }
        }

        $confirmed = $this->confirmedDeleteId();
        if ($confirmed > 0) {
            Db::delete('bans', $confirmed);
            Flash::success('Ban erfolgreich entfernt!');
            $this->redirect();
        }

        $delete = Request::queryInt('delete');
        if ($delete > 0) {
            $ban = $this->find($delete);
            if ($ban === null) {
                Flash::error('Der Ban wurde nicht gefunden.');
                return $this->overview();
            }
            return $this->confirmDelete($delete, 'Soll die Sperrung für ' . $ban->ip . ' aufgehoben werden?');
        }

        if (Request::queryInt('edit') > 0 || Request::string('new') !== '') {
            return $this->form($this->find(Request::queryInt('edit')));
        }

        return $this->overview();
    }

    /**
     * Speichert eine neue oder geänderte Sperrung.
     *
     * @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde
     */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $id = Request::int('id');
        $ip = Request::string('ip');

        // Dauer in Tagen; 0 oder leer bedeutet "unbegrenzt"
        $days = Request::float('time');
        $expires = $days > 0 ? time() + (int)round($days * 86400) : 0;

        $data = [
            'ip' => $ip,
            'reason' => Request::text('reason'),
            'time' => $expires,
        ];
        $entered = (object)($data + ['id' => $id]);

        if ($ip === '') {
            Errors::add('ip', 'Bitte geben Sie eine IP-Adresse an.');
            return $entered;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            Errors::add('ip', 'Das ist keine gültige IP-Adresse.');
            return $entered;
        }

        $success = $id > 0 ? Db::update('bans', $id, $data) : Db::insert('bans', $data) > 0;

        if ($success) {
            Flash::success('Sperrung erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern der Sperrung!');
        return $entered;
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('bans') . ' WHERE id = :id', ['id' => $id]);
    }

    /** Formular zum Anlegen und Bearbeiten. */
    private function form(?object $ban): string
    {
        $isEdit = $ban !== null;
        $id = $isEdit ? (int)$ban->id : 0;

        $days = '';
        if ($isEdit && (int)$ban->time > 0) {
            $days = (string)max(0, (int)ceil(((int)$ban->time - time()) / 86400));
        }

        $fields = Form::field(
            'IP-Adresse',
            Html::input('ip', $isEdit ? $ban->ip : '', ['id' => 'ip', 'maxlength' => 45]),
            ['name' => 'ip', 'required' => true, 'hint' => 'IPv4 oder IPv6, zum Beispiel 203.0.113.7']
        )
            . Form::field(
                'Begründung',
                Html::textarea('reason', $isEdit ? $ban->reason : '', 5, 40),
                ['name' => 'reason', 'for' => '', 'hint' => 'Optional, erscheint nur im Backend.']
            )
            . Form::field(
                'Dauer',
                '<span class="field-inline">'
                . Html::input('time', $days, ['id' => 'time', 'type' => 'number', 'min' => 0, 'style' => 'width:7rem'])
                . ' Tage</span>',
                ['name' => 'time', 'hint' => '0 oder leer sperrt unbegrenzt.']
            );

        return Components::pageHeader($id > 0 ? 'Sperrung bearbeiten' : 'Neue Sperrung')
            . Html::formOpen($this->action())
            . Html::hidden('id', $id)
            . Form::card(Form::section('', $fields), Form::actions('bans', 'Speichern', $this->url()))
            . Html::formClose();
    }

    /** Übersicht aller Sperrungen. */
    private function overview(): string
    {
        $list = Listing::from('bans')
            ->searchIn(['ip', 'reason'])
            ->sortableBy(['id' => 'id', 'ip' => 'ip', 'time' => 'time'])
            ->orderedBy('id')
            ->load();

        $rows = [];
        foreach ($list->rows as $ban) {
            $remaining = (string)ban_time($ban->time);

            $rows[] = [
                (string)(int)$ban->id,
                '<code>' . Html::e((string)$ban->ip) . '</code>',
                nl2br(Html::e((string)$ban->reason)),
                self::durationChip($remaining),
                $this->rowActions((int)$ban->id),
            ];
        }

        return Components::pageHeader(
            'Sperrungen',
            'Gesperrte IP-Adressen mit Begründung und verbleibender Dauer.',
            Components::primary('Neue Sperrung', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, [], 'IP oder Begründung suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'ip', 'label' => 'IP-Adresse', 'class' => 'cell-title'],
                    ['label' => 'Begründung'],
                    ['key' => 'time', 'label' => 'Verbleibende Dauer'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Keine Sperrung passt zur Suche.' : 'Es sind keine Sperrungen eingetragen.'
            )
            . Components::pagination($list, $this->action());
    }

    /**
     * Dauerhafte Sperrungen sollen sich von befristeten unterscheiden.
     * ban_time() liefert entweder die Zahl der Tage oder den Sprachtext
     * für eine unbefristete Sperrung.
     */
    private static function durationChip(string $remaining): string
    {
        $remaining = trim($remaining);
        if ($remaining === '' || !is_numeric($remaining)) {
            return Components::chip($remaining === '' ? 'unbegrenzt' : $remaining, 'danger');
        }
        return Components::chip($remaining . ' Tage', 'warn');
    }
}
