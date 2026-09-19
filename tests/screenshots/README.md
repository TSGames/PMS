# Screenshots des Admin-Backends

Aufgenommen mit `npm run screenshots` (siehe [tests/README.md](../README.md)).
Sie zeigen den aktuellen Stand der Oberfläche. Der Stand vor dem Refactoring
des Admin-Backends liegt im Commit, der das Testsystem eingeführt hat.

| Variante | Auflösung | Farbschema |
| --- | --- | --- |
| `desktop` | 1440x900 | hell |
| `desktop-dark` | 1440x900 | dunkel |
| `mobile` | 390x844 | hell |

## Anmeldung

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Login-Maske | `admin` | [PNG](desktop/login.png) | [PNG](desktop-dark/login.png) | [PNG](mobile/login.png) |
| Login mit falschem Passwort | `admin` | [PNG](desktop/login-falsches-passwort.png) | [PNG](desktop-dark/login-falsches-passwort.png) | – |
| Login ohne Backend-Berechtigung | `admin` | [PNG](desktop/login-zu-wenig-rechte.png) | [PNG](desktop-dark/login-zu-wenig-rechte.png) | – |
| Abmeldung | `admin/abmelden` | [PNG](desktop/logout.png) | [PNG](desktop-dark/logout.png) | – |

## Start

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Admin-Startseite | `admin` | [PNG](desktop/home.png) | [PNG](desktop-dark/home.png) | [PNG](mobile/home.png) |

## Konfiguration

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Website-Konfigurator | `admin/einstellungen` | [PNG](desktop/config.png) | [PNG](desktop-dark/config.png) | [PNG](mobile/config.png) |
| Konfigurator ohne Super-Admin-Rechte | `admin/einstellungen` | [PNG](desktop/config-ohne-rechte.png) | [PNG](desktop-dark/config-ohne-rechte.png) | – |

## Menü

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Menüverwaltung | `admin/menue` | [PNG](desktop/menu-liste.png) | [PNG](desktop-dark/menu-liste.png) | [PNG](mobile/menu-liste.png) |
| Menüeintrag erstellen | `admin/menue?new=yes` | [PNG](desktop/menu-neu.png) | [PNG](desktop-dark/menu-neu.png) | – |
| Menüeintrag bearbeiten | `admin/menue?edit=2` | [PNG](desktop/menu-bearbeiten.png) | [PNG](desktop-dark/menu-bearbeiten.png) | – |
| Menüeintrag löschen (Bestätigung) | `admin/menue?delete=9` | [PNG](desktop/menu-loeschen.png) | [PNG](desktop-dark/menu-loeschen.png) | – |

## Benutzer

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Benutzerverwaltung | `admin/benutzer` | [PNG](desktop/user-liste.png) | [PNG](desktop-dark/user-liste.png) | [PNG](mobile/user-liste.png) |
| Benutzer erstellen | `admin/benutzer?new=yes` | [PNG](desktop/user-neu.png) | [PNG](desktop-dark/user-neu.png) | – |
| Benutzer bearbeiten | `admin/benutzer?edit=2` | [PNG](desktop/user-bearbeiten.png) | [PNG](desktop-dark/user-bearbeiten.png) | – |
| Benutzer löschen (Bestätigung) | `admin/benutzer?delete=5` | [PNG](desktop/user-loeschen.png) | [PNG](desktop-dark/user-loeschen.png) | – |

## Kategorien

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Kategorien | `admin/kategorien` | [PNG](desktop/cat-liste.png) | [PNG](desktop-dark/cat-liste.png) | [PNG](mobile/cat-liste.png) |
| Kategorie erstellen | `admin/kategorien?new=yes` | [PNG](desktop/cat-neu.png) | [PNG](desktop-dark/cat-neu.png) | – |
| Kategorie bearbeiten | `admin/kategorien?edit=1` | [PNG](desktop/cat-bearbeiten.png) | [PNG](desktop-dark/cat-bearbeiten.png) | – |
| Kategorie löschen (Bestätigung) | `admin/kategorien?delete=4` | [PNG](desktop/cat-loeschen.png) | [PNG](desktop-dark/cat-loeschen.png) | – |

## Unterkategorien

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Unterkategorien | `admin/unterkategorien` | [PNG](desktop/subcat-liste.png) | [PNG](desktop-dark/subcat-liste.png) | [PNG](mobile/subcat-liste.png) |
| Unterkategorie erstellen | `admin/unterkategorien?new=yes` | [PNG](desktop/subcat-neu.png) | [PNG](desktop-dark/subcat-neu.png) | – |
| Unterkategorie bearbeiten | `admin/unterkategorien?edit=1` | [PNG](desktop/subcat-bearbeiten.png) | [PNG](desktop-dark/subcat-bearbeiten.png) | – |
| Unterkategorie löschen (Bestätigung) | `admin/unterkategorien?delete=6` | [PNG](desktop/subcat-loeschen.png) | [PNG](desktop-dark/subcat-loeschen.png) | – |

