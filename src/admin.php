<?php
/**
 * Einstiegspunkt des Administrationsbereichs.
 *
 * Ablauf einer Anfrage:
 *   1. Schnittstellen ohne Seitenausgabe (JSON)
 *   2. Anmeldung prüfen - ohne gültige Sitzung wird nichts verarbeitet
 *   3. Eingaben verarbeiten (Controller bzw. Übergangsschicht)
 *   4. Seite ausgeben
 */

define('PMS_FRONTEND', 0);
define('PMS_BACKEND', 1);
define('PMS_ADMIN_ENTRY', 1);

require 'functions.php';
require 'backend/bootstrap.php';

// Bereiche, die noch nicht auf Controller umgestellt sind
require 'admin_helpers.php';
require 'admin_templates.php';
require 'admin_actions_admin.php';
require 'admin_actions_monitoring.php';
require 'admin_actions_ui.php';
require 'admin_actions_menu.php';
require 'admin_actions_content.php';
require 'admin_action_dispatcher.php';

use Pms\Backend\Http\Router;
use Pms\Backend\Http\UpdateGate;
use Pms\Backend\Http\XlsxEndpoint;
use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Editor;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Request;
use Pms\Backend\View\Layout;

// 1. Schnittstellen, die kein HTML liefern
XlsxEndpoint::handleIfRequested();

// 2. Anmeldung: setzt Cookies und muss vor jeder Ausgabe laufen
Auth::handleRequest();
Auth::resumePendingAction();
Auth::enforceBackendAccess();

if (!Auth::isLoggedIn()) {
    // Ohne gültige Sitzung werden keinerlei Eingaben verarbeitet.
    if (!Request::submitted('login')) {
        if (Request::isPost()) {
            Flash::error('Aus Sicherheitsgründen wurde die Sitzung beendet.<br>'
                . 'Bitte geben Sie Ihre Zugangsdaten erneut ein');
        }
        // Angefangenen Vorgang merken, damit er nach der Anmeldung weitergeht
        store_all();
    }

    $rememberedName = '';
    if (!empty($_COOKIE['login_id'])) {
        $rememberedName = (string)from_db('user', (int)$_COOKIE['login_id'], 'name');
    }

    Layout::renderLogin($rememberedName);
    exit;
}

// 3. Besucherzähler des Backends
// counter.php ordnet den Aufruf über $action_list einer Backend-Seite zu
$admin_center = 1;
$action_list = \Pms\Backend\Http\Navigation::actionNames();
include 'counter.php';

Editor::syncSession();

$modul = Request::string('modul');
$action = Request::action($modul === '' ? 'home' : '');

require 'backend/modules.php';  // $modul_name, $modul_content
require 'backend/legacy.php';   // verarbeitet Eingaben, kann $action ändern

// 4. Inhalt erzeugen
ob_start();

$updateNotice = UpdateGate::render();
if ($updateNotice !== null) {
    echo $updateNotice;
} elseif ($modul !== '') {
    echo $modul_content;
} elseif (Router::handles($action)) {
    echo Router::dispatch($action);
} else {
    dispatch_admin_action($action);
}

$content = ob_get_clean();

Layout::render($content, $action, $modul_name);
