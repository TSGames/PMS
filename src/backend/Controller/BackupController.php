<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Csrf;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

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

    /** @param list<string> $backups */
    private function overview(array $backups): string
    {
        $rows = [];
        foreach ($backups as $position => $name) {
            $isProtected = file_exists($this->folder($name) . '/saved');

            $protectCell = $isProtected
                ? '<span class="disabled">Geschützt</span>'
                : '<a href="' . Html::e($this->url(['save' => $name] + Csrf::queryParam())) . '">Schützen</a>';

            $deleteCell = '<span class="disabled">Nicht möglich</span>';
            if ($position >= self::PROTECTED_RECENT && !$isProtected) {
                $deleteCell = '<a href="' . Html::e($this->url(['delete' => $name] + Csrf::queryParam())) . '">Löschen</a>';
            }

            $parts = explode('_', $name);
            $date = count($parts) >= 5 ? $parts[2] . '.' . $parts[1] . '.' . $parts[0] : 'Nicht auslesbar';
            $time = count($parts) >= 5 ? $parts[3] . ':' . $parts[4] : '';

            $rows[] = [
                Html::e($name),
                Html::e($date),
                Html::e($time),
                (string)round(get_filesize($this->folder($name)) / 1024 / 1024, 2),
                $deleteCell,
                $protectCell,
            ];
        }

        return Html::heading('Backup-Manager')
            . '<p>Aus Sicherheitsgründen können Sie nicht die ' . self::PROTECTED_RECENT
            . ' aktuellsten Backups löschen, bitte erledigen Sie dies auf Wunsch über FTP.<br>'
            . 'Sie finden alle Backups im Ordner "backup".</p>'
            . '<p>Das System speichert alle Kategorien, Inhalte, Variablen, Benutzer, Kommentare und Bewertungen '
            . 'sowie alle Bilder von Kategorien, Nutzern und Inhalten.<br>'
            . 'Manuell eingefügte Bilder, Dateien oder Download-Links werden nicht gesichert - sichern Sie diese '
            . 'gegebenenfalls von Hand.</p>'
            . '<p>Es wird empfohlen, wichtige Backups vor dem Löschen zu schützen. Diese lassen sich dann nur noch '
            . 'über FTP entfernen.</p>'
            . Html::table(
                ['Name', 'Datum', 'Uhrzeit', 'Größe (in MB)', 'Löschen', 'Schützen'],
                $rows,
                'Es wurden noch keine Backups angelegt. Klicken Sie auf "Neues Backup erstellen", um eines anzulegen.'
            )
            . Html::formOpen($this->action())
            . '<div class="action-section"><input type="submit" name="backup" value="Neues Backup erstellen"></div>'
            . '<p>Hinweis: Das Erzeugen eines Backups kann, abhängig vom Umfang der Website, mehrere Minuten dauern!</p>'
            . Html::formClose();
    }
}
