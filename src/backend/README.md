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

| Verzeichnis | Inhalt |
| --- | --- |
| `Controller/` | Je ein Controller pro Bereich des Backends |
| `Data/` | `Db` – Datenbankzugriff mit vorbereiteten Anweisungen |
| `Http/` | Router, Navigation, JSON-Schnittstellen, Update-Prüfung |
| `Support/` | Eingaben, Anmeldung, Token, Meldungen, HTML-Bausteine |
| `View/` | Grundgerüst, Templates und Symbole |

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

Für Interaktion im Browser ist **Alpine.js 3.17.3** eingebunden
(`src/js/vendor/alpine.min.js`, bezogen von
`https://cdn.jsdelivr.net/npm/alpinejs@3.17.3/dist/cdn.min.js`). Die Datei
liegt bewusst im Projekt statt an einem CDN: Das Backend muss auch ohne
Internetzugang vollständig bedienbar bleiben (siehe `tests/BEFUNDE.md`, B6).
Zum Aktualisieren die neue Fassung an dieselbe Stelle legen und die Version
hier nachtragen.

## Was außerhalb liegt

`functions*.php` im Verzeichnis darüber teilen sich Frontend und Backend
(Bilder, Mails, Sicherungen, Sprachdateien). Sie sind nicht Teil dieses
Refactorings und werden von den Controllern als Funktionen benutzt.
