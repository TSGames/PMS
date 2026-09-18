<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Data\Db;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Website-Konfigurator.
 *
 * Alle Einstellungen der Website in einem Formular, nach Modulen gruppiert.
 */
final class ConfigController extends Controller
{
    /**
     * Felder, die als Zahl gespeichert werden, mit ihrem Standardwert und
     * dem erlaubten Bereich.
     *
     * @var array<string, array{0: int, 1: int|null, 2: int|null}>
     */
    private const NUMBERS = [
        'picquali' => [85, 10, 100],
        'menubreak' => [0, 0, null],
        'menu_width' => [0, 0, null],
        'menu_height' => [0, 0, null],
        'page_limit' => [15, 1, null],
        'list_rows' => [1, 0, null],
        'numcomments' => [10, 0, null],
        'mincomments' => [2, 0, null],
        'numtopuser' => [5, 0, null],
        'visitors_increment' => [1, 0, null],
        'visitors_lifetime' => [15, 0, null],
        'latest_comments_days' => [7, 0, null],
        'latest_comments_chars' => [120, 0, null],
        'menu_mode' => [0, 0, 1],
        'vertical' => [0, 0, 1],
    ];

    /** Felder, die als Ja/Nein gespeichert werden. */
    private const FLAGS = [
        'title', 'rate', 'comments', 'commentssmall', 'predownload', 'writtenby',
        'register_activated', 'password_recovery_activated', 'guestbook_activated',
        'editor', 'safemail', 'topusers', 'speciallinks', 'allow_compress', 'smileys',
    ];

    /** Felder, die als Text gespeichert werden. */
    private const TEXTS = ['name', 'page', 'mail', 'language', 'search_list', 'visitors_password'];

    #[\Override]
    public function action(): string
    {
        return 'config';
    }

    #[\Override]
    protected function requiredLevel(): int
    {
        return Auth::TYPE_SUPERADMIN;
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('config')) {
            $this->save();
        }

