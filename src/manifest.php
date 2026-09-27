<?php
/**
 * Web-App-Manifest des Backends.
 *
 * Eigene PHP-Datei statt einer statischen manifest.json, weil die
 * Installation in einem Unterverzeichnis liegen kann (siehe
 * Pms\Support\Url::base()) - start_url, scope und Icon-Pfade müssen das
 * berücksichtigen. Braucht keine Sitzung und keinen Login: Das Manifest
 * enthält nichts Sensibles, und der Browser fragt es unabhängig davon ab,
 * ob schon eine Anmeldung besteht.
 */

$script = (string)($_SERVER['SCRIPT_NAME'] ?? '/manifest.php');
$directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
$base = $directory === '/' ? '' : $directory;

header('Content-Type: application/manifest+json');

echo json_encode([
    'name' => 'PMS Administration',
    'short_name' => 'PMS Admin',
    'description' => 'Backend zur Pflege von Kategorien, Inhalten, Benutzern und Einstellungen.',
    'start_url' => $base . '/admin',
    'scope' => $base . '/admin',
    'display' => 'standalone',
    'background_color' => '#0d47a1',
    'theme_color' => '#1976d2',
    'lang' => 'de',
    'icons' => [
        ['src' => $base . '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $base . '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $base . '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
], JSON_UNESCAPED_SLASHES);
