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
| Login-Maske | `admin.php` | [PNG](desktop/login.png) | [PNG](desktop-dark/login.png) | [PNG](mobile/login.png) |
| Login mit falschem Passwort | `admin.php` | [PNG](desktop/login-falsches-passwort.png) | [PNG](desktop-dark/login-falsches-passwort.png) | – |
| Login ohne Backend-Berechtigung | `admin.php` | [PNG](desktop/login-zu-wenig-rechte.png) | [PNG](desktop-dark/login-zu-wenig-rechte.png) | – |
| Abmeldung | `admin.php?action=logout` | [PNG](desktop/logout.png) | [PNG](desktop-dark/logout.png) | – |

## Start

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Admin-Startseite | `admin.php?action=home` | [PNG](desktop/home.png) | [PNG](desktop-dark/home.png) | [PNG](mobile/home.png) |

## Konfiguration

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Website-Konfigurator | `admin.php?action=config` | [PNG](desktop/config.png) | [PNG](desktop-dark/config.png) | [PNG](mobile/config.png) |
| Konfigurator ohne Super-Admin-Rechte | `admin.php?action=config` | [PNG](desktop/config-ohne-rechte.png) | [PNG](desktop-dark/config-ohne-rechte.png) | – |

## Menü

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Menüverwaltung | `admin.php?action=menu` | [PNG](desktop/menu-liste.png) | [PNG](desktop-dark/menu-liste.png) | [PNG](mobile/menu-liste.png) |
| Menüeintrag erstellen | `admin.php?action=menu&new=yes` | [PNG](desktop/menu-neu.png) | [PNG](desktop-dark/menu-neu.png) | – |
| Menüeintrag bearbeiten | `admin.php?action=menu&edit=2` | [PNG](desktop/menu-bearbeiten.png) | [PNG](desktop-dark/menu-bearbeiten.png) | – |
| Menüeintrag löschen (Bestätigung) | `admin.php?action=menu&delete=9` | [PNG](desktop/menu-loeschen.png) | [PNG](desktop-dark/menu-loeschen.png) | – |

## Benutzer

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Benutzerverwaltung | `admin.php?action=user` | [PNG](desktop/user-liste.png) | [PNG](desktop-dark/user-liste.png) | [PNG](mobile/user-liste.png) |
| Benutzer erstellen | `admin.php?action=user&new=yes` | [PNG](desktop/user-neu.png) | [PNG](desktop-dark/user-neu.png) | – |
| Benutzer bearbeiten | `admin.php?action=user&edit=2` | [PNG](desktop/user-bearbeiten.png) | [PNG](desktop-dark/user-bearbeiten.png) | – |
| Benutzer löschen (Bestätigung) | `admin.php?action=user&delete=5` | [PNG](desktop/user-loeschen.png) | [PNG](desktop-dark/user-loeschen.png) | – |

## Kategorien

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Kategorien | `admin.php?action=cat` | [PNG](desktop/cat-liste.png) | [PNG](desktop-dark/cat-liste.png) | [PNG](mobile/cat-liste.png) |
| Kategorie erstellen | `admin.php?action=cat&new=yes` | [PNG](desktop/cat-neu.png) | [PNG](desktop-dark/cat-neu.png) | – |
| Kategorie bearbeiten | `admin.php?action=cat&edit=1` | [PNG](desktop/cat-bearbeiten.png) | [PNG](desktop-dark/cat-bearbeiten.png) | – |
| Kategorie löschen (Bestätigung) | `admin.php?action=cat&delete=4` | [PNG](desktop/cat-loeschen.png) | [PNG](desktop-dark/cat-loeschen.png) | – |

## Unterkategorien

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Unterkategorien | `admin.php?action=subcat` | [PNG](desktop/subcat-liste.png) | [PNG](desktop-dark/subcat-liste.png) | [PNG](mobile/subcat-liste.png) |
| Unterkategorie erstellen | `admin.php?action=subcat&new=yes` | [PNG](desktop/subcat-neu.png) | [PNG](desktop-dark/subcat-neu.png) | – |
| Unterkategorie bearbeiten | `admin.php?action=subcat&edit=1` | [PNG](desktop/subcat-bearbeiten.png) | [PNG](desktop-dark/subcat-bearbeiten.png) | – |
| Unterkategorie löschen (Bestätigung) | `admin.php?action=subcat&delete=6` | [PNG](desktop/subcat-loeschen.png) | [PNG](desktop-dark/subcat-loeschen.png) | – |

