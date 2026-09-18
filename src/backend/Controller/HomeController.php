<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Html;

/**
 * Startseite des Backends mit Hinweisen zu Updates und Sicherungen.
 */
final class HomeController extends Controller
{
    #[\Override]
    public function action(): string
    {
        return 'home';
    }

    #[\Override]
    public function handle(): string
    {
        $version = (string)($GLOBALS['pms_version'] ?? '');

        $html = Html::heading('Willkommen im Admin Center!')
            . '<p>Sie befinden sich nun im gesicherten Bereich der Website.<br>'
            . 'Bitte wählen Sie eine Aktion im Menü links aus.</p>';

        if (!empty($GLOBALS['set_reloadable'])) {
            $html .= $this->resumeDialog();
        }

        if (Auth::isSuperAdmin()) {
            $html .= $this->updateHint($version) . $this->backupHint();
        }

        $html .= '<p><strong>Technische Systeminformationen:</strong><br>'
            . 'Sie benutzen das Professional Management System, Version ' . Html::e($version) . '.<br>'
            . 'Um Informationen zu den Neuerungen zu sehen, klicken Sie '
            . '<a href="http://www.tsgames.de/?item=270&amp;version=' . Html::e($version) . '" target="_blank" rel="noopener">hier</a>'
            . ' und Sie gelangen zur Versionshistory.</p>';

        if (!empty($GLOBALS['pms_db_use_reference'])) {
            $referenceId = (int)($GLOBALS['pms_db_reference_id'] ?? 0);
            $html .= '<p><strong>Erweiterte Informationen:</strong><br>'
                . 'Dieses System läuft als sekundäres Referenzsystem eines anderen, primären Systems '
                . '(vermutlich stellt es den Inhalt in einer anderen Sprache bereit).<br>'
                . 'Sie müssen Kategorien, Inhalte usw. zunächst im primären System anlegen.<br>'
                . '<a href="admin.php?config_id=' . $referenceId . '">Klicken Sie hier</a>, '
                . 'um Daten im primären System anzulegen.</p>';
        }

        return $html;
    }

    /**
     * Beim letzten Abbruch war ein Vorgang offen; er kann nachgeholt werden.
     */
    private function resumeDialog(): string
    {
        return '<div class="info_ok"><p>Beim Ausführen des letzten Vorgangs (z.B. Schreiben einer Seite) '
            . 'wurden Sie abgemeldet. Die Änderungen wurden daher noch nicht übernommen.</p>'
            . '<div class="action-section">'
            . Html::button('Vorgang jetzt ausführen', Html::url('load_last'))
            . '</div>'
            . '<p>Hinweis: Wenn Sie diese Seite verlassen, gehen die Daten verloren.</p></div>';
    }

    private function updateHint(string $version): string
    {
        $latest = get_latest_version();
        if (!$latest || $latest <= $version) {
            return '';
        }

        return '<p><strong>Update-Hinweis</strong><br>'
            . 'Es ist eine aktuellere Version von PMS verfügbar (' . Html::e((string)$latest) . ').<br>'
            . 'Sie können das Update auf der Seite <a href="admin.php?modul=update">Update</a> starten.</p>';
    }

    private function backupHint(): string
    {
        $backups = get_backups();

        if (!is_array($backups) || $backups === []) {
            return $this->backupAdvice('Sie haben bisher keinerlei Backups Ihrer Website erstellt.');
        }

        $parts = explode('_', (string)$backups[0]);
        if (count($parts) < 5) {
            return '';
        }

        $last = mktime((int)$parts[3], (int)$parts[4], 0, (int)$parts[1], (int)$parts[2], (int)$parts[0]);
        if ($last >= time() - 60 * 60 * 24 * 30) {
            return '';
        }

        return $this->backupAdvice(
            'Das zuletzt durchgeführte Backup ist älter als ein Monat (vom ' . date('d.m.Y', $last) . ').'
        );
    }

    private function backupAdvice(string $message): string
    {
        return '<p><strong>Backup-Hinweis</strong><br>' . Html::e($message) . '<br>'
            . '<a href="' . Html::e(Html::url('backup')) . '">Klicken Sie hier</a>, um ein neues Backup anzulegen.</p>';
    }
}
