<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;
use Pms\Data\Db;
use Pms\Data\EventFeed;
use Pms\Support\Auth;
use Pms\Support\Html;

/**
 * Startseite des Backends.
 *
 * Der Altbestand begrüßte mit drei Absätzen Fließtext und verwies auf das
 * Menü links. Hier stehen stattdessen die Zahlen der Website, die jüngsten
 * Ereignisse und die Aktionen, die am häufigsten gebraucht werden.
 */
final class HomeController extends Controller
{
    /** So viele Ereignisse zeigt die Startseite. */
    private const RECENT_EVENTS = 5;

    /** Eine Sicherung gilt danach als überfällig. */
    private const BACKUP_MAX_AGE = 30 * 86400;

    #[\Override]
    public function action(): string
    {
        return 'home';
    }

    #[\Override]
    public function handle(): string
    {
        return Components::pageHeader(
            'Willkommen im Admin Center!',
            'Der Stand Ihrer Website auf einen Blick.',
            $this->quickActions()
        )
            . $this->notices()
            . $this->statistics()
            . '<div class="dashboard-columns">'
            . $this->recentEvents()
            . $this->systemCard()
            . '</div>';
    }

    /** Die Aktionen, die am häufigsten gebraucht werden. */
    private function quickActions(): string
    {
        $actions = Components::primary('Inhalt anlegen', Html::url('item', ['new' => 'yes']));

        if (Auth::isSuperAdmin()) {
            $actions .= Components::secondary('Sicherung erstellen', Html::url('backup'), 'archive');
        }

        return $actions . Components::secondary('Website ansehen', 'index.php', 'globe');
    }

    /** Hinweise, die eine Handlung verlangen. */
    private function notices(): string
    {
        $html = '';

        if (!empty($GLOBALS['set_reloadable'])) {
            $html .= $this->resumeNotice();
        }

        if (Auth::isSuperAdmin()) {
            $html .= $this->updateNotice() . $this->backupNotice();
        }

        if (!empty($GLOBALS['pms_db_use_reference'])) {
            $referenceId = (int)($GLOBALS['pms_db_reference_id'] ?? 0);
            $html .= '<div class="notice notice-warn">' . Icons::render('info')
                . '<span>Dieses System läuft als sekundäres Referenzsystem eines anderen Systems. '
                . 'Kategorien und Inhalte werden zuerst im primären System angelegt. '
                . '<a href="admin.php?config_id=' . $referenceId . '">Zum primären System</a>.</span></div>';
        }

        return $html;
    }

    /** Die Zahlen der Website als Karten. */
    private function statistics(): string
    {
        $config = $GLOBALS['config_values'] ?? null;

        $cards = [
            ['document', 'Inhalte', Db::count('item'), 'davon ' . Db::count('item', 'available = 1') . ' verfügbar', Html::url('item')],
            ['folder', 'Kategorien', Db::count('cat'), Db::count('subcat') . ' Unterkategorien', Html::url('cat')],
            ['users', 'Benutzer', Db::count('user'), Db::count('user', 'typ >= 2') . ' mit Backend-Zugang', Html::url('user')],
            ['globe', 'Besucher heute', (int)($config->visitors_today ?? 0), 'gestern ' . (int)($config->visitors_yesterday ?? 0), Html::url('activity')],
        ];

        $html = '<div class="stat-grid">';
        foreach ($cards as [$icon, $label, $value, $hint, $href]) {
            $html .= '<a class="stat stat-link" href="' . Html::e($href) . '">'
                . '<span class="stat-label">' . Icons::render($icon, 'icon icon-sm') . Html::e($label) . '</span>'
                . '<span class="stat-value">' . Html::e(number_format((int)$value, 0, ',', '.')) . '</span>'
                . '<span class="stat-hint">' . Html::e((string)$hint) . '</span>'
                . '</a>';
        }

        return $html . '</div>';
    }

