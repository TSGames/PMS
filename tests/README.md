# Testsystem für das PMS-Admin-Backend

Dieses Verzeichnis enthält ein vollständig lokales Mock-System der Anwendung
sowie End-to-End-Tests und Screenshots aller Bildschirme des Admin-Backends.

## Überblick

```
tests/
├── mock/            Mock-System (Datenbank, Template, PHP-Server)
│   ├── setup.php    Richtet Verzeichnisse, PHP-Konfiguration und Datenbank ein
│   ├── seed.sql     Mock-Daten mit festen Zeitstempeln
│   ├── paths.php    Gemeinsame Pfade/Ports
│   └── server.sh    Start/Stop des PHP-Entwicklungsservers
├── e2e/             Playwright-Tests und Screenshot-Aufnahme
│   ├── lib/         Screen-Katalog, Login-Helfer, Mock-Benutzer
│   ├── specs/       Testfälle
│   ├── screenshots/ Screenshot-Aufnahme
│   └── bin/         Hilfsskripte
├── screenshots/     Aufgenommene Screenshots aller Bildschirme
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

## Tests ausführen

```bash
cd tests/e2e
npm test                        # alle Testfälle
npx playwright test --project=tests specs/items.spec.js
npm run report                  # HTML-Bericht der letzten Ausführung
```

Jede Spec setzt die Datenbank zu Beginn auf den Ausgangszustand zurück
(`resetDatabase()` ruft `tests/mock/setup.php` auf), Tests sind daher
voneinander unabhängig.

## Screenshots aufnehmen

```bash
cd tests/e2e
npm run screenshots
```

Legt für jeden Bildschirm aus `lib/screens.js` Screenshots unter
`tests/screenshots/<variante>/<id>.png` ab und erzeugt eine Übersicht in
`tests/screenshots/README.md`. Der Aufbau des Backends selbst ist in
[src/backend/README.md](../src/backend/README.md) beschrieben.

## Statische Analyse

Die Anwendung wird zusätzlich mit Psalm geprüft (wie in der CI):

```bash
composer install
vendor/bin/psalm
```

## Neue Bildschirme aufnehmen

`tests/e2e/lib/screens.js` ist die zentrale Liste. Ein neuer Eintrag wird
automatisch vom Smoke-Test (`specs/screens.spec.js`) und von der
Screenshot-Aufnahme berücksichtigt.
