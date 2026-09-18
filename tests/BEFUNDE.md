# Befunde aus dem Aufbau des Testsystems

Beim Anlegen des Mock-Systems und der Tests (Stand vor dem Refactoring des
Admin-Backends) sind die folgenden Defekte aufgefallen. Jeder Eintrag ist in
`tests/e2e/specs/known-defects.spec.js` als erwarteter Fehlschlag
(`test.fail()`) hinterlegt: Wird der Defekt behoben, meldet Playwright
"Expected to fail, but passed" – dann wird `test.fail()` entfernt und der
Eintrag hier abgehakt.

## Funktionale Defekte

### B1 – Benutzer anlegen bricht mit Fatal Error ab
`admin_actions_admin.php:140` rechnet `$_POST['id'] * 1`. Das Formular
"Benutzer erstellen" sendet ein leeres `id`-Feld; unter PHP 8 wirft
`"" * 1` einen `TypeError`. Ergebnis: weiße Seite, kein Benutzer angelegt.
Betrifft das produktive Image (php:8.4-apache).

### B2 – Ban ohne Dauer bricht mit Fatal Error ab
Gleiches Muster in `admin_actions_admin.php:25`:
`time() + str_replace(",", ".", $_POST['time']) * 60 * 60 * 24`.
Bleibt das Feld "Dauer" leer (Standardfall für eine dauerhafte Sperre),
wirft PHP 8 einen `TypeError`.

### B3 – Menüeinträge lassen sich nicht speichern
`process_menu_post_handlers()` speichert nur, wenn die globale Variable
`$post` den Wert 2 hat. Gesetzt wird `$post = 2` ausschließlich in den
Inhalts-Handlern (`admin_actions_content.php`) und beim Bild-Upload.
Beim Absenden des Menü-Formulars bleibt `$post` leer, der Speicherzweig
wird nie erreicht – weder Anlegen noch Ändern eines Menüeintrags wirkt.

### B4 – Löschen ohne Rückfrage per GET
`admin.php?action=user&delete=<id>` und `admin.php?action=item&delete=<id>`
löschen den Datensatz sofort beim Aufruf des Links. Kategorien,
Unterkategorien und Menüeinträge fragen dagegen nach. Neben der
inkonsistenten Bedienung ist das eine CSRF-Lücke: Ein eingebettetes
`<img src="…admin.php?action=item&delete=5">` genügt, um Inhalte eines
angemeldeten Administrators zu löschen. Es gibt keinen CSRF-Token.

### B5 – JavaScript-Fehler auf der Login-Maske
`admin.php` gibt den Skriptblock für die Seitenleiste unabhängig vom
Login-Status aus. Ohne Seitenleiste läuft er auf
`sidebarToggle.addEventListener` und bricht ab.

### B6 – Variablen-Seite lädt den Editor von einem CDN
Die Seite "Variablen" bindet den Monaco-Editor von
`cdn.jsdelivr.net` ein. Ohne Internetzugang (interne Installation,
Testumgebung) bleibt das Eingabefeld ohne Editor, in der Konsole steht
`require is not defined`. Zusätzlich ein Datenschutz-Aspekt, weil jede
Bearbeitung eine Anfrage an einen Dritt-Server auslöst.

### B8 – SQL-Syntaxfehler im Menü-Formular
`handle_admin_menu()` baut `make_sql("subcat", "cat = " . $cat, …)` auch
dann, wenn `$cat` leer ist. Die Abfrage lautet dann
`… WHERE cat =  ORDER BY sort,name;` und wird von SQLite abgelehnt
(`near "ORDER": syntax error` im Log). Gleiches Muster für die Item-Abfrage
(`near "AND"`). Die Seite rendert, die Auswahllisten bleiben aber leer.

## Strukturelle Beobachtungen (Grundlage für das Refactoring)

1. **Globale Variablen als Steuerfluss.** `$post`, `$action`, `$edit`,
   `$new`, `$ok`, `$error` werden quer über `admin.php` und fünf
   Handler-Dateien gesetzt und gelesen (siehe B3). Reihenfolge und
   Nebenwirkungen sind nicht nachvollziehbar.
2. **Keine Trennung von Verarbeitung und Ausgabe.** Die Handler geben HTML
   direkt per `echo` aus und führen dabei Datenbankänderungen durch.
3. **SQL wird durchgängig per String-Verkettung gebaut.** Werte aus `$_POST`
   landen teilweise ungeprüft im Statement (`bans`, `menu`, `config`).
   Es gibt keine Prepared Statements.
4. **Kein CSRF-Schutz**, Sitzungsprüfung nur über `REMOTE_ADDR`.
5. **Passwörter als ungesalzenes MD5** (`do_login`, `make_user`).
6. **Zweistufiges Inhaltsformular** (`item_step1`/`item_step2`) mit
   Zustand in `$_SESSION['tinymce']`.
7. **Gemischte Zeichenkodierung**: Die Quelldateien enthalten sowohl
   UTF-8- als auch Latin-1-Umlaute, `admin.php` deklariert
   `charset=iso-8859-1`, ausgeliefert wird UTF-8.
8. **Tabellen laufen horizontal aus dem Bild** (z.B. Inhaltsliste: Spalte
   "Löschen" ist bei 1440px nicht mehr sichtbar).
9. **Tippfehler in der Oberfläche**: "Gelöschen Inhalt Wiederherstellen".