    /** Die jüngsten Ereignisse als kurzer Zeitstrahl. */
    private function recentEvents(): string
    {
        $events = array_slice(EventFeed::collect(), 0, self::RECENT_EVENTS);

        $body = $events === []
            ? '<p class="field-hint">Auf der Website ist bisher nichts passiert.</p>'
            : '';

        if ($events !== []) {
            $body = '<ol class="timeline">';
            foreach ($events as $event) {
                $body .= '<li>'
                    . '<div class="timeline-head">' . Components::chip($event['type'])
                    . '<span class="timeline-time">' . Html::e(Html::date($event['time'], 'd.m.Y, H:i')) . '</span>'
                    . '</div>'
                    // Der Text ist bereits maskiert und enthält Verlinkungen
                    . '<div class="timeline-text">' . $event['text'] . '</div>'
                    . '</li>';
            }
            $body .= '</ol>';
        }

        return '<div class="card">'
            . '<div class="card-header"><span class="card-title">Zuletzt passiert</span>'
            . '<a href="' . Html::e(Html::url('events')) . '">Alle Ereignisse</a></div>'
            . '<div class="card-body">' . $body . '</div>'
            . '</div>';
    }

    /** Version und letzte Sicherung. */
    private function systemCard(): string
    {
        $version = (string)($GLOBALS['pms_version'] ?? '');
        $backup = $this->lastBackup();

        $rows = [
            ['PMS-Version', Html::e($version)],
            ['Letzte Sicherung', $backup === null ? 'keine' : Html::e(date('d.m.Y, H:i', $backup))],
            ['Angemeldet als', Html::e(Auth::userName())],
        ];

        $html = '<div class="card">'
            . '<div class="card-header"><span class="card-title">System</span></div>'
            . '<div class="card-body"><dl class="definition-list">';

        foreach ($rows as [$label, $value]) {
            $html .= '<dt>' . Html::e($label) . '</dt><dd>' . $value . '</dd>';
        }

        return $html . '</dl></div></div>';
    }

    /** Beim letzten Abbruch war ein Vorgang offen; er kann nachgeholt werden. */
    private function resumeNotice(): string
    {
        return '<div class="notice notice-warn">' . Icons::render('warning')
            . '<span>Beim letzten Vorgang wurden Sie abgemeldet, die Änderung ist noch nicht übernommen. '
            . '<a href="' . Html::e(Html::url('load_last')) . '">Jetzt ausführen</a> - '
            . 'wenn Sie diese Seite verlassen, geht sie verloren.</span></div>';
    }

    private function updateNotice(): string
    {
        $version = (string)($GLOBALS['pms_version'] ?? '');
        $latest = get_latest_version();

        if (!$latest || $latest <= $version) {
            return '';
        }

        return '<div class="notice notice-warn">' . Icons::render('refresh')
            . '<span>Version ' . Html::e((string)$latest) . ' ist verfügbar (Sie nutzen ' . Html::e($version) . '). '
            . '<a href="' . Html::e(Html::asset('admin/modul/update')) . '">Update starten</a>.</span></div>';
    }

    private function backupNotice(): string
    {
        $backup = $this->lastBackup();

        if ($backup === null) {
            return $this->backupAdvice('Es wurde noch keine Sicherung Ihrer Website angelegt.');
        }
        if ($backup >= time() - self::BACKUP_MAX_AGE) {
            return '';
        }

        return $this->backupAdvice(
            'Die letzte Sicherung ist vom ' . date('d.m.Y', $backup) . ' und damit älter als einen Monat.'
        );
    }

    private function backupAdvice(string $message): string
    {
        return '<div class="notice notice-warn">' . Icons::render('archive')
            . '<span>' . Html::e($message)
            . ' <a href="' . Html::e(Html::url('backup')) . '">Jetzt eine anlegen</a>.</span></div>';
    }

    /** Zeitpunkt der jüngsten Sicherung, oder null. */
    private function lastBackup(): ?int
    {
        $backups = get_backups();
        if (!is_array($backups) || $backups === []) {
            return null;
        }

        // Ordnername: JJJJ_MM_TT_HH_MM
        $parts = explode('_', (string)$backups[0]);
        if (count($parts) < 5) {
            return null;
        }

        $time = mktime((int)$parts[3], (int)$parts[4], 0, (int)$parts[1], (int)$parts[2], (int)$parts[0]);
        return $time === false ? null : $time;
    }
}
