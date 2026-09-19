<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Form;
use Pms\Backend\View\Icons;
use Pms\Data\Db;
use Pms\Support\Auth;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

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
        foreach ($GLOBALS['confirmation_dialogs'] ?? [] as $dialog) {
            [$field, $column] = $dialog;
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

    /**
     * Aufbau des Konfigurators: Reiter mit den Abschnitten darunter.
     *
     * Der Altbestand hatte über 35 Einstellungen in einem einzigen Formular.
     * Die Abschnitte behalten ihre Namen, liegen jetzt aber auf Reitern -
     * und das Suchfeld findet eine Einstellung quer über alle hinweg.
     *
     * @var array<string, array{label: string, sections: list<string>}>
     */
    private const TABS = [
        'allgemein' => ['label' => 'Allgemein', 'sections' => ['Allgemeines', 'Modul: Sprachen', 'Modul: Suche']],
        'darstellung' => ['label' => 'Darstellung', 'sections' => ['Modul: Menü', 'Modul: Listenansicht', 'Modul: Inhaltsansicht']],
        'mitmachen' => ['label' => 'Mitmachen', 'sections' => ['Modul: Kommentare', 'Modul: Bewertungen', 'Modul: Gästebuch', 'Modul: Am Meisten diskutiert', 'Modul: Aktuelle Kommentare']],
        'benutzer' => ['label' => 'Benutzer', 'sections' => ['Modul: User-System', 'Modul: Top-Users']],
        'betrieb' => ['label' => 'Betrieb', 'sections' => ['Modul: Besucherzähler', 'Modul: Downloads']],
        'benachrichtigungen' => ['label' => 'Benachrichtigungen', 'sections' => ['Modul: E-Mail Benachrichtigungen']],
    ];

    private function form(): string
    {
        $config = $this->config();
        $sections = $this->sections($config);

        $tabs = '<div class="tabs" x-show="search === \'\'" x-cloak>';
        foreach (self::TABS as $key => $tab) {
            $tabs .= '<button type="button" class="tab" :class="tab === \'' . $key . '\' ? \'active\' : \'\'"'
                . ' @click="select(\'' . $key . '\')">' . Html::e($tab['label']) . '</button>';
        }
        $tabs .= '</div>';

        $body = '';
        foreach (self::TABS as $key => $tab) {
            foreach ($tab['sections'] as $title) {
                if (!isset($sections[$title])) {
                    continue;
                }
                $body .= Form::section($title, $sections[$title], [
                    'data-tab' => $key,
                    'x-show' => 'sectionVisible($el)',
                    'x-cloak' => '',
                ]);
            }
        }

        return Components::pageHeader(
            'Website-Konfiguration',
            'Globale Einstellungen dieser Website. Die Suche findet eine Einstellung über alle Reiter hinweg.'
        )
            . Html::formOpen($this->action())
            . '<div x-data="configForm(\'allgemein\')">'
            . '<div class="toolbar">'
            . '<div class="toolbar-group toolbar-search">'
            . Icons::render('search', 'icon icon-sm')
            . '<label class="visually-hidden" for="config-search">Einstellung suchen</label>'
            . '<input type="search" id="config-search" x-model="search" placeholder="Einstellung suchen" autocomplete="off">'
            . '</div></div>'
            . $tabs
            . Form::card($body, Form::actions('config', 'Speichern', ''))
            . '</div>'
            . Html::formClose();
    }

    /**
     * Die Einstellungen je Abschnitt.
     *
     * @return array<string, string> Abschnittsname => fertige Felder
     */
    private function sections(object $config): array
    {
        $sections = [];

        $sections['Allgemeines'] = $this->text('Website-Name', 'name', (string)($config->name ?? ''))
            . $this->flag('title', 'Aktuellen Inhalt im Seitentitel anzeigen', $config)
            . $this->text('Seiten-Adresse', 'page', (string)($config->page ?? ''), 'Zum Beispiel https://www.beispiel.de')
            . $this->text('Mail-Adresse', 'mail', (string)($config->mail ?? ''), 'Absender der Benachrichtigungen.')
            . $this->number('Bilderqualität', 'picquali', (int)($config->picquali ?? 85), '10 bis 100.')
            . $this->flag('speciallinks', 'Suchmaschinen-freundliche Links', $config)
            . $this->flag('safemail', 'E-Mail-Adressen verschlüsseln', $config)
            . $this->flag('allow_compress', 'Seitenausgabe komprimieren', $config, 'Sofern der Browser es zulässt, verkürzt das die Ladezeit.')
            . $this->flag('editor', 'Grafischen HTML-Editor (TinyMCE) verwenden', $config)
            . $this->flag('smileys', 'Smiley-Modul aktivieren', $config);

        $sections['Modul: Sprachen'] = Form::field(
            'Sprachdatei',
            Html::select('language', $this->languageOptions(), $config->language ?? '', ['id' => 'language']),
            ['name' => 'language', 'searchable' => true]
        );

        $lists = get_lists($config->search_list ?? '', 'search_list');
        if ($lists) {
            $sections['Modul: Suche'] = Form::field('Listenansicht', (string)$lists, ['searchable' => true, 'for' => '']);
        }

        $sections['Modul: E-Mail Benachrichtigungen'] = Form::wide(
            '<p class="field-hint">Wer wird worüber benachrichtigt?</p>' . $this->notificationTable(),
            ['data-search' => 'benachrichtigung e-mail', 'x-show' => 'matches($el)']
        );

        $sections['Modul: Menü'] = Form::field(
            'Menü-Modus',
            $this->menuModeRadios($config),
            ['for' => '', 'searchable' => true]
        )
            . $this->number('Menüumbruch alle', 'menubreak', (int)($config->menubreak ?? 0), 'Einträge. 0 schaltet den Umbruch ab.')
            . Form::field('Menüausrichtung', $this->orientationRadios($config), ['for' => '', 'searchable' => true])
            . Form::field(
                'Menü-Größe',
                '<span class="field-inline">'
                . Html::input('menu_width', (int)($config->menu_width ?? 0), ['id' => 'menu_width', 'type' => 'number', 'style' => 'width:6rem'])
                . ' px breit '
                . Html::input('menu_height', (int)($config->menu_height ?? 0), ['id' => 'menu_height', 'type' => 'number', 'style' => 'width:6rem'])
                . ' px hoch</span>',
                ['for' => 'menu_width', 'searchable' => true]
            );

        $sections['Modul: Listenansicht'] = $this->number('Einträge je Seite', 'page_limit', (int)($config->page_limit ?? 15), 'Gilt auch für die Übersichten im Backend.')
            . $this->flag('commentssmall', 'Zahl der Kommentare in der Inhaltsliste anzeigen', $config)
            . $this->number('Spalten bei Ausgabe', 'list_rows', (int)($config->list_rows ?? 1));

        $sections['Modul: Inhaltsansicht'] = $this->flag('writtenby', '"Geschrieben von ..." anzeigen', $config);

        $sections['Modul: Kommentare'] = $this->flag('comments', 'Inhalte dürfen kommentiert werden', $config)
            . $this->number('Kommentare je Seite', 'numcomments', (int)($config->numcomments ?? 10));

        $sections['Modul: Bewertungen'] = $this->flag('rate', 'Bewertungs-System für Inhalte aktivieren', $config);

        $sections['Modul: Besucherzähler'] = $this->text('Passwort für Statistik-Zugriff', 'visitors_password', (string)($config->visitors_password ?? ''))
            . $this->number('Besucherzähler erhöhen um', 'visitors_increment', (int)($config->visitors_increment ?? 1))
            . $this->number('Besucher gilt als online für', 'visitors_lifetime', (int)($config->visitors_lifetime ?? 15), 'Minuten.');

        $sections['Modul: Downloads'] = $this->flag('predownload', 'Bei Downloads zunächst eine Vorschaltseite zeigen', $config);

        $sections['Modul: User-System'] = $this->flag('register_activated', 'Registrierung erlauben', $config)
            . $this->flag('password_recovery_activated', 'Passwort darf zurückgesetzt werden', $config);

        $sections['Modul: Top-Users'] = $this->flag('topusers', 'Top-Userliste aktiv', $config)
            . $this->number('Anzahl der Top-User', 'numtopuser', (int)($config->numtopuser ?? 5));

        $sections['Modul: Gästebuch'] = $this->flag('guestbook_activated', 'Neue Gästebucheinträge sind möglich', $config);

        $sections['Modul: Am Meisten diskutiert'] = $this->number('Mindestzahl an Kommentaren', 'mincomments', (int)($config->mincomments ?? 2));

        $sections['Modul: Aktuelle Kommentare'] = $this->number('Kommentare der letzten', 'latest_comments_days', (int)($config->latest_comments_days ?? 7), 'Tage anzeigen.')
            . $this->number('Zeichen, bis gekürzt wird', 'latest_comments_chars', (int)($config->latest_comments_chars ?? 120));

        return $sections;
    }

    private function text(string $label, string $field, string $value, string $hint = ''): string
    {
        return Form::field(
            $label,
            Html::input($field, $value, ['id' => $field]),
            ['name' => $field, 'hint' => $hint, 'searchable' => true]
        );
    }

    private function number(string $label, string $field, int $value, string $hint = ''): string
    {
        return Form::field(
            $label,
            Html::input($field, $value, ['id' => $field, 'type' => 'number', 'style' => 'width:8rem']),
            ['name' => $field, 'hint' => $hint, 'searchable' => true]
        );
    }

    private function flag(string $field, string $label, object $config, string $hint = ''): string
    {
        return Form::check(
            Html::checkbox($field, !empty($config->$field)),
            $label,
            ['name' => $field, 'hint' => $hint, 'searchable' => true]
        );
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

        $html = '<div class="table-wrap"><table class="data-table"><thead><tr><th class="confirm_head">Benutzer</th>';
        foreach ($dialogs as $dialog) {
            $html .= '<th class="confirm_head">' . Html::e((string)($dialog[2] ?? $dialog[0])) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $users = Db::select('SELECT * FROM ' . Db::table('user') . ' WHERE typ >= 1 ORDER BY typ DESC, name');
        foreach ($users as $user) {
            $html .= '<tr><td class="cell-title" data-label="Benutzer">' . Html::e((string)$user->name)
                . ' (' . Html::e((string)$user->mail) . ')</td>';
            foreach ($dialogs as $dialog) {
                [$field, $column] = $dialog;
                $label = (string)($dialog[2] ?? $dialog[0]);
                $checked = !empty($user->$column) ? ' checked' : '';
                $html .= '<td class="cell-check" data-label="' . Html::e($label) . '">'
                    . '<input type="checkbox" name="' . Html::e($field) . '[]"'
                    . ' value="' . (int)$user->id . '"' . $checked . '></td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }
}
