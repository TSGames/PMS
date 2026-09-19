# Befunde aus dem Refactoring des Admin-Backends

Beim Aufbau des Mock-Systems und der Tests sind die folgenden Defekte
aufgefallen. Alle sind inzwischen behoben; die Tests in
`tests/e2e/specs/known-defects.spec.js`, `security.spec.js` und
`specs/frontend/` halten fest, dass sie nicht zurückkehren.

B13 bis B19 sind später dazugekommen - B13 und B14 beim Umbau des
Backends, B15 bis B19 durch die neuen Frontend-Tests. Die beiden letzten
betreffen `index.php` und seine Hilfsdateien, also Code außerhalb des
Refactorings; sie waren Abbrüche und deshalb nicht aufschiebbar.

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

### B13 – Die Zuschneide-Schnittstelle lieferte HTML statt JSON
Mit den sprechenden Adressen aus Phase 1 landete `admin.php?action=crop_image_ajax`
in der Zuordnung der Bereiche, fand dort keinen Controller und bekam die
Startseite zurück. Der Zuschneide-Dialog scheiterte an der Antwort, ohne
eine Meldung zu zeigen. `Kernel::ENDPOINTS` beantwortet die Schnittstellen
jetzt auch über die frühere Adresse; `specs/security.spec.js` prüft, dass
`Content-Type: application/json` zurückkommt.

### B14 – `browser()` gab die rohe Kennung zurück
Die Funktion in `functions_utility.php` begann mit `return $a;`, gefolgt von
totem Code für Firefox. Der Website-Status zeigte deshalb mehrere hundert
Zeilen voller `Mozilla/5.0 (…) AppleWebKit/537.36 …`. `Support\UserAgent`
macht daraus "Chrome 120 auf Windows 10/11" und erkennt Suchmaschinen.

### B15 – Das Gästebuch brach mit einem fatalen Fehler ab
`make_mail()` in `functions_mail.php` rechnete mit `date(Y)` statt
`date("Y")`. Unter PHP 8 ist ein Bezeichner ohne Anführungszeichen kein
String mehr, sondern eine unbekannte Konstante - und damit ein Abbruch.
Betroffen war jede Seite mit einer verschlüsselt ausgegebenen Mailadresse,
insbesondere das Gästebuch. Gefunden von den neuen Frontend-Tests.

### B16 – Das Bild des Prüf-Codes wurde nicht ausgeliefert
`image.php` rief `imagejpeg($img, "", 90)` auf. Ein leerer Dateiname ist
seit PHP 8 ein Fehler, die Anfrage brach ab und der Prüf-Code des
Gästebuchs blieb leer. Zusätzlich hängte ein `echo` eine 1 an die
Bilddaten, und der Content-Type fehlte.

### B17 – Die Seite für gesperrte Adressen brach mit einem fatalen Fehler ab
Stand die anfragende Adresse auf der Sperrliste, antwortete `index.php` mit
`Too few arguments to function dynamic_string()` statt mit der dafür
vorgesehenen Spezialseite. Die Schleife, die dort die Platzhalter ersetzt,
ist eine verdorbene Kopie der beiden intakten Stellen: Sie las `$a->replacer`
nie, arbeitete stattdessen mit einer noch undefinierten Variablen und rief
`dynamic_string()` mit zwei statt drei Argumenten. Der Weg war von keinem
Test abgedeckt, weil die Sperrliste im Mock-System keine der Testadressen
enthält. Er hat jetzt einen eigenen Test
(`tests/e2e/specs/frontend/sperrung.spec.js`).

### B18 – Sitemap und Unterkategorie-Listen brachen ab
Vier Stellen riefen `mysqli_num_rows()` mit einem `SQLite3Result` auf. Die
Sitemap (`/action/sitemap.html`) und jede Unterkategorie, deren Inhalte auf
eine Seite passen, endeten deshalb mit einem `TypeError`. Betroffen waren
außerdem das ausklappbare Menü (`menu_mode=1`) und das Referenzsystem.
`pms_db_class::fetchAllObject()` liefert die Zeilen als Feld - `count()`
ersetzt den Aufruf, an einer Stelle stand das Feld sogar zwei Zeilen darüber
schon bereit.

### B19 – Die Website starb, sobald die mysqli-Erweiterung geladen war
`functions_global.php` definierte auf oberster Ebene eine eigene Funktion
`mysqli_fetch_object()` (und ein totes `mysqli_field_name()`). Ist die
mysqli-Erweiterung geladen, bricht PHP das Laden mit
`Cannot redeclare function mysqli_fetch_object()` ab - die gesamte Website
antwortet dann mit einem fatalen Fehler. Im Mock-System fiel das nie auf,
weil der Entwicklungsserver ohne mysqli startet. Beide Nachbauten sind
entfernt; die einzige Aufrufstelle benutzt jetzt `fetchObject()` direkt.

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
