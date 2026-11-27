<?php

namespace Pms\Support;

/**
 * Merkt sich, ob der grafische Editor benutzt werden soll und welcher
 * der beiden Editoren das ist - Quill oder TinyMCE.
 *
 * In der Sitzung steht 2 für "Editor an" und 1 für "Editor aus"; die
 * Standardeinstellung stammt aus der Website-Konfiguration. Die Wahl
 * des Editors selbst gilt nur für die Sitzung: Quill ist die Vorgabe,
 * weil es klein und touch-tauglich ist; TinyMCE bleibt wählbar für alle,
 * die dessen zusätzliche Formate (Tabellen, Medien-Einbettung) brauchen.
 */
final class Editor
{
    /**
     * Übernimmt eine Umschaltung aus der Adresse in die Sitzung.
     *
     * Der Editor wird über ?editor=0 bzw. ?editor=1 umgeschaltet, die
     * Auswahl des Editors über ?editorengine=quill bzw. ?editorengine=tinymce -
     * früher geschah das über ein Kontrollkästchen in der entfallenen
     * Vorauswahl. Muss so früh wie möglich laufen, damit der Kopfbereich
     * weiß, ob und welche Editor-Skripte geladen werden müssen.
     */
    public static function syncSession(): void
    {
        if (empty($_SESSION['richeditor'])) {
            $_SESSION['richeditor'] = (int)from_db('config', 1, 'editor') + 1;
        }

        $requested = Request::queryInt('editor', -1);
        if ($requested === 0 || $requested === 1) {
            $_SESSION['richeditor'] = $requested + 1;
        }

        if (empty($_SESSION['editorengine'])) {
            $_SESSION['editorengine'] = 'quill';
        }

        $requestedEngine = Request::string('editorengine');
        if ($requestedEngine === 'quill' || $requestedEngine === 'tinymce') {
            $_SESSION['editorengine'] = $requestedEngine;
        }
    }

    public static function isEnabled(): bool
    {
        return (int)($_SESSION['richeditor'] ?? 0) === 2;
    }

    /** Gilt nur für diese Anfrage, siehe requireTinymceFor(). */
    private static bool $tinymceRequired = false;

    /**
     * Legt für diese Anfrage TinyMCE fest, wenn der Text [php]- oder
     * [code]-Blöcke enthält.
     *
     * Quill liest den gespeicherten Text als HTML ein: Aus "$i<count($a)"
     * wird ein unbekanntes Element, und beim Speichern fehlt alles dahinter
     * (BEFUNDE B33). TinyMCE reicht solche Blöcke über seine Einstellung
     * "protect" unverändert durch, siehe get_tinymceinit(). Muss laufen,
     * bevor der Kopfbereich die Editor-Skripte einbindet.
     */
    public static function requireTinymceFor(string $content): void
    {
        if (preg_match('/\[(php|code)\]/i', $content) === 1) {
            self::$tinymceRequired = true;
        }
    }

    /** Ob der Inhalt dieser Anfrage TinyMCE erzwingt. */
    public static function tinymceRequired(): bool
    {
        return self::$tinymceRequired;
    }

    /** @return 'quill'|'tinymce' */
    public static function engine(): string
    {
        if (self::$tinymceRequired) {
            return 'tinymce';
        }

        return $_SESSION['editorengine'] ?? 'quill';
    }
}
