<?php

namespace Pms\Backend\View;

/**
 * Symbole der Oberfläche.
 *
 * Alle Zeichnungen sind selbst angelegt (24x24, Strichstärke 1.75,
 * `currentColor`), damit keine fremde Bibliothek und keine Lizenzfrage
 * dazukommt. Verwendung: Icons::render('save', 'icon-lg').
 */
final class Icons
{
    /** @var array<string, string> Pfad-Daten je Symbol */
    private const PATHS = [
        // Navigation
        'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10.5V20h12v-9.5"/><path d="M10 20v-5h4v5"/>',
        'settings' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.4M12 18.6V21M21 12h-2.4M5.4 12H3M18.4 5.6l-1.7 1.7M7.3 16.7l-1.7 1.7M18.4 18.4l-1.7-1.7M7.3 7.3 5.6 5.6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h10"/>',
        'users' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c0-3 2.5-4.8 5.5-4.8s5.5 1.8 5.5 4.8"/><path d="M16 5.5a3 3 0 0 1 0 6"/><path d="M17.5 14.5c1.9.5 3 1.9 3 4.5"/>',
        'folder' => '<path d="M3.5 7.5A1.5 1.5 0 0 1 5 6h4l2 2.2h8a1.5 1.5 0 0 1 1.5 1.5v8A1.5 1.5 0 0 1 19 19.2H5a1.5 1.5 0 0 1-1.5-1.5z"/>',
        'folders' => '<path d="M3.5 9A1.5 1.5 0 0 1 5 7.5h3.5L10 9.5h5.5A1.5 1.5 0 0 1 17 11v6.5A1.5 1.5 0 0 1 15.5 19H5a1.5 1.5 0 0 1-1.5-1.5z"/><path d="M7 7V6a1.5 1.5 0 0 1 1.5-1.5H12l1.5 2H19A1.5 1.5 0 0 1 20.5 8v6.5"/>',
        'document' => '<path d="M6.5 3.5h7L18.5 8v12.5h-12z"/><path d="M13.5 3.5V8h5"/><path d="M9 12.5h6M9 16h6"/>',
        'variable' => '<path d="M8.5 5C6.5 7 6 9.4 6 12s.5 5 2.5 7"/><path d="M15.5 5c2 2 2.5 4.4 2.5 7s-.5 5-2.5 7"/><path d="m10 9.5 4 5M14 9.5l-4 5"/>',
        'poll' => '<path d="M6 19V11M12 19V5M18 19v-6"/>',
        'shield' => '<path d="M12 3.5 19 6v6c0 4-3 7-7 8.5C8 19 5 16 5 12V6z"/><path d="m9.5 12 1.8 1.8 3.4-3.6"/>',
        'pulse' => '<path d="M3.5 12.5h4l2-6 3.5 12 2.5-6h5"/>',
        'archive' => '<path d="M3.5 6.5h17v3.5h-17z"/><path d="M5.5 10v9.5h13V10"/><path d="M10 13.5h4"/>',
        'globe' => '<circle cx="12" cy="12" r="8.2"/><path d="M4 12h16"/><path d="M12 3.8c2.2 2.3 3.3 5 3.3 8.2s-1.1 5.9-3.3 8.2c-2.2-2.3-3.3-5-3.3-8.2S9.8 6.1 12 3.8z"/>',
        'refresh' => '<path d="M19.5 12a7.5 7.5 0 1 1-2.4-5.5"/><path d="M19.5 4.5V9H15"/>',

        // Aktionen
        'plus' => '<path d="M12 5.5v13M5.5 12h13"/>',
        'edit' => '<path d="m15.5 5.5 3 3L9 18l-4 1 1-4z"/><path d="M13.5 7.5 16 10"/>',
        'trash' => '<path d="M5 7h14"/><path d="M9.5 7V5.5h5V7"/><path d="M6.8 7.5 7.6 19h8.8l.8-11.5"/><path d="M10.5 10.5v5.5M13.5 10.5v5.5"/>',
        'copy' => '<path d="M9 9h9.5v11.5H9z"/><path d="M15 6H5.5v11H9"/>',
        'search' => '<circle cx="11" cy="11" r="5.5"/><path d="m15.2 15.2 4 4"/>',
        'check' => '<path d="m5.5 12.5 4.2 4.2 8.8-9.4"/>',
        'close' => '<path d="m6.5 6.5 11 11M17.5 6.5l-11 11"/>',
        'chevron-up' => '<path d="m7 14 5-5 5 5"/>',
        'chevron-down' => '<path d="m7 10 5 5 5-5"/>',
        'chevron-left' => '<path d="m14 7-5 5 5 5"/>',
        'chevron-right' => '<path d="m10 7 5 5-5 5"/>',
        'image' => '<rect x="4" y="5.5" width="16" height="13" rx="1.5"/><circle cx="9" cy="10" r="1.6"/><path d="m5.5 16.5 4-4 3.5 3.5 2.5-2.5 3 3"/>',
        'clock' => '<circle cx="12" cy="12" r="8.2"/><path d="M12 7.5V12l3 2"/>',
        'logout' => '<path d="M14 7.5V5.5H5.5v13H14v-2"/><path d="M10.5 12h9"/><path d="m16.5 9 3 3-3 3"/>',
        'warning' => '<path d="M12 4.5 21 19.5H3z"/><path d="M12 10v4"/><path d="M12 16.6v.4"/>',
        'info' => '<circle cx="12" cy="12" r="8.2"/><path d="M12 11v5"/><path d="M12 8.3v.4"/>',
        'filter' => '<path d="M4.5 6.5h15l-5.8 6.6V19l-3.4-2v-3.9z"/>',
        'eye' => '<path d="M3 12s3.5-5.5 9-5.5S21 12 21 12s-3.5 5.5-9 5.5S3 12 3 12z"/><circle cx="12" cy="12" r="2.4"/>',
        'help' => '<circle cx="12" cy="12" r="8.2"/><path d="M9.7 9.6a2.4 2.4 0 1 1 2.9 2.7v1.4"/><path d="M12.5 16.4v.4"/>',
    ];

    /** Gibt ein Symbol als eingebettetes SVG aus. */
    public static function render(string $name, string $class = 'icon'): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['info'];

        return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" width="20" height="20"'
            . ' fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"'
            . ' stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
    }

    public static function has(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }
}
