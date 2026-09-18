<?php
/**
 * Übergangsschicht für die noch nicht umgestellten Bereiche.
 *
 * Die alten Handler (admin_actions_*.php) verständigen sich über globale
 * Variablen. Diese Datei wird deshalb im globalen Gültigkeitsbereich aus
 * admin.php eingebunden und stellt genau die Variablen bereit, die sie
 * erwarten. Sie schrumpft mit jedem Bereich, der auf einen Controller
 * umgestellt wird.
 *
 * Voraussetzung: Der Benutzer ist angemeldet und besitzt Backend-Rechte.
 * Die Prüfung findet in admin.php statt, bevor diese Datei geladen wird.
 */

if (!defined('PMS_ADMIN_ENTRY')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

use Pms\Backend\Support\Request;

// Zuordnung Formularfeld => Datenbankspalte für die E-Mail-Benachrichtigungen.
// Muss vor den POST-Handlern stehen, sonst speichert der Konfigurator sie nicht.
$confirmation_dialogs = [
    ['user_guestbook', 'mail_guestbook'],
    ['user_comments', 'mail_comments'],
    ['user_register', 'mail_register'],
];

// Von den alten Handlern erwartete Zustandsvariablen
$delete = Request::queryInt('delete');
$edit = Request::queryInt('edit');
$new = $_GET['new'] ?? '';
$subcat_filter = $_SESSION['subcat_filter'] ?? '';
$item_filter = $_SESSION['item_filter'] ?? '';
$item_filter2 = $_SESSION['item_filter2'] ?? '';
$sort_do = $_GET['sort'] ?? '';
$sort_para = $_GET['pos'] ?? '';
$id_para = $_GET['id'] ?? '';
$post = 0;
$found = 0;
$select_reference = 0;

// Eingaben verarbeiten
process_content_post_handlers();
process_monitoring_post_handlers();
process_menu_post_handlers();
process_admin_post_handlers();

// Sonderfälle der Inhaltsverwaltung
if (Request::submitted('item_restore')) {
    $action = 'item_restore';
}

if (Request::submitted('item_refresh')) {
    if (Request::checkbox('tinymce_vis')) {
        $_SESSION['tinymce'] = Request::int('tinymce') + 1;
    }
    $action = 'item';
    $edit = Request::int('id');
    $post = 1;
}

if (Request::submitted('subcat_filter')) {
    $action = 'subcat';
    $subcat_filter = Request::int('uppcat');
    $_SESSION['subcat_filter'] = $subcat_filter;
}

if (Request::submitted('item_filter')) {
    $action = 'item';
    $item_filter = Request::int('uppcat');
    $_SESSION['item_filter'] = $item_filter;

    // Unterkategorie nur übernehmen, wenn sie zur Kategorie gehört
    $item_filter2 = Request::int('uppcat2');
    $belongs = \Pms\Backend\Data\Db::first(
        'SELECT id FROM ' . \Pms\Backend\Data\Db::table('subcat') . ' WHERE id = :id AND cat = :cat',
        ['id' => $item_filter2, 'cat' => $item_filter]
    );
    if ($belongs === null) {
        $item_filter2 = 0;
    }
    $_SESSION['item_filter2'] = $item_filter2;
}

handle_admin_add_image();
