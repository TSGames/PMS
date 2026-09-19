<?php
/**
 * Einstiegspunkt des Administrationsbereichs.
 *
 * Ablauf einer Anfrage:
 *   1. Anwendung laden (Konfiguration, Funktionen, Abhängigkeiten)
 *   2. Anmeldung verarbeiten - setzt Cookies, muss vor jeder Ausgabe laufen
 *   3. Zubehör des angemeldeten Bereichs (Besucherzähler, Module)
 *   4. Slim beantwortet die Anfrage
 *
 * Die Schritte 2 und 3 stehen hier und nicht in einer Klasse, weil der
 * Altbestand (counter.php, update.php, modules/*) mit globalen Variablen
 * arbeitet und diese im globalen Gültigkeitsbereich erwartet.
 */

define('PMS_FRONTEND', 0);
define('PMS_BACKEND', 1);
define('PMS_ADMIN_ENTRY', 1);

require 'functions.php';
require 'backend/bootstrap.php';

use Pms\Backend\Http\Kernel;
use Pms\Backend\Http\Navigation;
use Pms\Backend\Http\Routes;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Editor;

// 2. Anmeldung, Abmeldung, offener Vorgang aus einer beendeten Sitzung
Auth::handleRequest();
Auth::resumePendingAction();
Auth::enforceBackendAccess();

$modul_name = [];
$modul_content = '';

if (Auth::isLoggedIn()) {
    Editor::syncSession();

    // Besucherzähler: counter.php ordnet den Aufruf über $action_list einer
    // Backend-Seite zu, convert_action() beschriftet sie über $action_name
    $admin_center = 1;
    $action = Routes::currentAction();
    $action_list = Navigation::actionNames();
    $action_name = Navigation::actionLabels();
    include 'counter.php';

    // Module melden sich an und liefern ihre Ausgabe
    $modul = Routes::currentModule();
    require 'backend/modules.php';
} else {
    // Angefangenen Vorgang merken, damit er nach der Anmeldung weitergeht
    if (!\Pms\Backend\Support\Request::submitted('login')) {
        store_all();
    }
}

// 4. Anfrage beantworten
Kernel::run($modul_name, $modul_content);
