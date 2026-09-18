<?php

namespace Pms\Backend\Http;

use Pms\Backend\Support\Auth;

/**
 * Die Navigationspunkte des Backends.
 *
 * Bisher standen Beschriftung, Aktion und Beschreibung in drei parallelen
 * Feldern in admin.php. Hier stehen sie an einer Stelle.
 */
final class Navigation
{
    /**
     * @return list<array{action: string, label: string, info: string, href?: string, target?: string}>
     */
    public static function items(): array
    {
        return [
            ['action' => 'home', 'label' => 'Home', 'info' => 'Anzeigen der Startseite von PMS'],
            ['action' => 'config', 'label' => 'Website-Konfigurator', 'info' => 'Festlegen globaler Einstellungen für diese Website'],
            ['action' => 'menu', 'label' => 'Menü', 'info' => 'Konfigurieren und Anpassen der Menü-Einträge'],
            ['action' => 'user', 'label' => 'Benutzerverwaltung', 'info' => 'Verwaltung und Rechtevergabe der Benutzerkonten'],
            ['action' => 'cat', 'label' => 'Kategorien', 'info' => 'Anlegen, Bearbeiten und Löschen von Haupt-Kategorien'],
            ['action' => 'subcat', 'label' => 'Unterkategorien', 'info' => 'Anlegen, Bearbeiten und Löschen von Unter-Kategorien'],
            ['action' => 'item', 'label' => 'Inhalte', 'info' => 'Erstellen, Bearbeiten und Löschen von Textseiten sowie Wiederherstellung aus Backups'],
            ['action' => 'var', 'label' => 'Variablen', 'info' => 'Konfigurieren von veränderbaren Platzhaltern'],
            ['action' => 'poll', 'label' => 'Umfragen', 'info' => 'Festlegen von Fragen & Antworten für das Umfragen-Plugin'],
            ['action' => 'bans', 'label' => 'Bans/Sperrungen', 'info' => 'Bestimmte IP-Adressen dauerhaft oder vorübergehend sperren'],
            ['action' => 'events', 'label' => 'Ereignisse', 'info' => 'Übersichtliche Liste der Ereignisse auf der Website'],
            ['action' => 'backup', 'label' => 'Backup-Manager', 'info' => 'Erstellen und Löschen von Datenbank & System-Backups'],
            ['action' => 'activity', 'label' => 'Website-Status', 'info' => 'Anzeige aktueller Website-Aktivitäten'],
            ['action' => 'page', 'label' => 'Website anzeigen', 'info' => 'Die Website anzeigen', 'href' => 'index.php', 'target' => '_blank'],
        ];
    }

    /**
     * Nur die Aktionsnamen in Reihenfolge der Navigation.
     * Wird vom Besucherzähler für die Statistik benötigt.
     *
     * @return list<string>
     */
    public static function actionNames(): array
    {
        return array_column(self::items(), 'action');
    }

    /**
     * Nur die Beschriftungen in Reihenfolge der Navigation.
     * convert_action() benennt damit die besuchte Backend-Seite.
     *
     * @return list<string>
     */
    public static function actionLabels(): array
    {
        return array_column(self::items(), 'label');
    }

    /** Aktionen, die nur Super-Administratoren offenstehen. */
    public static function requiresSuperAdmin(string $action): bool
    {
        return in_array($action, ['config', 'bans', 'backup'], true);
    }

    /** Darf der angemeldete Benutzer diese Aktion sehen? */
    public static function isAllowed(string $action): bool
    {
        if (self::requiresSuperAdmin($action)) {
            return Auth::isSuperAdmin();
        }
        return Auth::isLoggedIn();
    }
}
