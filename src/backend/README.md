# Aufbau des Admin-Backends

Der gesamte Administrationsbereich liegt in diesem Verzeichnis. Es ist über
den Webserver nicht erreichbar (`.htaccess`) und wird ausschließlich von
`src/admin.php` geladen.

## Ablauf einer Anfrage

`admin.php` ist der einzige Einstiegspunkt und macht nur noch vier Dinge:

1. **Schnittstellen ohne Seitenausgabe** – `Http\XlsxEndpoint` und
   `Http\CropEndpoint` beantworten Anfragen des Editors mit JSON.
2. **Anmeldung** – `Support\Auth` verarbeitet Anmeldung, Abmeldung und
   Cookie-Anmeldung. Ohne gültige Sitzung wird **keine** Eingabe
   verarbeitet; es erscheint die Anmeldemaske.
3. **Verarbeitung** – `Http\Router` bestimmt den zuständigen Controller,
   der die Eingaben verarbeitet und das HTML des Inhaltsbereichs liefert.
4. **Ausgabe** – `View\Layout` setzt Kopfbereich, Navigation, Meldungen und
   Inhalt zur fertigen Seite zusammen.

## Verzeichnisse

Unter `src/backend/` liegt, was nur das Backend angeht:

| Verzeichnis | Inhalt |
| --- | --- |
| `Controller/` | Je ein Controller pro Bereich des Backends |
| `Http/` | Routen, Navigation, JSON-Schnittstellen, Update-Prüfung |
| `View/` | Grundgerüst, Templates, Symbole, Listen- und Formularbausteine |

