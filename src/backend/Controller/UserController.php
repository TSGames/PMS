<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Data\Db;
use Pms\Support\Auth;
use Pms\Support\Errors;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Listing;
use Pms\Support\Request;

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
            $entered = $this->save();
            if ($entered !== null) {
                return $this->form($entered);
            }
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

    /** @return object|null Die eingegebenen Werte, wenn nicht gespeichert wurde */
    private function save(): ?object
    {
        if (!$this->checkToken()) {
            return null;
        }

        $id = Request::int('id');
        if ($id > 0 && !$this->mayEdit($id)) {
            Flash::error('Sie können keine Benutzer bearbeiten, die höhere Berechtigungen als Sie selbst haben!');
            return null;
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
        $entered = $this->enteredValues($id, $type, $active);

        if ($id === Auth::userId() && !$active) {
            Errors::add('active', 'Sie können nicht Ihren aktuellen Account sperren.');
            return $entered;
        }

        // Die Prüfungen stehen hier und nicht nur in make_user(), damit die
        // Meldung am betroffenen Feld erscheint statt am Seitenkopf
        if (!$this->validate($id)) {
            return $entered;
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
            return $entered;
        }

        // make_user liefert bei Eingabefehlern die Meldung als Zeichenkette;
        // was hier noch ankommt, hat validate() nicht abgedeckt
        Flash::error((string)$result);
        return $entered;
    }

    /**
     * Prüft die Eingaben und legt die Meldungen am jeweiligen Feld ab.
     *
     * @return bool true, wenn nichts zu beanstanden ist
     */
    private function validate(int $id): bool
    {
        $name = Request::string('name');
        $password = Request::text('password');
        $isNew = $id <= 0;

        if (mb_strlen($name) < 3) {
            Errors::add('name', 'Der Benutzername muss mindestens 3 Zeichen lang sein.');
        } elseif (!name_condition($name)) {
            Errors::add('name', 'Der Benutzername enthält unzulässige Zeichen.');
        } elseif ($this->nameTaken($name, $id)) {
            Errors::add('name', 'Dieser Benutzername ist bereits vorhanden.');
        }

        if ($isNew || $password !== '') {
            if ($password !== Request::text('passwordr')) {
                Errors::add('passwordr', 'Die beiden Passwörter stimmen nicht überein.');
            } elseif (strlen($password) < 3) {
                Errors::add('password', 'Das Passwort muss mindestens 3 Zeichen lang sein.');
            }
        }

        if (!check_mail(Request::string('mail'))) {
            Errors::add('mail', 'Das ist keine gültige E-Mail-Adresse.');
        }

        $birthday = Request::string('bday');
        if ($birthday !== '' && !$this->isValidBirthday($birthday)) {
            Errors::add('bday', 'Bitte geben Sie das Geburtsdatum als TT.MM.JJJJ an.');
        }

        return !Errors::has();
    }

    private function nameTaken(string $name, int $id): bool
    {
        $found = Db::first(
            'SELECT id FROM ' . Db::table('user') . ' WHERE LOWER(name) = LOWER(:name) AND id <> :id',
            ['name' => $name, 'id' => $id]
        );
        return $found !== null;
    }

    /** Ein Geburtsdatum liegt zwischen 200 Jahren und einem Jahr zurück. */
    private function isValidBirthday(string $value): bool
    {
        $parts = explode('.', $value);
        if (count($parts) !== 3) {
            return false;
        }
        [$day, $month, $year] = array_map('intval', $parts);
        if (!checkdate($month, $day, $year)) {
            return false;
        }
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        return $timestamp !== false
            && $timestamp <= time() - 365 * 86400
            && $timestamp >= time() - 200 * 365 * 86400;
    }

    /**
     * Die abgeschickten Werte als Datensatz, damit das Formular sie nach
     * einem Fehler wieder anzeigt.
     */
    private function enteredValues(int $id, int $type, bool $active): object
    {
        return (object)[
            'id' => $id,
            'name' => Request::string('name'),
            'mail' => Request::string('mail'),
            'website' => Request::string('website'),
            'signatur' => Request::text('signatur'),
            'typ' => $type,
            'bday' => 0,
            'image' => '',
            'showmail' => Request::checkbox('showmail'),
            'top' => Request::checkbox('top'),
            'active' => $active ? 1 : 0,
            'bday_text' => Request::string('bday'),
        ];
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

        $birthday = $isEdit ? (string)($user->bday_text ?? '') : '';
        if ($birthday === '' && $isEdit && (int)$user->bday > 0) {
            $birthday = date('d.m.Y', (int)$user->bday);
        }

        $account = Form::field(
            'Name',
            Html::input('name', $isEdit ? $user->name : '', ['id' => 'name']),
            ['name' => 'name', 'required' => true, 'hint' => 'Mindestens 3 Zeichen.']
        )
            . Form::field(
                'E-Mail-Adresse',
                Html::input('mail', $isEdit ? $user->mail : '', ['id' => 'mail', 'type' => 'email']),
                ['name' => 'mail', 'required' => true]
            )
            . Form::field(
                'Benutzertyp',
                Html::select('typ', $this->typeOptions($id === Auth::userId()), $isEdit ? (int)$user->typ : 0, ['id' => 'typ']),
                ['name' => 'typ', 'help' => 'user/typ', 'hint' => 'Höher als die eigene Stufe lässt sich niemand einstufen.']
            );

        $password = Form::field(
            $isEdit ? 'Neues Passwort' : 'Passwort',
            Html::input('password', '', ['id' => 'password', 'type' => 'password', 'autocomplete' => 'new-password']),
            [
                'name' => 'password',
                'required' => !$isEdit,
                'hint' => $isEdit ? 'Leer lassen, um das bisherige Passwort zu behalten.' : 'Mindestens 3 Zeichen.',
            ]
        )
            . Form::field(
                'Passwort wiederholen',
                Html::input('passwordr', '', ['id' => 'passwordr', 'type' => 'password', 'autocomplete' => 'new-password']),
                ['name' => 'passwordr', 'required' => !$isEdit]
            );

        $profile = Form::field(
            'Website',
            Html::input('website', $isEdit ? $user->website : '', ['id' => 'website', 'maxlength' => 128]),
            ['name' => 'website']
        )
            . Form::field(
                'Signatur',
                Html::textarea('signatur', $isEdit ? my_stripslashes((string)$user->signatur) : '', 3, 30),
                ['name' => 'signatur', 'help' => 'user/signatur', 'for' => '']
            )
            . Form::field(
                'Geburtsdatum',
                Html::input('bday', $birthday, ['id' => 'bday', 'placeholder' => 'TT.MM.JJJJ']),
                ['name' => 'bday', 'hint' => 'Zum Beispiel 15.03.1985.']
            )
            . Form::field(
                'Avatar',
                '<input type="file" name="image" id="image" accept="image/*">',
                ['name' => 'image', 'for' => 'image', 'hint' => 'JPG, GIF oder PNG.']
            );

        if ($isEdit && $user->image) {
            $profile .= Form::field(
                'Aktueller Avatar',
                (string)make_contentimg('user', $id, $user->image, 0)
                . '<label class="field-check">' . Html::checkbox('image_delete', false) . ' Aktuelles Bild löschen</label>',
                ['for' => '']
            );
        }

        $options = Form::check(
            Html::checkbox('showmail', !$isEdit || (bool)$user->showmail),
            'E-Mail-Adresse öffentlich anzeigen'
        )
            . Form::check(
                Html::checkbox('top', !$isEdit || (bool)$user->top),
                'In der Top-Liste sichtbar'
            )
            . Form::check(
                Html::checkbox('active', !$isEdit || (bool)$user->active),
                'Konto ist freigeschaltet',
                ['name' => 'active', 'help' => 'user/active']
            );

        return Components::pageHeader($id > 0 ? 'Benutzer bearbeiten' : 'Benutzer erstellen')
            . Html::formOpen($this->action(), [], ['upload' => true])
            . Html::hidden('id', $id)
            . Form::card(
                Form::section('Konto', $account)
                . Form::section('Passwort', $password)
                . Form::section('Profil', $profile)
                . Form::section('Einstellungen', $options),
                Form::actions('user', 'Speichern', $this->url())
            )
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
