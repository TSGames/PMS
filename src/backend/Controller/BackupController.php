<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Backend\View\Icons;
use Pms\Support\Auth;
use Pms\Support\Csrf;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

/**
 * Backup-Manager: Sicherungen anlegen, schützen und löschen.
 */
final class BackupController extends Controller
{
    /** So viele der neuesten Sicherungen bleiben in jedem Fall erhalten. */
    private const PROTECTED_RECENT = 3;

    #[\Override]
    public function action(): string
    {
        return 'backup';
    }

    #[\Override]
    protected function requiredLevel(): int
    {
        return Auth::TYPE_SUPERADMIN;
    }

    #[\Override]
    public function handle(): string
    {
        if (Request::submitted('backup') && $this->checkToken()) {
            $this->createBackup();
        }

        $backups = $this->backups();

        $protect = Request::string('save');
        if ($protect !== '') {
            $this->protect($protect, $backups);
        }

        $delete = Request::string('delete');
        if ($delete !== '') {
            $this->deleteBackup($delete, $backups);
        }

        return $this->overview($this->backups());
    }

    /** @return list<string> */
    private function backups(): array
    {
        $backups = get_backups();
        return is_array($backups) ? array_values($backups) : [];
    }

    private function folder(string $name): string
    {
        return ($GLOBALS['backup_folder'] ?? '') . $name;
    }

    private function createBackup(): void
    {
        $result = do_export();

        if (is_array($result) && $result[0] === $result[1]) {
            Flash::success('Der Export aller Dateien war erfolgreich.');
        } elseif ($result === 0) {
            Flash::error('Der Export konnte nicht durchgeführt werden. Überprüfen Sie die Ordnerberechtigungen, oder wenden Sie sich an den Support!');
        } else {
            Flash::error('Der Export konnte nicht vollständig durchgeführt werden. Möglicherweise wird auf einige Dateien momentan zugegriffen. Versuchen Sie es später erneut!');
        }
        $this->redirect();
    }

    /** @param list<string> $backups */
    private function protect(string $name, array $backups): void
    {
        if (!$this->checkToken()) {
            $this->redirect();
        }
        if (!in_array($name, $backups, true)) {
            Flash::error('Das angegebene Backup existiert nicht!');
            $this->redirect();
        }

        $handle = @fopen($this->folder($name) . '/saved', 'w+');
        if ($handle === false) {
            Flash::error('Fehler beim Schützen (Zugriffsrechte überprüfen)');
            $this->redirect();
        }
        fclose($handle);
        Flash::success('Backup wurde geschützt!');
        $this->redirect();
    }

    /** @param list<string> $backups */
    private function deleteBackup(string $name, array $backups): void
    {
        if (!$this->checkToken()) {
            $this->redirect();
        }

        $position = array_search($name, $backups, true);
        if ($position === false) {
            Flash::error('Das angegebene Backup existiert nicht!');
            $this->redirect();
        }
        if (file_exists($this->folder($name) . '/saved')) {
            Flash::error('Das Backup kann nicht gelöscht werden (geschützt)');
            $this->redirect();
        }
        if ($position < self::PROTECTED_RECENT) {
            Flash::error('Das Backup kann nicht gelöscht werden (zu Aktuell)');
            $this->redirect();
        }

        if (delete_all($this->folder($name))) {
            Flash::success('Backup wurde gelöscht!');
        } else {
            Flash::error('Fehler beim Löschen!');
        }
        $this->redirect();
    }

    /**
     * Die Sicherungen als Zeitstrahl - die jüngste oben.
     *
     * @param list<string> $backups
     */
    private function overview(array $backups): string
    {
        return Components::pageHeader(
            'Backup-Manager',
            'Sicherungen der Datenbank und der Bilder, die neueste zuerst.',
            Html::formOpen($this->action())
            . '<input type="submit" name="backup" value="Neues Backup erstellen">'
            . Html::formClose()
        )
            . $this->hints()
            . ($backups === []
                ? Components::emptyState(
                    'Noch keine Sicherung',
                    'Klicken Sie auf "Neues Backup erstellen", um die erste anzulegen.'
                )
                : $this->timeline($backups));
    }

    /** @param list<string> $backups */
    private function timeline(array $backups): string
    {
        $html = '<div class="card"><div class="card-body"><ol class="timeline">';

        foreach ($backups as $position => $name) {
            $isProtected = file_exists($this->folder($name) . '/saved');
            $isRecent = $position < self::PROTECTED_RECENT;
            [$date, $time] = self::parseName($name);
            $size = round(get_filesize($this->folder($name)) / 1024 / 1024, 2);

            $actions = '';
            if ($isProtected) {
                $actions .= Components::chip('Geschützt', 'ok');
            } else {
                $actions .= Components::action(
                    'shield',
                    $this->url(['save' => $name] + Csrf::queryParam()),
                    'Vor dem Löschen schützen'
                );
            }
            if (!$isRecent && !$isProtected) {
                $actions .= Components::action(
                    'trash',
                    $this->url(['delete' => $name] + Csrf::queryParam()),
                    'Sicherung löschen',
                    'danger'
                );
            }

            $html .= '<li>'
                . '<div class="timeline-head">'
                . '<strong>' . Html::e($date) . ($time === '' ? '' : ', ' . Html::e($time) . ' Uhr') . '</strong>'
                . Components::chip($size . ' MB')
                . ($isRecent && !$isProtected ? Components::chip('Zu aktuell zum Löschen', 'warn') : '')
                . '<span class="timeline-actions">' . $actions . '</span>'
                . '</div>'
                . '<div class="timeline-text"><code>' . Html::e($name) . '</code></div>'
                . '</li>';
        }

        return $html . '</ol></div></div>';
    }

    /**
     * Datum und Uhrzeit aus dem Ordnernamen (JJJJ_MM_TT_HH_MM).
     *
     * @return array{0: string, 1: string}
     */
    private static function parseName(string $name): array
    {
        $parts = explode('_', $name);
        if (count($parts) < 5) {
            return [$name, ''];
        }
        return [$parts[2] . '.' . $parts[1] . '.' . $parts[0], $parts[3] . ':' . $parts[4]];
    }

    /** Was gesichert wird und was nicht. */
    private function hints(): string
    {
        return '<div class="notice notice-warn">' . Icons::render('info')
            . '<span>Gesichert werden Kategorien, Inhalte, Variablen, Benutzer, Kommentare und Bewertungen '
            . 'sowie die Bilder von Kategorien, Nutzern und Inhalten. '
            . 'Von Hand eingefügte Bilder, Dateien und Download-Ziele sind nicht enthalten. '
            . 'Die ' . self::PROTECTED_RECENT . ' neuesten Sicherungen lassen sich hier nicht löschen; '
            . 'geschützte Sicherungen nur noch über FTP. Alle liegen im Ordner "backup". '
            . 'Das Anlegen kann je nach Umfang mehrere Minuten dauern.</span></div>';
    }

}
