# Testsystem für das PMS-Admin-Backend

Dieses Verzeichnis enthält ein vollständig lokales Mock-System der Anwendung,
Unit-Tests der Backend-Bausteine, End-to-End-Tests von Backend und Frontend
sowie Screenshots aller Bildschirme.

## Überblick

```
tests/
├── mock/            Mock-System (Datenbank, Template, PHP-Server)
│   ├── setup.php    Richtet Verzeichnisse, PHP-Konfiguration und Datenbank ein
│   ├── seed.sql     Mock-Daten mit festen Zeitstempeln
│   ├── paths.php    Gemeinsame Pfade/Ports
│   └── server.sh    Start/Stop des PHP-Entwicklungsservers
├── unit/            PHPUnit-Tests der Bausteine unter src/backend/
├── e2e/             Playwright-Tests und Screenshot-Aufnahme
│   ├── lib/         Screen-Kataloge, Login-Helfer, Mock-Benutzer
│   ├── specs/       Testfälle des Backends
│   │   └── frontend/  Testfälle des Frontends (index.php)
│   ├── screenshots/ Screenshot-Aufnahme
│   └── bin/         Hilfsskripte
├── screenshots/     Aufgenommene Screenshots (Backend und Frontend)
└── .runtime/        Laufzeitdaten, nicht versioniert
```

## Voraussetzungen

* PHP 8.4 mit `sqlite3` und `gd`
* Node.js 20+
* Schreibrechte für `/var/db` und `/var/template` (beide Pfade sind im
  Anwendungscode fest verdrahtet; ggf. `sudo` verwenden)

## Einrichtung

```bash
php tests/mock/setup.php        # Datenbank + Mock-Daten anlegen
cd tests/e2e && npm install     # Playwright installieren
```

`setup.php` legt an:

* `/var/db/default.sqlite` mit dem Schema aus `src/.db_layout.sql` und den
  Mock-Daten aus `tests/mock/seed.sql`
* `/var/template` mit dem Standard-Template
* `src/template_files` (Symlink) und `src/images/uploads`
* `tests/.runtime/conf.d` – eine PHP-Konfiguration, deren Extensions dem
  Produktions-Image entsprechen (u.a. **ohne** `mysqli`), damit sich der
  Entwicklungsserver wie der Container verhält

## Server starten

```bash
tests/mock/server.sh start      # http://127.0.0.1:8099/admin.php
tests/mock/server.sh logs
tests/mock/server.sh stop
```

Die Tests starten den Server bei Bedarf selbst (`webServer` in
`playwright.config.js`) und verwenden einen bereits laufenden Server weiter.

## Mock-Zugänge

| Benutzer | Passwort | Typ | Backend-Zugriff |
| --- | --- | --- | --- |
| `admin` | `admin123` | Super-Administrator | ja, inkl. Konfigurator |
| `redakteur` | `admin123` | Administrator | ja, ohne Konfigurator |
| `moderator` | `admin123` | Moderator | nein |
| `gast` | `admin123` | Benutzer | nein |
| `gesperrt` | `admin123` | Benutzer (inaktiv) | nein |

Die Mock-Daten enthalten Kategorien, Unterkategorien, Inhalte aller Typen
(inkl. Spezialseiten wie Startseite, Gästebuch und Fehlerseite), Menüeinträge
aller Varianten, Variablen, Umfragen, Sperrungen, Kommentare und Besucherdaten.

## Unit-Tests

Die Bausteine unter `src/backend/` werden ohne Webserver und ohne Browser
geprüft - Eingaben, Adressen, Listen, Formularfehler, Datenbankzugriff und
die Auswertung der Browser-Kennungen:

```bash
composer install
vendor/bin/phpunit
```

`tests/unit/bootstrap.php` registriert den Autoloader und stellt eine
SQLite-Datenbank im Arbeitsspeicher bereit. Die Tests brauchen weder
`/var/db` noch ein Template.

## End-to-End-Tests

```bash
cd tests/e2e
npm test                        # alle Testfälle (Backend und Frontend)
npx playwright test --project=tests specs/items.spec.js
npx playwright test --project=tests specs/frontend/
npm run report                  # HTML-Bericht der letzten Ausführung
```

Jede Spec setzt die Datenbank zu Beginn auf den Ausgangszustand zurück
(`resetDatabase()` ruft `tests/mock/setup.php` auf), Tests sind daher
voneinander unabhängig.

Die Tests unter `specs/frontend/` prüfen `index.php`. Das Frontend ist nicht
Teil des Refactorings des Admin-Backends; die Tests halten seinen heutigen
Stand fest, damit ein späterer Umbau abgesichert ist.

`tests/mock/router.php` bildet die Rewrite-Regeln aus `src/.htaccess` nach,
damit auch die sprechenden Adressen (`/content/…`, `/action/…`, `/rss/…`)
so funktionieren wie unter Apache.

## Screenshots aufnehmen

```bash
cd tests/e2e
npm run screenshots
```

Legt Screenshots ab und erzeugt eine Übersicht in
`tests/screenshots/README.md`:

* Backend aus `lib/screens.js` unter `tests/screenshots/<variante>/<id>.png`
  (`desktop`, `desktop-dark`, `mobile`)
* Frontend aus `lib/frontend-screens.js` unter
  `tests/screenshots/frontend/<variante>/<id>.png` (`desktop`, `mobile`)

Der Aufbau des Backends selbst ist in
[src/backend/README.md](../src/backend/README.md) beschrieben.

## Statische Analyse

Die Anwendung wird zusätzlich mit Psalm geprüft (wie in der CI):

```bash
composer install
vendor/bin/psalm
```

## Alles zusammen

```bash
composer install && vendor/bin/psalm && vendor/bin/phpunit
php tests/mock/setup.php
cd tests/e2e && npm install && npm test
```

## Neue Bildschirme aufnehmen

`tests/e2e/lib/screens.js` (Backend) und `lib/frontend-screens.js`
(Frontend) sind die zentralen Listen. Ein neuer Eintrag wird automatisch vom
jeweiligen Smoke-Test (`specs/screens.spec.js`,
`specs/frontend/bildschirme.spec.js`) und von der Screenshot-Aufnahme
berücksichtigt.