Unter `src/lib/` liegt, was Frontend und Backend sich teilen (Namensraum
`Pms\`, ohne `Backend`):

| Verzeichnis | Inhalt |
| --- | --- |
| `Data/` | `Db` – Datenbankzugriff mit vorbereiteten Anweisungen, `EventFeed` |
| `Support/` | Eingaben, Anmeldung, Token, Meldungen, HTML-Bausteine, Listen, Feldfehler, Adressen, Browser-Kennungen |

Die Richtung ist festgelegt: `Pms\Backend\*` darf `Pms\Support` und
`Pms\Data` benutzen, nie umgekehrt. Wo eine geteilte Klasse etwas braucht,
das nur der jeweilige Bereich weiß, bekommt sie es übergeben -
`Support\Url` etwa lässt sich vom Einstiegspunkt sagen, wie eine Aktion zu
ihrem Pfad kommt, und `Auth::handleRequest()` bekommt die Aktion als
Parameter.

## Ein Controller

Jeder Bereich erbt von `Controller\Controller` und liefert HTML zurück,
statt es auszugeben. Das Muster ist überall gleich:

```php
public function handle(): string
{
    if (Request::submitted('bans')) {   // 1. Eingaben verarbeiten
        $this->save();                  //    bei Erfolg: redirect()
    }

    $delete = Request::queryInt('delete');
    if ($delete > 0) {                  // 2. Rückfragen
        return $this->confirmDelete(...);
    }

    return $this->overview();           // 3. Anzeige
}
```

Feste Regeln:

* **Eingaben** kommen ausschließlich über `Support\Request` herein und sind
  dort bereits typisiert (`int`, `string`, `checkbox`). Rohes `$_POST`
  taucht in Controllern nicht auf.
* **Datenbank** ausschließlich über `Data\Db`; Werte werden gebunden, nie
  in die Abfrage geschrieben.
* **Verändernde Anfragen** prüfen mit `$this->checkToken()` das
  CSRF-Token, das `Support\Html::formOpen()` in jedes Formular legt.
* **Löschen** erfolgt nur nach Rückfrage (`confirmDelete()`), nie über
  einen einfachen Link.
* **Nach erfolgreichem Speichern** wird weitergeleitet (`redirect()`),
  damit ein Neuladen die Aktion nicht wiederholt.
* **Ausgabe** wird über `Support\Html` erzeugt, das jeden dynamischen Wert
  maskiert.

## Einen neuen Bereich anlegen

1. Controller unter `Controller/` anlegen, von `Controller` erben.
2. Adresse in `Http\Routes::PATHS` und Zuordnung in
   `Http\Kernel::CONTROLLERS` eintragen.
3. Bei Bedarf einen Navigationspunkt in `Http\Navigation::items()` ergänzen
   (ans Ende, siehe dort) und in `Http\Navigation::GROUPS` einsortieren.
4. Testfälle unter `tests/e2e/specs/` ergänzen und den Bildschirm in
   `tests/e2e/lib/screens.js` aufnehmen, damit er in Smoke-Test und
   Screenshots erscheint.

## Eine Übersicht bauen

Alle Listen des Backends folgen demselben Muster. `Support\Listing` baut die
Abfrage aus dem, was in der Adresse steht (Suchbegriff `q`, Sortierspalte
`order` und `dir`, Seite `page`, dazu die Filter des Bereichs);
`View\Components` gibt sie aus:

```php
$list = Listing::from('cat')
    ->searchIn(['name'])
    ->sortableBy(['id' => 'id', 'name' => 'name', 'sort' => 'sort'])
    ->orderedBy('sort, name')
    ->keep('available', $available)   // Filter bleibt in allen Links erhalten
    ->where('available = :available', ['available' => $available])
    ->load();

return Components::pageHeader('Kategorien', 'Kurze Erläuterung', $primaryAction)
    . Components::toolbar($this->action(), $list, $filters, 'Kategorie suchen')
    . Components::table($columns, $rows, $this->action(), $list, $leerText)
    . Components::pagination($list, $this->action());
```

Spaltennamen stehen ausschließlich im Code. Aus der Adresse kommen nur
Schlüssel, die `sortableBy()` freigegeben hat - alles andere fällt auf die
Grundreihenfolge zurück. Werte werden gebunden, nie in das SQL eingesetzt.

Die Zahl der Einträge je Seite kommt aus der Website-Konfiguration
(`config.page_limit`). Filter senden bei Auswahl selbst ab (Alpine);
ohne JavaScript erscheint stattdessen ein Schalter "Anwenden".

`Controller::rowActions()` liefert die Aktionsspalte, `Components::chip()`
und `Components::booleanChip()` die farbigen Markierungen. `Html::table()`
bleibt für die wenigen Übersichten, die weder Suche noch Sortierung
brauchen (Sicherungen, Ereignisse, Website-Status).

## Ein Formular bauen

`View\Form` liefert die Teile, `Support\Errors` die Meldungen am Feld:

```php
$fields = Form::field('Name', Html::input('name', $value, ['id' => 'name']), [
    'name' => 'name',        // unter diesem Namen sucht Form die Fehlermeldung
    'required' => true,
    'hint' => 'Mindestens 3 Zeichen.',
])
    . Form::check(Html::checkbox('available', true), 'Eintrag verfügbar');

return Components::pageHeader('Kategorie bearbeiten')
    . Html::formOpen($this->action())
    . Html::hidden('id', $id)
    . Form::card(
        Form::section('Allgemein', $fields),
        Form::actions('cat', 'Speichern', $this->url())
    )
    . Html::formClose();
```

Geprüft wird auf dem Server, deshalb trägt jedes POST-Formular `novalidate`:
Sonst blockiert der Browser das Absenden und die Meldung am Feld käme nie
zustande.

`save()` gibt die eingegebenen Werte zurück, wenn nicht gespeichert werden
konnte; `handle()` zeigt damit das Formular erneut an. So bleibt nichts
verloren, was jemand eingetippt hat - der Altbestand warf das Formular bei
jedem Fehler weg.

Felder, die nur für bestimmte Fälle gelten, blendet Alpine ein und aus
(`x-show`), statt sie serverseitig wegzulassen. Der Zustand umschließt das
ganze Formular, nicht einzelne Abschnitte. Abhängige Auswahlfelder
(Kategorie - Unterkategorie - Inhalt) laden ihre Einträge über
`Http\OptionsEndpoint` nach; ihr Ausgangsbestand steht im Zustand, den
`linkedSelects()` bekommt, und deshalb nicht zusätzlich im Markup.

## Dialoge statt Seitenwechsel

Der Bild-Dialog im Inhaltseditor ist das Muster für alles, was früher eine
eigene Seite war: `View`-Markup mit `x-show`, eine Alpine-Komponente unter
`src/js/` und eine JSON-Schnittstelle unter `Http/`. Hochladen, auswählen,
skalieren und einfügen laufen über `Http\ImageEndpoint`, ohne dass der
Editor verlassen wird - bisher ging dabei jede ungespeicherte Änderung am
Text verloren.

Eine Schnittstelle prüft immer selbst: `Auth::isLoggedIn()` und, sobald sie
etwas verändert, `Csrf::check()`. `Kernel::ENDPOINTS` nimmt sie von der
CSRF-Middleware aus, damit sie mit JSON antworten kann statt mit einer
Weiterleitung auf eine HTML-Seite, und beantwortet sie auch über die frühere
Adresse `admin.php?action=...`.

## Oberfläche

`src/admin.css` ist die Gestaltungsgrundlage. Ganz oben stehen die Token
(Farben, Abstände, Radien, Schrift) je einmal für hell und dunkel, darunter
die Komponentenklassen. Eine neue Komponente nimmt ausschließlich Token,
keine festen Farbwerte - sonst bricht der dunkle Modus.

Der Abschnitt "Übergang" am Ende hält die Klassen des Altbestands
(`.group`, `.items`, `.info_ok`, `.config_table` …) am Leben. Jede dieser
Klassen verschwindet, sobald der zugehörige Bereich auf die neuen
Komponenten umgestellt ist.

Symbole liefert `View\Icons::render('name')` als eingebettetes SVG. Alle
Zeichnungen sind selbst angelegt, damit keine fremde Bibliothek und keine
Lizenzfrage dazukommt.

## Fremdbibliotheken

Alles, was der Browser lädt, liefert das Projekt selbst aus. Kein CDN: Das
Backend muss auch ohne Internetzugang vollständig bedienbar bleiben, und
bei jeder Bearbeitung eine Anfrage an einen Dritt-Server zu schicken ist
weder nötig noch erwünscht.

Die Versionen stehen in der `package.json` im Projektwurzelverzeichnis,
die fertigen Dateien unter `src/js/vendor/`. Beide werden eingecheckt,
damit das Docker-Image ohne npm gebaut werden kann.

| Datei | Herkunft | Wofür |
| --- | --- | --- |
| `alpine.min.js` | `alpinejs` | Reiter, Auf- und Zuklappen, abhängige Auswahlfelder |
| `editor.js` | `@codemirror/*` | Der Code-Editor der Variablen-Seite |

Aktualisieren:

```bash
npm install          # holt die Fassungen aus package.json
npm run vendor       # legt beide Dateien unter src/js/vendor/ ab
```

`editor.js` entsteht dabei aus `build/editor.js` - einer schlanken
Zusammenstellung von CodeMirror mit Zeilennummern, HTML-Hervorhebung und
Verlauf. Vorher stand dort der Monaco-Editor, der rund fünf Megabyte von
`cdn.jsdelivr.net` nachlud; die jetzige Fassung ist etwa ein Elftel davon
(siehe `tests/BEFUNDE.md`, B6).

## Erklärungen an den Feldern

Unter `src/help/` liegen die Texte, die im Formular hinter dem
Fragezeichen neben einer Beschriftung stehen. Sie sind nach Bereich
geordnet: `src/help/config/page_limit.md` gehört zur Einstellung
`page_limit` des Konfigurators. Erste Zeile ist die Überschrift, danach
folgt der Text in schlichtem Markdown — Absätze, `**fett**`, `` `Code` ``,
Listen mit `*` und eingerückte Beispielblöcke.

Ein Feld bekommt seinen Text über die Option `help`:

```php
Form::field('Besucher gilt als online für', $control, [
    'name' => 'visitors_lifetime',
    'help' => 'config/visitors_lifetime',
])
```

Der Konfigurator leitet den Verweis aus dem Feldnamen ab, weil er dort
immer `config/<feld>` lautet. `Support\Help` liest die Datei; gibt es
keine, bleibt das Feld ohne Fragezeichen. Ein Tippfehler im Verweis fällt
deshalb nicht im Browser auf, sondern in `tests/unit/HelpTest.php` — der
hält jeden Verweis aus den Controllern gegen den Bestand und prüft
außerdem, dass zu **jeder** Einstellung des Konfigurators ein Text
existiert.

Der Text steht immer im Markup, auch zugeklappt: So findet ihn die Suche
des Browsers, und ohne JavaScript ist er von vornherein zu sehen. Alpine
blendet ihn erst beim Zeichnen aus.

Die Texte stammen aus dem früheren Hilfe- und Referenzcenter im Ordner
`hilfe/`, das seit PHP 7 nicht mehr lief. Was dort zu keinem Feld gehörte
— CSS-Klassen, Template-Platzhalter, Funktionsreferenz — steht jetzt in
`docs/REFERENZ.md`.

## Was außerhalb liegt

`functions*.php` im Verzeichnis darüber teilen sich Frontend und Backend
(Bilder, Mails, Sicherungen, Sprachdateien). Sie sind nicht Teil dieses
Refactorings und werden von den Controllern als Funktionen benutzt.
