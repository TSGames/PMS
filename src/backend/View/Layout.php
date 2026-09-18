<?php

namespace Pms\Backend\View;

use Pms\Backend\Http\Navigation;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;

/**
 * Das Grundgerüst des Backends: Kopfbereich, Seitenleiste, Inhaltsbereich.
 *
 * Bisher war das Markup über admin.php verteilt und wurde in mehreren
 * Ausgabepuffern zusammengesetzt. Hier liegt es an einer Stelle.
 */
final class Layout
{
    /**
     * Gibt die vollständige Seite aus.
     *
     * @param string $content      Fertiges HTML des Inhaltsbereichs
     * @param string $activeAction Aktuell gewählte Aktion (für die Markierung)
     * @param list<array{action: string, label: string}> $modules Zusätzliche Module
     */
    public static function render(string $content, string $activeAction = '', array $modules = []): void
    {
        $data = [
            'content' => $content,
            'activeAction' => $activeAction,
            'modules' => $modules,
            'siteName' => self::siteName(),
            'userName' => Auth::userName(),
            'navigation' => Navigation::items(),
            'messages' => Flash::render(),
        ];
        self::template('page', $data);
    }

    /** Gibt die Anmeldemaske aus. */
    public static function renderLogin(string $rememberedName = ''): void
    {
        self::template('login', [
            'siteName' => self::siteName(),
            'rememberedName' => $rememberedName,
            'messages' => Flash::render(),
        ]);
    }

    /** Kopfbereich mit Stylesheets und Skripten. */
    public static function head(string $title): string
    {
        $tinymce = self::tinymceRequested() ? get_tinymce() : '';
        // Token für Anfragen, die das Skript selbst absetzt (Zuschneiden, Import)
        $token = Auth::isLoggedIn()
            ? '<script>window.PMS_TOKEN=' . json_encode(\Pms\Backend\Support\Csrf::token()) . ';</script>'
            : '';

        return '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . Html::e($title) . '</title>
<link rel="stylesheet" type="text/css" href="admin.css">
<link rel="stylesheet" type="text/css" href="crop_modal.css">
<link rel="icon" type="image/svg+xml" href="admin-favicon.svg">
<link rel="icon" type="image/x-icon" href="admin.ico">
<script>
// Farbschema vor dem ersten Zeichnen setzen, um Flackern zu vermeiden
(function(){
    var stored=null;
    try{stored=localStorage.getItem("adminTheme");}catch(e){}
    if(stored==="dark")document.documentElement.classList.add("dark");
    else if(stored==="light")document.documentElement.classList.add("light");
})();
</script>
' . $token . $tinymce . '
<script type="text/javascript" src="drag.js"></script>
<script type="text/javascript" src="crop_modal.js"></script>
<script type="text/javascript" src="js/admin-forms.js"></script>
<script type="text/javascript" src="js/admin-tables.js"></script>
<script type="text/javascript" src="js/admin-dialogs.js"></script>
<script type="text/javascript" src="js/admin-image.js"></script>
<script type="text/javascript" src="js/admin-theme.js"></script>
</head>
';
    }

    /** Titel des Browserfensters. */
    public static function title(): string
    {
        return 'PMS Administration (BackEnd) - ' . self::siteName();
    }

    public static function siteName(): string
    {
        $config = $GLOBALS['config_values'] ?? null;
        return $config?->name ?? 'PMS';
    }

    /** Wird der grafische Editor auf dieser Seite benötigt? */
    private static function tinymceRequested(): bool
    {
        return (int)($_SESSION['tinymce'] ?? 0) === 2
            || ($_GET['modul'] ?? '') === 'newsletter';
    }

    /** Bindet ein Template ein und liefert dessen Ausgabe direkt aus. */
    private static function template(string $name, array $data): void
    {
        $file = __DIR__ . '/templates/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template nicht gefunden: ' . $name);
        }
        (static function (string $file, array $data): void {
            extract($data, EXTR_SKIP);
            require $file;
        })($file, $data);
    }
}
