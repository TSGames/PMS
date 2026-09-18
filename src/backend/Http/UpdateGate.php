<?php

namespace Pms\Backend\Http;

use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Datenbank-Updates.
 *
 * Liegt eine update.sql vor, muss sie installiert werden, bevor das
 * Backend wieder normal benutzbar ist. Referenzsysteme prüfen zusätzlich,
 * ob das Hauptsystem eine neuere Version benutzt.
 */
final class UpdateGate
{
    /**
     * Liefert HTML, wenn der Zugriff auf das Backend blockiert ist,
     * sonst null.
     */
    public static function render(): ?string
    {
        self::ensureVersionFile();

        if (Request::string('action') === 'update' && file_exists('update.sql')) {
            self::install();
            return null;
        }

        $referenceNotice = self::referenceNotice();
        if ($referenceNotice !== null) {
            return $referenceNotice;
        }

        return self::pendingUpdateNotice();
    }

    /** Name der Datei, in der die installierte Datenbankversion steht. */
    private static function versionFile(): string
    {
        $configId = $_SESSION['config_id'] ?? '';
        return $configId !== '' ? 'update_' . (int)$configId . '.info' : 'update.info';
    }

    private static function installedVersion(): string
    {
        return trim((string)@file_get_contents(self::versionFile()));
    }

    private static function ensureVersionFile(): void
    {
        $file = self::versionFile();
        if (!file_exists($file)) {
            @file_put_contents($file, (string)($GLOBALS['pms_version'] ?? ''));
        }
    }

    /** Führt die Datenbank-Aktualisierung aus. */
    private static function install(): void
    {
        if (!Auth::isSuperAdmin()) {
            Flash::error('Sie müssen Super-Administrator sein, um das Update auszuführen.');
            return;
        }

        $counts = update_engine(1, (int)self::installedVersion(), $GLOBALS['pms_db_prefix'] ?? '');
        if ($counts[0] === $counts[1]) {
            @file_put_contents(self::versionFile(), (string)($GLOBALS['pms_version'] ?? ''));
            Flash::success('Update erfolgreich installiert!');
        } else {
            Flash::error('Fehler bei der Installation des Updates.<br>Bitte melden Sie das Problem an den Support!<br>Weitere Informationen in der Datei update_sql.log');
        }
        @unlink('update.sql');
    }

    /** Hinweis für Referenzsysteme, deren Hauptsystem neuer ist. */
    private static function referenceNotice(): ?string
    {
        if (empty($GLOBALS['pms_db_use_reference'])) {
            return null;
        }

        $referenceId = (int)($GLOBALS['pms_db_reference_id'] ?? 0);
        $suffix = $referenceId > 0 ? '_' . $referenceId : '';
        $referenceVersion = trim((string)@file_get_contents('update' . $suffix . '.info'));

        if (self::installedVersion() >= $referenceVersion || file_exists('update.sql')) {
            return null;
        }

        return Html::heading('Updates notwendig')
            . '<p>Dieses Web-System ist ein Referenz-System eines anderen Systems.<br>'
            . 'Es wurde jedoch festgestellt, dass das primäre System eine andere Datenbank-Version benutzt.</p>'
            . '<p>Klicken Sie bitte auf den Button unten, um ein Update auszuführen.</p>'
            . self::actionBlock('System aktualisieren', 'update.php?action=do');
    }

    /** Hinweis, dass eine vorliegende update.sql installiert werden muss. */
    private static function pendingUpdateNotice(): ?string
    {
        if (!file_exists('update.sql')) {
            return null;
        }

        $pending = update_engine(0, (int)self::installedVersion());
        if (!$pending[0]) {
            @unlink('update.sql');
            return null;
        }

        return Html::heading('Update Installieren')
            . '<p>Das System wurde aktualisiert.<br>Bevor jedoch die volle Funktionalität der neuen Version '
            . 'verfügbar ist, muss die Installation abgeschlossen werden.</p>'
            . '<p>Klicken Sie bitte auf den Button unten, um die Installation durchzuführen.<br>'
            . '<strong>Wichtig:</strong> Erstellen Sie nach erfolgreichem Update ein Backup der Website!</p>'
            . self::actionBlock('Update Installieren', Html::url('update'));
    }

    /** Schalter, der nur Super-Administratoren zur Verfügung steht. */
    private static function actionBlock(string $label, string $href): string
    {
        if (Auth::isSuperAdmin()) {
            return '<div class="action-section">' . Html::button($label, $href) . '</div>';
        }

        return '<div class="action-section"><span class="button disabled">' . Html::e($label) . '</span></div>'
            . '<p class="disabled">Sie müssen Super-Administrator sein, um den Vorgang fortzusetzen.<br><br>'
            . 'Ein Zugriff auf die Administrationsoberfläche ist erst möglich, wenn ein Super-Administrator '
            . 'diesen Vorgang abgeschlossen hat.</p>';
    }
}
