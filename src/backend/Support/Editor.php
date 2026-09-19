<?php

namespace Pms\Backend\Support;

/**
 * Merkt sich, ob der grafische Editor (TinyMCE) benutzt werden soll.
 *
 * In der Sitzung steht 2 für "Editor an" und 1 für "Editor aus"; die
 * Standardeinstellung stammt aus der Website-Konfiguration.
 */
final class Editor
{
    /**
     * Übernimmt eine Umschaltung aus der Adresse in die Sitzung.
     *
     * Der Editor wird über ?editor=0 bzw. ?editor=1 umgeschaltet - früher
     * geschah das über ein Kontrollkästchen in der entfallenen Vorauswahl.
     * Muss so früh wie möglich laufen, damit der Kopfbereich weiß, ob die
     * Editor-Skripte geladen werden müssen.
     */
    public static function syncSession(): void
    {
        if (empty($_SESSION['tinymce'])) {
            $_SESSION['tinymce'] = (int)from_db('config', 1, 'editor') + 1;
        }

        $requested = Request::queryInt('editor', -1);
        if ($requested === 0 || $requested === 1) {
            $_SESSION['tinymce'] = $requested + 1;
        }
    }

    public static function isEnabled(): bool
    {
        return (int)($_SESSION['tinymce'] ?? 0) === 2;
    }
}
