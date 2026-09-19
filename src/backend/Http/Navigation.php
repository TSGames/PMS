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
     * Alle Navigationspunkte in fester Reihenfolge.
     *
     * Die Reihenfolge ist Teil der Datenhaltung: Der Besucherzähler legt den
     * Index dieser Liste ab (counter.php, convert_action). Neue Punkte gehören
     * deshalb ans Ende, nicht in die Mitte. Wie die Seitenleiste sie anordnet,
     * bestimmt groups().
     *
     * @return list<array{action: string, label: string, info: string, icon: string, href?: string, target?: string}>
     */
    public static function items(): array
    {
        return [
            ['action' => 'home', 'label' => 'Home', 'icon' => 'home', 'info' => 'Anzeigen der Startseite von PMS'],
            ['action' => 'config', 'label' => 'Website-Konfigurator', 'icon' => 'settings', 'info' => 'Festlegen globaler Einstellungen für diese Website'],
            ['action' => 'menu', 'label' => 'Menü', 'icon' => 'menu', 'info' => 'Konfigurieren und Anpassen der Menü-Einträge'],
            ['action' => 'user', 'label' => 'Benutzerverwaltung', 'icon' => 'users', 'info' => 'Verwaltung und Rechtevergabe der Benutzerkonten'],
            ['action' => 'cat', 'label' => 'Kategorien', 'icon' => 'folder', 'info' => 'Anlegen, Bearbeiten und Löschen von Haupt-Kategorien'],
            ['action' => 'subcat', 'label' => 'Unterkategorien', 'icon' => 'folders', 'info' => 'Anlegen, Bearbeiten und Löschen von Unter-Kategorien'],
            ['action' => 'item', 'label' => 'Inhalte', 'icon' => 'document', 'info' => 'Erstellen, Bearbeiten und Löschen von Textseiten sowie Wiederherstellung aus Backups'],
            ['action' => 'var', 'label' => 'Variablen', 'icon' => 'variable', 'info' => 'Konfigurieren von veränderbaren Platzhaltern'],
            ['action' => 'poll', 'label' => 'Umfragen', 'icon' => 'poll', 'info' => 'Festlegen von Fragen & Antworten für das Umfragen-Plugin'],
            ['action' => 'bans', 'label' => 'Bans/Sperrungen', 'icon' => 'shield', 'info' => 'Bestimmte IP-Adressen dauerhaft oder vorübergehend sperren'],
            ['action' => 'events', 'label' => 'Ereignisse', 'icon' => 'clock', 'info' => 'Übersichtliche Liste der Ereignisse auf der Website'],
            ['action' => 'backup', 'label' => 'Backup-Manager', 'icon' => 'archive', 'info' => 'Erstellen und Löschen von Datenbank & System-Backups'],
            ['action' => 'activity', 'label' => 'Website-Status', 'icon' => 'pulse', 'info' => 'Anzeige aktueller Website-Aktivitäten'],
            ['action' => 'page', 'label' => 'Website anzeigen', 'icon' => 'globe', 'info' => 'Die Website anzeigen', 'href' => 'index.php', 'target' => '_blank'],
        ];
    }

    /**
     * Aufbau der Seitenleiste: Überschrift und die Aktionen darunter.
     *
     * Der erste Abschnitt trägt keine Überschrift; dort stehen die Punkte,
     * die zu keiner Gruppe gehören.
     *
     * @var array<string, list<string>>
     */
    private const GROUPS = [
        '' => ['home'],
        'Inhalt' => ['item', 'var', 'poll'],
        'Struktur' => ['menu', 'cat', 'subcat'],
        'Benutzer' => ['user', 'bans'],
        'System' => ['config', 'backup', 'events', 'activity'],
        'Website' => ['page'],
    ];

    /**
     * Die Navigationspunkte nach Gruppen geordnet - ohne die Punkte, die der
     * angemeldeten Stufe verschlossen sind. Leere Gruppen entfallen.
     *
     * @return list<array{label: string, items: list<array{action: string, label: string, info: string, icon: string, href?: string, target?: string}>}>
     */
    public static function groups(): array
    {
        $byAction = array_column(self::items(), null, 'action');

        $groups = [];
        foreach (self::GROUPS as $label => $actions) {
            $entries = [];
            foreach ($actions as $action) {
                if (isset($byAction[$action]) && self::isAllowed($action)) {
                    $entries[] = $byAction[$action];
                }
            }
            if ($entries !== []) {
                $groups[] = ['label' => $label, 'items' => $entries];
            }
        }

        return $groups;
    }

    /** Beschriftung einer Aktion, für Seitentitel und Brotkrumen. */
    public static function label(string $action): string
    {
        foreach (self::items() as $entry) {
            if ($entry['action'] === $action) {
                return $entry['label'];
            }
        }
        return '';
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
