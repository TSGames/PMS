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
    /** Übernimmt die Auswahl aus dem Vorauswahl-Formular in die Sitzung. */
    public static function syncSession(): void
    {
        if (empty($_SESSION['tinymce'])) {
            $_SESSION['tinymce'] = (int)from_db('config', 1, 'editor') + 1;
        }
        // Muss so früh wie möglich geschehen, damit der Kopfbereich weiß,
        // ob die Editor-Skripte geladen werden müssen.
        if (Request::submitted('item_step1')) {
            $_SESSION['tinymce'] = Request::int('tinymce') + 1;
        }
    }

    public static function isEnabled(): bool
    {
        return (int)($_SESSION['tinymce'] ?? 0) === 2;
    }
}
