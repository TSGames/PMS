<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

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
            $this->save();
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

    /** Speichert einen neuen oder geänderten Ban. */
    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $id = Request::int('id');
        $ip = Request::string('ip');
        if ($ip === '') {
            Flash::error('Bitte geben Sie eine IP-Adresse an.');
            return;
        }

        // Dauer in Tagen; 0 oder leer bedeutet "unbegrenzt"
        $days = Request::float('time');
        $expires = $days > 0 ? time() + (int)round($days * 86400) : 0;

        $data = [
            'ip' => $ip,
            'reason' => Request::text('reason'),
            'time' => $expires,
        ];

        $success = $id > 0 ? Db::update('bans', $id, $data) : Db::insert('bans', $data) > 0;

        if ($success) {
            Flash::success('Ban erfolgreich gespeichert!');
            $this->redirect();
        }
        Flash::error('Fehler beim Speichern des Bans!');
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
        $days = '';
        if ($isEdit && (int)$ban->time > 0) {
            $days = (string)max(0, (int)ceil(((int)$ban->time - time()) / 86400));
        }

        return Html::formOpen($this->action())
            . Html::heading($isEdit ? 'Ban bearbeiten' : 'Neuen Ban erstellen')
            . Html::hidden('id', $isEdit ? (int)$ban->id : 0)
            . '<table>'
            . Html::field('IP', Html::input('ip', $isEdit ? $ban->ip : '', ['maxlength' => 15]))
            . Html::field('Begründung (Optional)', Html::textarea('reason', $isEdit ? $ban->reason : '', 5, 40))
            . Html::field('Zeitlimit in Tagen (0 = Kein Limit)', Html::input('time', $days, ['size' => 3]))
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="bans" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();
    }

    /** Übersicht aller Sperrungen. */
    private function overview(): string
    {
        $bans = Db::select('SELECT * FROM ' . Db::table('bans') . ' ORDER BY id');

        $rows = [];
        foreach ($bans as $ban) {
            $rows[] = [
                (string)(int)$ban->id,
                Html::e((string)$ban->ip),
                nl2br(Html::e((string)$ban->reason)),
                Html::e(ban_time($ban->time)),
                $this->editLink((int)$ban->id),
                $this->deleteLink((int)$ban->id),
            ];
        }

        return Html::heading('Bans verwalten')
            . '<div class="action-section">'
            . Html::button('Neuer Ban', $this->url(['new' => 'yes']))
            . '</div>'
            . Html::table(
                ['ID', 'IP', 'Begründung', 'Verbleibende Dauer (in Tagen)', 'Bearbeiten', 'Löschen'],
                $rows,
                'Es sind keine Sperrungen eingetragen.'
            );
    }
}