        return $this->form();
    }

    private function save(): void
    {
        if (!$this->checkToken()) {
            return;
        }

        $data = [];
        foreach (self::TEXTS as $field) {
            $data[$field] = Request::string($field);
        }
        foreach (self::FLAGS as $field) {
            $data[$field] = Request::checkbox($field);
        }
        foreach (self::NUMBERS as $field => [$default, $min, $max]) {
            $value = Request::int($field, $default);
            if ($min !== null) {
                $value = max($min, $value);
            }
            if ($max !== null) {
                $value = min($max, $value);
            }
            $data[$field] = $value;
        }

        if (!Db::update('config', 1, $data)) {
            Flash::error('Fehler beim Speichern!');
            return;
        }

        $this->saveNotifications();

        Flash::success('Einstellungen erfolgreich gespeichert!');
        $this->redirect();
    }

    /** Übernimmt die Auswahl der E-Mail-Benachrichtigungen je Benutzer. */
    private function saveNotifications(): void
    {
        foreach ($GLOBALS['confirmation_dialogs'] ?? [] as [$field, $column]) {
            Db::execute('UPDATE ' . Db::table('user') . ' SET ' . $column . " = 0 WHERE typ >= 1");

            foreach (Request::intList($field) as $userId) {
                Db::execute(
                    'UPDATE ' . Db::table('user') . ' SET ' . $column . ' = 1 WHERE id = :id',
                    ['id' => $userId]
                );
            }
        }
    }

    private function config(): object
    {
        return Db::first('SELECT * FROM ' . Db::table('config') . ' WHERE id = 1') ?? new \stdClass();
    }

    private function form(): string
    {
        $config = $this->config();

        $html = Html::formOpen($this->action())
            . Html::heading('Website-Konfiguration')
            . '<table class="config_table">'
            . $this->section('Allgemeines')
            . Html::field('Website-Name', Html::input('name', $config->name ?? '', ['size' => 30]))
            . $this->flagRow('title', 'Aktuellen Inhalt bei Seitentitel anzeigen', $config)
            . Html::field('Seiten-Adresse', Html::input('page', $config->page ?? '', ['size' => 30]), 'z.B. http://www.tsgames.de')
            . Html::field('Mail-Adresse', Html::input('mail', $config->mail ?? '', ['size' => 30]))
            . Html::field('Bilderqualität (10 - 100)', Html::input('picquali', (int)($config->picquali ?? 85), ['size' => 3, 'maxlength' => 3]))
            . $this->flagRow('speciallinks', 'Suchmaschinen-freundliche Links', $config)
            . $this->flagRow('safemail', 'E-Mailadressen verschlüsseln', $config)
            . $this->flagRow('allow_compress', 'Seitenausgabe komprimieren (sofern vom Browser zugelassen, kann Ladezeit verkürzen)', $config)
            . $this->flagRow('editor', 'Grafischen HTML-Editor (TinyMCE) für Content-Bearbeitung verwenden', $config)
            . $this->flagRow('smileys', 'Smiley-Modul aktivieren', $config)

            . $this->section('Modul: Sprachen')
            . Html::field('Sprachdatei', Html::select('language', $this->languageOptions(), $config->language ?? ''));

        $lists = get_lists($config->search_list ?? '', 'search_list');
        if ($lists) {
            $html .= $this->section('Modul: Suche') . Html::field('Listenansicht', $lists);
        }

        $html .= $this->section('Modul: E-Mail Benachrichtigungen')
            . '<tr><td colspan="2">' . $this->notificationTable() . '</td></tr>'

            . $this->section('Modul: Menü')
            . Html::field('Menü-Modus', $this->menuModeRadios($config))
            . Html::field('Menüumbruch alle', Html::input('menubreak', (int)($config->menubreak ?? 0), ['size' => 3, 'maxlength' => 3]) . ' Einträge')
            . Html::field('Menüausrichtung', $this->orientationRadios($config))
            . Html::field(
                'Menü-Größe',
                Html::input('menu_width', (int)($config->menu_width ?? 0), ['size' => 4, 'maxlength' => 4]) . 'px Breite, '
                . Html::input('menu_height', (int)($config->menu_height ?? 0), ['size' => 4, 'maxlength' => 4]) . 'px Höhe'
            )

            . $this->section('Modul: Listenansicht')
            . Html::field('Einträge/Seite', Html::input('page_limit', (int)($config->page_limit ?? 15), ['size' => 3, 'maxlength' => 3]) . ' Einträge')
            . $this->flagRow('commentssmall', 'Zahl der Kommentare bei Inhalts-Liste anzeigen', $config)
            . Html::field('Spalten bei Ausgabe', Html::input('list_rows', (int)($config->list_rows ?? 1), ['size' => 3, 'maxlength' => 3]))

            . $this->section('Modul: Inhaltsansicht')
            . $this->flagRow('writtenby', 'Geschrieben von... anzeigen', $config)

            . $this->section('Modul: Kommentare')
            . $this->flagRow('comments', 'Inhalte dürfen kommentiert werden', $config)
            . Html::field('Kommentar-Anzahl (Anzeige)', Html::input('numcomments', (int)($config->numcomments ?? 10), ['size' => 3, 'maxlength' => 5]))

            . $this->section('Modul: Bewertungen')
            . $this->flagRow('rate', 'Bewertungs-System für Inhalte aktivieren', $config)

            . $this->section('Modul: Besucherzähler')
            . Html::field('Passwort für Statistik-Zugriff', Html::input('visitors_password', $config->visitors_password ?? '', ['size' => 20, 'maxlength' => 32]))
            . Html::field('Besucherzähler erhöhen um', Html::input('visitors_increment', (int)($config->visitors_increment ?? 1), ['size' => 6]))
            . Html::field('Zeit (Minuten), die ein Besucher als "Online" gilt', Html::input('visitors_lifetime', (int)($config->visitors_lifetime ?? 15), ['size' => 6]))

            . $this->section('Modul: Downloads')
            . $this->flagRow('predownload', 'Bei Downloads zunächst Download-Vorschaltseite', $config)

            . $this->section('Modul: User-System')
            . $this->flagRow('register_activated', 'Registration erlauben', $config)
            . $this->flagRow('password_recovery_activated', 'Passwort darf zurückgesetzt werden', $config)

            . $this->section('Modul: Top-Users')
            . $this->flagRow('topusers', 'Top-Userliste aktiv', $config)
            . Html::field('Anzahl der Top-User', Html::input('numtopuser', (int)($config->numtopuser ?? 5), ['size' => 3, 'maxlength' => 5]))

            . $this->section('Modul: Gästebuch')
            . $this->flagRow('guestbook_activated', 'Verfassen neuer Gästebucheinträge möglich', $config)

            . $this->section('Modul: Am Meisten diskutiert')
            . Html::field('Minimum-Kommentarzahl für "Meist diskutiert"', Html::input('mincomments', (int)($config->mincomments ?? 2), ['size' => 3, 'maxlength' => 5]))

            . $this->section('Modul: Aktuelle Kommentare')
            . Html::field('Kommentare der letzten', Html::input('latest_comments_days', (int)($config->latest_comments_days ?? 7), ['size' => 2]) . ' Tage anzeigen')
            . Html::field('Anzahl Zeichen, bis gekürzt wird', Html::input('latest_comments_chars', (int)($config->latest_comments_chars ?? 120), ['size' => 2]))

            . '<tr><td colspan="2"><div class="action-section">'
            . '<input type="submit" name="config" value="Speichern">'
            . '</div></td></tr></table>'
            . Html::formClose();

        return $html;
    }

    private function section(string $title): string
    {
        return '<tr><td colspan="2"><div class="config_space">' . Html::e($title) . '</div></td></tr>';
    }

    private function flagRow(string $field, string $label, object $config): string
    {
        return '<tr><td colspan="2">'
            . Html::checkbox($field, !empty($config->$field), $label)
            . '</td></tr>';
    }

    private function menuModeRadios(object $config): string
    {
        $mode = (int)($config->menu_mode ?? 0);
        return '<label><input type="radio" name="menu_mode" value="0"' . ($mode === 0 ? ' checked' : '') . '>'
            . ' Standard (einfache Menü-Konfiguration per Admin)</label><br>'
            . '<label><input type="radio" name="menu_mode" value="1"' . ($mode === 1 ? ' checked' : '') . '>'
            . ' Erweitert (Spezielle Konfiguration für aufklappende Menüs, mit Stylesheets)</label>';
    }

    private function orientationRadios(object $config): string
    {
        $vertical = (int)($config->vertical ?? 0);
        return '<label><input type="radio" name="vertical" value="0"' . ($vertical === 0 ? ' checked' : '') . '> Horizontal</label> '
            . '<label><input type="radio" name="vertical" value="1"' . ($vertical === 1 ? ' checked' : '') . '> Vertikal</label>';
    }

    private function languageOptions(): array
    {
        $folder = $GLOBALS['language_folder'] ?? 'dialoges';
        $options = [];

        foreach (glob($folder . '/*.txt') ?: [] as $file) {
            $name = basename($file, '.txt');
            if (strtolower($name) === 'custom') {
                continue;
            }
            $options[$name] = $name;
        }
        return $options;
    }

    /** Übersicht, welche Redaktion worüber benachrichtigt wird. */
    private function notificationTable(): string
    {
        $dialogs = $GLOBALS['confirmation_dialogs'] ?? [];

        $html = '<table class="group"><tr><td class="confirm_head">Benutzer</td>'
            . '<td class="confirm_head">Gästebuch</td>'
            . '<td class="confirm_head">Kommentare</td>'
            . '<td class="confirm_head">Registration</td></tr>';

        $users = Db::select('SELECT * FROM ' . Db::table('user') . ' WHERE typ >= 1 ORDER BY typ DESC, name');
        foreach ($users as $user) {
            $html .= '<tr><td>' . Html::e((string)$user->name) . ' (' . Html::e((string)$user->mail) . ')</td>';
            foreach ($dialogs as [$field, $column]) {
                $checked = !empty($user->$column) ? ' checked' : '';
                $html .= '<td style="text-align:center;"><input type="checkbox" name="' . Html::e($field) . '[]"'
                    . ' value="' . (int)$user->id . '"' . $checked . '></td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }
}
