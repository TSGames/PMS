# Befunde aus dem Refactoring des Admin-Backends

Beim Aufbau des Mock-Systems und der Tests sind die folgenden Defekte
aufgefallen. Alle sind inzwischen behoben; die Tests in
`tests/e2e/specs/known-defects.spec.js` und `security.spec.js` halten fest,
dass sie nicht zurückkehren.

## Behobene Defekte

### B9 – Ohne Anmeldung wurden Eingaben verarbeitet (kritisch)
`admin.php` rief die POST-Handler auf, bevor irgendeine Rechteprüfung
stattfand. Eine einzige Anfrage ohne jede Anmeldung genügte, um
Sperrungen, Kategorien, Unterkategorien, Inhalte und sogar Benutzerkonten
anzulegen oder zu ändern:

```
curl -d "id=0&ip=203.0.113.99&reason=x&time=5&bans=Speichern" .../admin.php
```

Der Ablauf prüft die Anmeldung jetzt vor jeder Verarbeitung; ohne gültige
Sitzung erscheint nur noch die Anmeldemaske.
Tests: `specs/security.spec.js`.

### B1 – Benutzer anlegen brach mit Fatal Error ab
`admin_actions_admin.php` rechnete `$_POST['id'] * 1`. Das Formular
"Benutzer erstellen" sendete ein leeres Feld, und unter PHP 8 wirft
`"" * 1` einen `TypeError`. Ergebnis: weiße Seite, kein Benutzer angelegt.
Eingaben laufen jetzt über `Request::int()`.

### B2 – Ban ohne Dauer brach mit Fatal Error ab
Gleiches Muster bei `time() + str_replace(...) * 60 * 60 * 24`. Eine
dauerhafte Sperre (leeres Feld) war damit nicht anlegbar.

### B3 – Menüeinträge ließen sich nicht speichern
Der Speicherzweig lief nur, wenn die globale Variable `$post` den Wert 2
hatte. Gesetzt wurde sie ausschließlich von den Handlern der
Inhaltsverwaltung – beim Menü-Formular also nie. Weder Anlegen noch Ändern
eines Menüeintrags hatte eine Wirkung.

### B4 – Löschen ohne Rückfrage per GET
`admin.php?action=user&delete=…` und `…action=item&delete=…` löschten den
Datensatz sofort beim Aufruf des Links – ohne Rückfrage und ohne Token.
Ein eingebettetes `<img src="…admin.php?action=item&delete=5">` genügte,
um Inhalte eines angemeldeten Administrators zu löschen. Alle Bereiche
fragen jetzt nach und prüfen ein Token.

### B5 – JavaScript-Fehler auf der Anmeldemaske
Der Skriptblock der Seitenleiste wurde unabhängig vom Anmeldestatus
ausgegeben und brach ohne Seitenleiste ab. Er liegt jetzt in
`js/admin-sidebar.js` und prüft, ob die Elemente vorhanden sind.

### B8 – SQL-Syntaxfehler im Menü-Formular
`make_sql("subcat", "cat = " . $cat, …)` erzeugte ohne gewählte Kategorie
`… WHERE cat =  ORDER BY sort,name;`. SQLite lehnte die Abfrage ab, die
Auswahllisten blieben leer.

### B10 – Konfigurator speicherte die E-Mail-Benachrichtigungen nie
Die Zuordnungstabelle `$confirmation_dialogs` wurde erst nach den
POST-Handlern aufgebaut. Beim Speichern war sie leer, die Schleife lief
ins Leere.

### B11 – Ereignisseite brach ohne Ereignisse ab
`usort()` lief auf einer nie befüllten Variablen, sobald im gewählten
Zeitraum kein Ereignis lag – Fatal Error statt leerer Liste.

### B12 – Zerstörte Umlaute im Quelltext
In `functions.php`, `functions_ui.php` und `functions_utility.php` standen
Ersatzzeichen statt Umlauten ("Gï¿½stebuch"). Betroffen war auch die
Ersetzungstabelle in `link_name()`, die Umlaute in Dateinamen ersetzen
soll und dafür ebenfalls nur Ersatzzeichen enthielt.

### Weitere Kleinigkeiten
* Die Benutzerliste erzeugte eine mehrdeutige Abfrage
  (`ambiguous column name: id`).
* Die Bildvorschau der Bildauswahl hing an einer Mausverfolgung, die es
  seit einem früheren Umbau nicht mehr gab.
* Tippfehler in der Oberfläche ("eingeloogt", "Gelöschen Inhalt").

## Offene Punkte

### B6 – Variablen-Seite lädt den Editor von einem CDN
Die Seite "Variablen" bindet den Monaco-Editor von `cdn.jsdelivr.net` ein.
Ohne Internetzugang bleibt das Eingabefeld ohne Editor; zusätzlich geht
bei jeder Bearbeitung eine Anfrage an einen Dritt-Server. Das Skript liegt
in `functions_editor.php` und gehört nicht zum Admin-Backend im engeren
Sinn; es ist im Test als bekannter Fehler hinterlegt
(`KNOWN_JS_ERRORS` in `tests/e2e/lib/admin.js`).

### Passwörter als ungesalzenes MD5
`do_login()` und `make_user()` speichern Passwörter als MD5-Hash ohne
Salt. Eine Umstellung auf `password_hash()` betrifft auch die
Benutzeranmeldung im Frontend und die Cookie-Anmeldung und wurde deshalb
nicht in diesem Refactoring erledigt.

### `TRUNCATE TABLE` in counter.php
SQLite kennt kein `TRUNCATE`; die Tabelle `visitors` wird beim Tageswechsel
deshalb nie geleert (Meldung `near "TRUNCATE": syntax error` im Log).
Betrifft den Besucherzähler, nicht das Backend.