## Inhalte

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Inhaltsübersicht | `admin/inhalte` | [PNG](desktop/item-liste.png) | [PNG](desktop-dark/item-liste.png) | [PNG](mobile/item-liste.png) |
| Inhalt hinzufügen | `admin/inhalte?new=yes&editor=0` | [PNG](desktop/item-neu-editor.png) | [PNG](desktop-dark/item-neu-editor.png) | – |
| Inhalt hinzufügen (Spezialseite) | `admin/inhalte?new=yes&editor=0` | [PNG](desktop/item-neu-spezialseite.png) | [PNG](desktop-dark/item-neu-spezialseite.png) | – |
| Inhalt bearbeiten (ohne TinyMCE) | `admin/inhalte?edit=2&editor=0` | [PNG](desktop/item-bearbeiten-editor.png) | [PNG](desktop-dark/item-bearbeiten-editor.png) | – |
| Inhalt bearbeiten (TinyMCE-Editor) | `admin/inhalte?edit=2&editor=1` | [PNG](desktop/item-bearbeiten-tinymce.png) | [PNG](desktop-dark/item-bearbeiten-tinymce.png) | – |
| Inhalt löschen (Bestätigung) | `admin/inhalte?delete=9` | [PNG](desktop/item-loeschen.png) | [PNG](desktop-dark/item-loeschen.png) | – |
| Gelöschten Inhalt wiederherstellen | `admin/inhalte/wiederherstellen` | [PNG](desktop/item-wiederherstellen.png) | [PNG](desktop-dark/item-wiederherstellen.png) | – |
| Inhalt aus Version wiederherstellen | `admin/inhalte/versionen?item=2` | [PNG](desktop/item-versionen.png) | [PNG](desktop-dark/item-versionen.png) | – |

## Variablen

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Variablen/Regeln | `admin/variablen` | [PNG](desktop/var-liste.png) | [PNG](desktop-dark/var-liste.png) | [PNG](mobile/var-liste.png) |
| Regel erstellen | `admin/variablen?new=yes` | [PNG](desktop/var-neu.png) | [PNG](desktop-dark/var-neu.png) | – |
| Regel bearbeiten | `admin/variablen?edit=1` | [PNG](desktop/var-bearbeiten.png) | [PNG](desktop-dark/var-bearbeiten.png) | – |

## Umfragen

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Umfragen | `admin/umfragen` | [PNG](desktop/poll-liste.png) | [PNG](desktop-dark/poll-liste.png) | [PNG](mobile/poll-liste.png) |
| Umfrage erstellen | `admin/umfragen?new=yes` | [PNG](desktop/poll-neu.png) | [PNG](desktop-dark/poll-neu.png) | – |
| Umfrage bearbeiten | `admin/umfragen?edit=1` | [PNG](desktop/poll-bearbeiten.png) | [PNG](desktop-dark/poll-bearbeiten.png) | – |

## Bans

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Sperrungen | `admin/sperrungen` | [PNG](desktop/bans-liste.png) | [PNG](desktop-dark/bans-liste.png) | [PNG](mobile/bans-liste.png) |
| Ban erstellen | `admin/sperrungen?new=yes` | [PNG](desktop/bans-neu.png) | [PNG](desktop-dark/bans-neu.png) | – |
| Ban bearbeiten | `admin/sperrungen?edit=1` | [PNG](desktop/bans-bearbeiten.png) | [PNG](desktop-dark/bans-bearbeiten.png) | – |

## Monitoring

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Ereignisse | `admin/ereignisse` | [PNG](desktop/events.png) | [PNG](desktop-dark/events.png) | [PNG](mobile/events.png) |
| Backup-Manager | `admin/sicherungen` | [PNG](desktop/backup.png) | [PNG](desktop-dark/backup.png) | – |
| Website-Status | `admin/status` | [PNG](desktop/activity.png) | [PNG](desktop-dark/activity.png) | [PNG](mobile/activity.png) |

## Module

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Modul: Update | `admin/modul/update` | [PNG](desktop/modul-update.png) | [PNG](desktop-dark/modul-update.png) | – |

## Frontend

Das Frontend (`index.php`) ist nicht Teil des Refactorings. Die Aufnahmen
halten seinen Stand fest, damit ein späterer Umbau eine Vergleichsgrundlage
hat.

| Seite | Adresse | Desktop | Mobil |
| --- | --- | --- | --- |
| Startseite | `index.php` | [PNG](frontend/desktop/start.png) | [PNG](frontend/mobile/start.png) |
| Kategorie mit Unterkategorien | `index.php?cat=1` | [PNG](frontend/desktop/kategorie.png) | [PNG](frontend/mobile/kategorie.png) |
| Unterkategorie mit Inhalten | `index.php?subcat=1` | [PNG](frontend/desktop/unterkategorie.png) | [PNG](frontend/mobile/unterkategorie.png) |
| Inhalt mit Kommentaren und Bewertung | `index.php?item=2` | [PNG](frontend/desktop/inhalt.png) | [PNG](frontend/mobile/inhalt.png) |
| Download-Inhalt | `index.php?item=5` | [PNG](frontend/desktop/download.png) | [PNG](frontend/mobile/download.png) |
| Gästebuch | `index.php?action=guestbook` | [PNG](frontend/desktop/gaestebuch.png) | [PNG](frontend/mobile/gaestebuch.png) |
| Suchergebnis | `index.php?action=search&query=Sommer` | [PNG](frontend/desktop/suche.png) | [PNG](frontend/mobile/suche.png) |
| Suche mit zu kurzem Begriff | `index.php?action=search&query=ab` | [PNG](frontend/desktop/suche-zu-kurz.png) | [PNG](frontend/mobile/suche-zu-kurz.png) |
| Registrierung | `action/register.html` | [PNG](frontend/desktop/registrieren.png) | [PNG](frontend/mobile/registrieren.png) |
| Passwort zurücksetzen | `action/password_recover.html` | [PNG](frontend/desktop/passwort-vergessen.png) | [PNG](frontend/mobile/passwort-vergessen.png) |
| Seite nicht verfügbar | `index.php?item=9999` | [PNG](frontend/desktop/fehlerseite.png) | [PNG](frontend/mobile/fehlerseite.png) |

---

Insgesamt 123 Screenshots zu 55 Bildschirmen.