## Inhalte

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Inhaltsübersicht | `admin.php?action=item` | [PNG](desktop/item-liste.png) | [PNG](desktop-dark/item-liste.png) | [PNG](mobile/item-liste.png) |
| Inhalt hinzufügen (Vorauswahl) | `admin.php?action=item&new=yes` | [PNG](desktop/item-neu-vorauswahl.png) | [PNG](desktop-dark/item-neu-vorauswahl.png) | – |
| Inhalt hinzufügen (Editor) | `admin.php?action=item&new=yes` | [PNG](desktop/item-neu-editor.png) | [PNG](desktop-dark/item-neu-editor.png) | – |
| Inhalt bearbeiten (Vorauswahl) | `admin.php?action=item&edit=2` | [PNG](desktop/item-bearbeiten-vorauswahl.png) | [PNG](desktop-dark/item-bearbeiten-vorauswahl.png) | – |
| Inhalt bearbeiten (Editor ohne TinyMCE) | `admin.php?action=item&edit=2` | [PNG](desktop/item-bearbeiten-editor.png) | [PNG](desktop-dark/item-bearbeiten-editor.png) | – |
| Inhalt bearbeiten (TinyMCE-Editor) | `admin.php?action=item&edit=2` | [PNG](desktop/item-bearbeiten-tinymce.png) | [PNG](desktop-dark/item-bearbeiten-tinymce.png) | – |
| Inhalt löschen (Bestätigung) | `admin.php?action=item&delete=9` | [PNG](desktop/item-loeschen.png) | [PNG](desktop-dark/item-loeschen.png) | – |
| Gelöschten Inhalt wiederherstellen | `admin.php?action=item_restore` | [PNG](desktop/item-wiederherstellen.png) | [PNG](desktop-dark/item-wiederherstellen.png) | – |
| Inhalt aus Version wiederherstellen | `admin.php?action=item_recover&item=2` | [PNG](desktop/item-versionen.png) | [PNG](desktop-dark/item-versionen.png) | – |

## Variablen

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Variablen/Regeln | `admin.php?action=var` | [PNG](desktop/var-liste.png) | [PNG](desktop-dark/var-liste.png) | [PNG](mobile/var-liste.png) |
| Regel erstellen | `admin.php?action=var&new=yes` | [PNG](desktop/var-neu.png) | [PNG](desktop-dark/var-neu.png) | – |
| Regel bearbeiten | `admin.php?action=var&edit=1` | [PNG](desktop/var-bearbeiten.png) | [PNG](desktop-dark/var-bearbeiten.png) | – |

## Umfragen

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Umfragen | `admin.php?action=poll` | [PNG](desktop/poll-liste.png) | [PNG](desktop-dark/poll-liste.png) | [PNG](mobile/poll-liste.png) |
| Umfrage erstellen | `admin.php?action=poll&new=yes` | [PNG](desktop/poll-neu.png) | [PNG](desktop-dark/poll-neu.png) | – |
| Umfrage bearbeiten | `admin.php?action=poll&edit=1` | [PNG](desktop/poll-bearbeiten.png) | [PNG](desktop-dark/poll-bearbeiten.png) | – |

## Bans

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Sperrungen | `admin.php?action=bans` | [PNG](desktop/bans-liste.png) | [PNG](desktop-dark/bans-liste.png) | [PNG](mobile/bans-liste.png) |
| Ban erstellen | `admin.php?action=bans&new=yes` | [PNG](desktop/bans-neu.png) | [PNG](desktop-dark/bans-neu.png) | – |
| Ban bearbeiten | `admin.php?action=bans&edit=1` | [PNG](desktop/bans-bearbeiten.png) | [PNG](desktop-dark/bans-bearbeiten.png) | – |

## Monitoring

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Ereignisse | `admin.php?action=events` | [PNG](desktop/events.png) | [PNG](desktop-dark/events.png) | [PNG](mobile/events.png) |
| Backup-Manager | `admin.php?action=backup` | [PNG](desktop/backup.png) | [PNG](desktop-dark/backup.png) | – |
| Website-Status | `admin.php?action=activity` | [PNG](desktop/activity.png) | [PNG](desktop-dark/activity.png) | [PNG](mobile/activity.png) |

## Module

| Seite | Adresse | Desktop | Dunkel | Mobil |
| --- | --- | --- | --- | --- |
| Modul: Update | `admin.php?modul=update` | [PNG](desktop/modul-update.png) | [PNG](desktop-dark/modul-update.png) | – |

---

Insgesamt 103 Screenshots zu 45 Bildschirmen.
