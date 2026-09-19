<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Components;

/**
 * Benutzerkonten.
 *
 * Ein Administrator darf nur Konten bearbeiten, deren Stufe die eigene
 * nicht übersteigt, und die eigene Stufe nicht herabsetzen.
 */
final class UserController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'user';
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('user')) {
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

        $edit = Request::queryInt('edit');
        if ($edit > 0) {
            if (!$this->mayEdit($edit)) {
                Flash::error('Sie können keine Benutzer bearbeiten, die höhere Berechtigungen als Sie selbst haben!');
                return $this->overview();
            }
            return $this->form($this->find($edit));
        }

        if (Request::string('new') !== '') {
            return $this->form(null);
        }

        return $this->overview();
    }

    /** Darf der angemeldete Benutzer dieses Konto verändern? */
    private function mayEdit(int $id): bool
    {
        return (int)from_db('user', $id, 'typ') <= Auth::userType();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $id = Request::int('id');
        if ($id > 0 && !$this->mayEdit($id)) {
            Flash::error('Sie können keine Benutzer bearbeiten, die höhere Berechtigungen als Sie selbst haben!');
            return;
        }

        $type = Request::int('typ');
        // Die eigene Stufe lässt sich weder anheben noch absenken
        if ($id === Auth::userId() && $type < Auth::userType()) {
            $type = Auth::userType();
        }
        if ($type > Auth::userType()) {
            $type = Auth::userType();
        }

        $active = Request::checkbox('active');
        if ($id === Auth::userId() && !$active) {
            Flash::error('Sie können nicht Ihren aktuellen Account sperren.');
            return;
        }

        $result = make_user(
            $id,
            Request::string('name'),
            Request::text('password'),
            Request::text('passwordr'),
            Request::string('mail'),
            Request::string('website'),
            $type,
            $_FILES['image']['name'] ?? '',
            $_FILES['image']['tmp_name'] ?? '',
            Request::checkbox('image_delete'),
            Request::string('bday'),
            Request::checkbox('top'),
            $active,
            0,
            Request::text('signatur'),
            Request::checkbox('showmail')
        );

        if (is_array($result)) {
            if ($result[1]) {
                Auth::forget();
                Flash::success($result[0]);
                $this->redirect();
            }
            Flash::error($result[0]);
            return;
        }

        // make_user liefert bei Eingabefehlern die Meldung als Zeichenkette
        Flash::error((string)$result);
    }

    private function delete(int $id): never
    {
        if (!$this->mayEdit($id)) {
            Flash::error('Sie können keine Benutzer löschen, die höhere Berechtigungen als Sie selbst haben!');
            $this->redirect();
        }
        if ($id === Auth::userId()) {
            Flash::error('Sie können sich nicht selbst löschen!');
            $this->redirect();
        }

        $name = (string)from_db('user', $id, 'name');
        del_contentimg('user', $id, from_db('user', $id, 'image'));

        if (Db::delete('user', $id)) {
            // Kommentare bleiben erhalten und tragen künftig nur noch den Namen
            Db::execute(
                'UPDATE ' . Db::table('comments') . ' SET user = 0, name = :name WHERE user = :id',
                ['name' => $name, 'id' => $id]
            );
            Flash::success('Benutzer erfolgreich entfernt');
        } else {
            Flash::error('Benutzer konnte nicht entfernt werden');
        }
        $this->redirect();
    }

    private function deleteConfirmation(int $id): string
    {
        $user = $this->find($id);
        if ($user === null) {
            Flash::error('Der Benutzer wurde nicht gefunden.');
            return $this->overview();
        }
        if (!$this->mayEdit($id)) {
            Flash::error('Sie können keine Benutzer löschen, die höhere Berechtigungen als Sie selbst haben!');
            return $this->overview();
        }
        if ($id === Auth::userId()) {
            Flash::error('Sie können sich nicht selbst löschen!');
            return $this->overview();
        }

        return $this->confirmDelete(
            $id,
            'Soll der Benutzer "' . $user->name . '" gelöscht werden?',
            'Hinweis: Kommentare des Benutzers bleiben erhalten und werden seinem Namen zugeordnet.'
        );
    }

    private function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return Db::first('SELECT * FROM ' . Db::table('user') . ' WHERE id = :id', ['id' => $id]);
    }

    /** Auswählbare Benutzertypen bis zur eigenen Stufe. */
    private function typeOptions(bool $isOwnAccount): array
    {
        $labels = $GLOBALS['user_typ'] ?? [];
        $lowest = $isOwnAccount ? Auth::userType() : 0;

        $options = [];
        for ($type = $lowest; $type <= Auth::userType(); $type++) {
            $options[$type] = $labels[$type] ?? (string)$type;
        }
        return $options;
    }

    private function form(?object $user): string
    {
        $isEdit = $user !== null;
        $id = $isEdit ? (int)$user->id : 0;

        $birthday = '';
        if ($isEdit && (int)$user->bday > 0) {
            $birthday = date('d.m.Y', (int)$user->bday);
        }

        $html = Html::formOpen($this->action(), [], ['upload' => true])
            . Html::heading($isEdit ? 'Benutzer bearbeiten' : 'Benutzer erstellen')
            . Html::hidden('id', $id)
            . '<table>'
            . Html::field('Name', Html::input('name', $isEdit ? $user->name : ''))
            . Html::field('Neues Passwort', Html::input('password', '', ['type' => 'password', 'autocomplete' => 'new-password']))
            . Html::field('Passwort wiederholen', Html::input('passwordr', '', ['type' => 'password', 'autocomplete' => 'new-password']))
            . Html::field('EMail-Adresse', Html::input('mail', $isEdit ? $user->mail : ''))
            . Html::field('Website', Html::input('website', $isEdit ? $user->website : '', ['maxlength' => 128]))
            . Html::field('Signatur', Html::textarea('signatur', $isEdit ? my_stripslashes((string)$user->signatur) : '', 3, 30))
            . Html::field('Benutzertyp', Html::select('typ', $this->typeOptions($id === Auth::userId()), $isEdit ? (int)$user->typ : 0))
            . Html::field('Geburtsdatum (z.B. 15.03.1985)', Html::input('bday', $birthday))
            . Html::field('Avatar wählen (jpg, gif, png)', '<input type="file" name="image">');

        if ($isEdit && $user->image) {
            $html .= '<tr><td>' . make_contentimg('user', $id, $user->image, 0) . '</td><td>'
                . Html::checkbox('image_delete', false, 'Aktuelles Bild löschen') . '</td></tr>';
        }

        return $html
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('showmail', !$isEdit || (bool)$user->showmail, 'Die E-Mailadresse des Benutzers anzeigen')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('top', !$isEdit || (bool)$user->top, 'Benutzer ist in Top-Liste sichtbar')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . Html::checkbox('active', !$isEdit || (bool)$user->active, 'Benutzer ist aktiviert')
            . '</div></td></tr>'
            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="user" value="Speichern"> '
            . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div></td></tr></table>'
            . Html::formClose();
    }

    private function overview(): string
    {
        /** @var array<int, string> $labels */
        $labels = $GLOBALS['user_typ'] ?? [];
        $typ = Request::queryInt('typ', -1);

        // Punkte: eigene Punkte zuzüglich 60 je verfasstem Kommentar
        $list = Listing::from('user')
            ->alias('u')
            ->join('LEFT JOIN ' . Db::table('comments') . ' c ON c.user = u.id')
            ->groupBy('u.id', 'COUNT(DISTINCT u.id)')
            ->select('u.*, COUNT(c.id) * 60 + u.points AS points')
            ->searchIn(['u.name', 'u.mail'])
            ->sortableBy([
                'id' => 'u.id',
                'name' => 'LOWER(u.name)',
                'typ' => 'u.typ',
                'login' => 'u.login',
                'register' => 'u.register',
                'points' => 'points',
                'active' => 'u.active',
            ])
            ->orderedBy('u.id')
            ->keep('typ', $typ < 0 ? '' : (string)$typ);

        if ($typ >= 0) {
            $list->where('u.typ = :typ', ['typ' => $typ]);
        }

        $list->load();

        $rows = [];
        foreach ($list->rows as $user) {
            $rows[] = [
                (string)(int)$user->id,
                Html::e((string)$user->name),
                '<a href="mailto:' . Html::e((string)$user->mail) . '">' . Html::e((string)$user->mail) . '</a>',
                Components::chip($labels[(int)$user->typ] ?? '', (int)$user->typ >= 2 ? 'accent' : ''),
                Html::date($user->login),
                Html::date($user->register),
                '<code>' . Html::e((string)$user->registerip) . '</code>',
                (string)(int)$user->points,
                Components::booleanChip((int)$user->active === 1, 'Aktiv', 'Gesperrt'),
                $this->rowActions((int)$user->id),
            ];
        }

        $filters = [];
        if ($labels !== []) {
            $filters[] = [
                'name' => 'typ',
                'label' => 'Benutzertyp',
                'options' => [-1 => 'Alle Typen'] + $labels,
                'value' => $typ,
            ];
        }

        return Components::pageHeader(
            'Benutzerverwaltung',
            'Konten der Website und ihre Rechtestufe.',
            Components::primary('Neuer Benutzer', $this->url(['new' => 'yes']))
        )
            . Components::toolbar($this->action(), $list, $filters, 'Name oder E-Mail suchen')
            . Components::table(
                [
                    ['key' => 'id', 'label' => 'ID', 'class' => 'cell-id'],
                    ['key' => 'name', 'label' => 'Name', 'class' => 'cell-title'],
                    ['label' => 'E-Mail'],
                    ['key' => 'typ', 'label' => 'Typ'],
                    ['key' => 'login', 'label' => 'Letzter Login'],
                    ['key' => 'register', 'label' => 'Registriert'],
                    ['label' => 'Registrations-IP'],
                    ['key' => 'points', 'label' => 'Punkte', 'class' => 'cell-number'],
                    ['key' => 'active', 'label' => 'Status'],
                    ['label' => 'Aktionen', 'class' => 'cell-actions'],
                ],
                $rows,
                $this->action(),
                $list,
                $list->isFiltered() ? 'Kein Benutzer passt zur Suche.' : 'Es sind keine Benutzer angelegt.'
            )
            . Components::pagination($list, $this->action());
    }
}
