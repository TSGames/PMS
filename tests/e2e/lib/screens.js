/**
 * Katalog aller Bildschirme des Admin-Backends.
 *
 * Der Katalog ist die gemeinsame Grundlage für die Screenshot-Aufnahme
 * (screenshots/all-screens.spec.js) und den Smoke-Test über alle Seiten
 * (specs/screens.spec.js).
 *
 * Felder:
 *   id       Dateiname des Screenshots, eindeutig
 *   group    Gruppierung für die Übersicht
 *   title    Bezeichnung in Berichten
 *   role     Benutzer, mit dem die Seite geöffnet wird (null = abgemeldet)
 *   url      Adresse relativ zum Server
 *   heading  Text, der auf der Seite vorkommen muss
 *   prepare  Optionale Interaktion nach dem Laden
 *   dynamic  true, wenn der Inhalt zeitabhängig ist (Screenshot-Vergleich unscharf)
 *   wide     true, wenn die Seite zusätzlich mobil aufgenommen werden soll
 */

const SCREENS = [
  // -------------------------------------------------------------- Anmeldung
  {
    id: 'login',
    group: 'Anmeldung',
    title: 'Login-Maske',
    role: null,
    url: 'admin',
    heading: 'PMS Back End Login',
    wide: true,
  },
  {
    id: 'login-falsches-passwort',
    group: 'Anmeldung',
    title: 'Login mit falschem Passwort',
    role: null,
    url: 'admin',
    heading: 'Passwort ist ungültig!',
    prepare: async (page) => {
      await page.fill('input[name="login_name"]', 'admin');
      await page.fill('input[name="login_password"]', 'falsch');
      await page.click('input[name="login"]');
    },
  },
  {
    id: 'login-zu-wenig-rechte',
    group: 'Anmeldung',
    title: 'Login ohne Backend-Berechtigung',
    role: null,
    url: 'admin',
    heading: 'Ihre Berechtigungen sind zu niedrig!',
    prepare: async (page) => {
      await page.fill('input[name="login_name"]', 'moderator');
      await page.fill('input[name="login_password"]', 'admin123');
      await page.click('input[name="login"]');
    },
  },
  {
    id: 'logout',
    group: 'Anmeldung',
    title: 'Abmeldung',
    role: 'admin',
    url: 'admin/abmelden',
    heading: 'Logout erfolgreich!',
  },

  // ------------------------------------------------------------- Startseite
  {
    id: 'home',
    group: 'Start',
    title: 'Admin-Startseite',
    role: 'admin',
    url: 'admin',
    heading: 'Willkommen im Admin Center!',
    wide: true,
  },

  // ---------------------------------------------------------- Konfiguration
  {
    id: 'config',
    group: 'Konfiguration',
    title: 'Website-Konfigurator',
    role: 'admin',
    url: 'admin/einstellungen',
    heading: 'Website-Konfiguration',
    wide: true,
  },
  {
    id: 'config-ohne-rechte',
    group: 'Konfiguration',
    title: 'Konfigurator ohne Super-Admin-Rechte',
    role: 'redakteur',
    url: 'admin/einstellungen',
    heading: 'Ihre Berechtigungen sind zu niedrig',
  },

  // ------------------------------------------------------------------ Menü
  {
    id: 'menu-liste',
    group: 'Menü',
    title: 'Menüverwaltung',
    role: 'admin',
    url: 'admin/menue',
    heading: 'Menüverwaltung',
    wide: true,
  },
  {
    id: 'menu-neu',
    group: 'Menü',
    title: 'Menüeintrag erstellen',
    role: 'admin',
    url: 'admin/menue?new=yes',
    heading: 'Menüeintrag erstellen',
  },
  {
    id: 'menu-bearbeiten',
    group: 'Menü',
    title: 'Menüeintrag bearbeiten',
    role: 'admin',
    url: 'admin/menue?edit=2',
    heading: 'Menüeintrag bearbeiten',
  },
  {
    id: 'menu-loeschen',
    group: 'Menü',
    title: 'Menüeintrag löschen (Bestätigung)',
    role: 'admin',
    url: 'admin/menue?delete=9',
    heading: 'Löschen',
  },

  // ------------------------------------------------------------- Benutzer
  {
    id: 'user-liste',
    group: 'Benutzer',
    title: 'Benutzerverwaltung',
    role: 'admin',
    url: 'admin/benutzer',
    heading: 'Benutzerverwaltung',
    wide: true,
  },
  {
    id: 'user-neu',
    group: 'Benutzer',
    title: 'Benutzer erstellen',
    role: 'admin',
    url: 'admin/benutzer?new=yes',
    heading: 'Benutzer erstellen',
  },
  {
    id: 'user-bearbeiten',
    group: 'Benutzer',
    title: 'Benutzer bearbeiten',
    role: 'admin',
    url: 'admin/benutzer?edit=2',
    heading: 'Benutzer bearbeiten',
  },
  {
    id: 'user-loeschen',
    group: 'Benutzer',
    title: 'Benutzer löschen (Bestätigung)',
    role: 'admin',
    url: 'admin/benutzer?delete=5',
    heading: 'Löschen',
  },

  // ----------------------------------------------------------- Kategorien
  {
    id: 'cat-liste',
    group: 'Kategorien',
    title: 'Kategorien',
    role: 'admin',
    url: 'admin/kategorien',
    heading: 'Kategorien',
    wide: true,
  },
  {
    id: 'cat-neu',
    group: 'Kategorien',
    title: 'Kategorie erstellen',
    role: 'admin',
    url: 'admin/kategorien?new=yes',
    heading: 'Kategorie erstellen',
  },
  {
    id: 'cat-bearbeiten',
    group: 'Kategorien',
    title: 'Kategorie bearbeiten',
    role: 'admin',
    url: 'admin/kategorien?edit=1',
    heading: 'Kategorie bearbeiten',
  },
  {
    id: 'cat-loeschen',
    group: 'Kategorien',
    title: 'Kategorie löschen (Bestätigung)',
    role: 'admin',
    url: 'admin/kategorien?delete=4',
    heading: 'Löschen von Kategorie bestätigen',
  },

  // ------------------------------------------------------- Unterkategorien
  {
    id: 'subcat-liste',
    group: 'Unterkategorien',
    title: 'Unterkategorien',
    role: 'admin',
    url: 'admin/unterkategorien',
    heading: 'Unterkategorien',
    wide: true,
  },
  {
    id: 'subcat-neu',
    group: 'Unterkategorien',
    title: 'Unterkategorie erstellen',
    role: 'admin',
    url: 'admin/unterkategorien?new=yes',
    heading: 'Unterkategorie erstellen',
  },
  {
    id: 'subcat-bearbeiten',
    group: 'Unterkategorien',
    title: 'Unterkategorie bearbeiten',
    role: 'admin',
    url: 'admin/unterkategorien?edit=1',
    heading: 'Unterkategorie bearbeiten',
  },
  {
    id: 'subcat-loeschen',
    group: 'Unterkategorien',
    title: 'Unterkategorie löschen (Bestätigung)',
    role: 'admin',
    url: 'admin/unterkategorien?delete=6',
    heading: 'Löschen',
  },

  // --------------------------------------------------------------- Inhalte
  {
    id: 'item-liste',
    group: 'Inhalte',
    title: 'Inhaltsübersicht',
    role: 'admin',
    url: 'admin/inhalte',
    heading: 'Inhalte',
    wide: true,
  },
  {
    id: 'item-neu-vorauswahl',
    group: 'Inhalte',
    title: 'Inhalt hinzufügen (Vorauswahl)',
    role: 'admin',
    url: 'admin/inhalte?new=yes',
    heading: 'Inhalt hinzufügen - Vorauswahl',
  },
  {
    id: 'item-neu-editor',
    group: 'Inhalte',
    title: 'Inhalt hinzufügen (Editor)',
    role: 'admin',
    url: 'admin/inhalte?new=yes',
    heading: 'Inhalt erstellen',
    // Beim Anlegen erscheint die Unterkategorie erst nach "Aktualisieren"
    prepare: async (page) => {
      await page.selectOption('select[name="cat"]', { label: 'Aktuelles' });
      await page.click('input[name="item_refresh"]');
      await page.selectOption('select[name="subcat"]', { label: 'Neuigkeiten' });
      await page.uncheck('input[name="tinymce"]');
      await page.click('input[name="item_step1"]');
    },
  },
  {
    id: 'item-bearbeiten-vorauswahl',
    group: 'Inhalte',
    title: 'Inhalt bearbeiten (Vorauswahl)',
    role: 'admin',
    url: 'admin/inhalte?edit=2',
    heading: 'Inhalt bearbeiten - Vorauswahl',
  },
  {
    id: 'item-bearbeiten-editor',
    group: 'Inhalte',
    title: 'Inhalt bearbeiten (Editor ohne TinyMCE)',
    role: 'admin',
    url: 'admin/inhalte?edit=2',
    heading: 'Inhalt bearbeiten',
    prepare: async (page) => {
      await page.uncheck('input[name="tinymce"]');
      await page.click('input[name="item_step1"]');
    },
  },
  {
    id: 'item-bearbeiten-tinymce',
    group: 'Inhalte',
    title: 'Inhalt bearbeiten (TinyMCE-Editor)',
    role: 'admin',
    url: 'admin/inhalte?edit=2',
    heading: 'Inhalt bearbeiten',
    prepare: async (page) => {
      await page.check('input[name="tinymce"]');
      await page.click('input[name="item_step1"]');
      await page.waitForTimeout(1500);
    },
  },
  {
    id: 'item-loeschen',
    group: 'Inhalte',
    title: 'Inhalt löschen (Bestätigung)',
    role: 'admin',
    url: 'admin/inhalte?delete=9',
    heading: 'Löschen',
  },
  {
    id: 'item-wiederherstellen',
    group: 'Inhalte',
    title: 'Gelöschten Inhalt wiederherstellen',
    role: 'admin',
    url: 'admin/inhalte/wiederherstellen',
    heading: 'Gelöschten Inhalt wiederherstellen',
  },
  {
    id: 'item-versionen',
    group: 'Inhalte',
    title: 'Inhalt aus Version wiederherstellen',
    role: 'admin',
    url: 'admin/inhalte/versionen?item=2',
    heading: 'Inhalt wiederherstellen',
  },

  // ------------------------------------------------------------- Variablen
  {
    id: 'var-liste',
    group: 'Variablen',
    title: 'Variablen/Regeln',
    role: 'admin',
    url: 'admin/variablen',
    heading: 'Variablen',
    wide: true,
  },
  {
    id: 'var-neu',
    group: 'Variablen',
    title: 'Regel erstellen',
    role: 'admin',
    url: 'admin/variablen?new=yes',
    heading: 'Neue Regel erstellen',
  },
  {
    id: 'var-bearbeiten',
    group: 'Variablen',
    title: 'Regel bearbeiten',
    role: 'admin',
    url: 'admin/variablen?edit=1',
    heading: 'Regel bearbeiten',
  },

  // --------------------------------------------------------------- Umfragen
  {
    id: 'poll-liste',
    group: 'Umfragen',
    title: 'Umfragen',
    role: 'admin',
    url: 'admin/umfragen',
    heading: 'Umfragen',
    wide: true,
  },
  {
    id: 'poll-neu',
    group: 'Umfragen',
    title: 'Umfrage erstellen',
    role: 'admin',
    url: 'admin/umfragen?new=yes',
    heading: 'Umfrage erstellen',
  },
  {
    id: 'poll-bearbeiten',
    group: 'Umfragen',
    title: 'Umfrage bearbeiten',
    role: 'admin',
    url: 'admin/umfragen?edit=1',
    heading: 'Umfrage bearbeiten',
  },

  // ------------------------------------------------------------------ Bans
  {
    id: 'bans-liste',
    group: 'Bans',
    title: 'Sperrungen',
    role: 'admin',
    url: 'admin/sperrungen',
    heading: 'Sperrungen',
    wide: true,
  },
  {
    id: 'bans-neu',
    group: 'Bans',
    title: 'Ban erstellen',
    role: 'admin',
    url: 'admin/sperrungen?new=yes',
    heading: 'Neuen Ban erstellen',
  },
  {
    id: 'bans-bearbeiten',
    group: 'Bans',
    title: 'Ban bearbeiten',
    role: 'admin',
    url: 'admin/sperrungen?edit=1',
    heading: 'Ban bearbeiten',
  },

  // ------------------------------------------------------------ Monitoring
  {
    id: 'events',
    group: 'Monitoring',
    title: 'Ereignisse',
    role: 'admin',
    url: 'admin/ereignisse',
    heading: 'Ereignisse',
    dynamic: true,
    wide: true,
  },
  {
    id: 'backup',
    group: 'Monitoring',
    title: 'Backup-Manager',
    role: 'admin',
    url: 'admin/sicherungen',
    heading: 'Backup-Manager',
    dynamic: true,
  },
  {
    id: 'activity',
    group: 'Monitoring',
    title: 'Website-Status',
    role: 'admin',
    url: 'admin/status',
    heading: 'Website-Status',
    dynamic: true,
    wide: true,
  },

  // ---------------------------------------------------------------- Module
  {
    id: 'modul-update',
    group: 'Module',
    title: 'Modul: Update',
    role: 'admin',
    url: 'admin/modul/update',
    heading: 'Updates Suchen',
    dynamic: true,
  },
];

module.exports = { SCREENS };
