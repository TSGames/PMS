# Aufbau des Frontends

Hier liegt, was nur die öffentliche Website angeht. Eingebunden wird es von
`src/index.php`; `rss.php` und `stats_xml.php` benutzen denselben Start.

Das Frontend wird schrittweise umgebaut. Was schon hier liegt, ist fertig;
was noch in `index.php` steht, ist Altbestand und zieht nach und nach um.

## Ablauf einer Anfrage

1. **Start** – `src/bootstrap.php` registriert den Autoloader und lädt die
   Composer-Abhängigkeiten. Er steht vor `functions.php`, weil der
   Altbestand inzwischen selbst auf Klassen unter `Pms\` zugreift.
2. **Ziel bestimmen** – `Http\Kernel::boot()` zerlegt die Adresse
   (`Http\Routes`), prüft die Sperrliste (`Http\Ban`) und verwirft
   unbekannte Aktionen. Heraus fällt ein `Http\Target`.
3. **Verarbeitung und Ausgabe** – noch in `index.php`.

## Die beiden Adressformen

PMS kennt zwei Formen, umschaltbar über die Einstellung `speciallinks`:

| | Abfrageform | sprechende Form |
| --- | --- | --- |
| Inhalt | `index.php?item=7` | `/content/Satzung-7.html` |
| Kategorie | `index.php?cat=3` | `/content/Aktuelles-3c.html` |
| Unterkategorie | `index.php?subcat=4` | `/content/Termine-4s.html` |
| Benutzer | `index.php?action=user&id=9` | `/content/Anna-9u.html` |
| Aktion | `index.php?action=guestbook` | `/action/guestbook.html` |
| Download | `index.php?action=download&id=7` | `/content/download/Satzung-7.html` |

Die sprechenden Pfade schreibt `src/.htaccess` auf `index.php?follow=…`
zurück; `Routes::resolve()` wertet sie aus. Der Namensteil dient allein der
Lesbarkeit – ausgewertet wird nur die Kennung dahinter, deshalb darf er sich
ändern, ohne dass ein Verweis bricht. Er enthält nie einen Bindestrich, weil
der Name und Kennung trennt.

Bleibt nach dem Bindestrich keine bekannte Kennung übrig, ist der vordere
Teil ein Suchbegriff: `/content/Weihnachtskonzert.html` führt zur Suche.

## Verzeichnisse

| Verzeichnis | Inhalt |
| --- | --- |
| `Http/` | Adressen (`Routes`), Ziel einer Anfrage (`Target`), Sperrliste (`Ban`), Start (`Kernel`) |

Unter `src/lib/` liegt, was Frontend und Backend sich teilen (`Pms\Support`,
`Pms\Data`). Die Richtung ist dieselbe wie im Backend: `Pms\Frontend\*` darf
nach unten greifen, nie umgekehrt. `Support\Url` kennt deshalb weder
Frontend- noch Backend-Adressen; beide melden ihre Auflösung selbst an
(`Url::resolveWith()`).

## Was noch offen ist

* Verarbeitung und Ausgabe stehen weiterhin in `index.php` (rund 1800
  Zeilen) und ziehen als Controller hierher um.
* Die Adressbildung liegt noch bei `make_link_mark()` in
  `functions_ui.php` – mit eigener, von `Routes` abweichender Logik. Sie
  zieht um, wenn die Ausgabe neu gebaut wird.
* Verändernde Formulare (Kommentare, Gästebuch, Bewertung, Umfrage,
  Registrierung) haben noch kein CSRF-Token, und ihr Versand wird über die
  Beschriftung des Schalters erkannt (`== language("…")`) statt über den
  Feldnamen.
