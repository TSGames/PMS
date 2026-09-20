# Handbuch "Inhalte pflegen"

Anleitung für Redakteure, die Texte, Termine und Bilder der Website
betreuen. Ergebnis ist `Inhalte-pflegen.pdf`.

## Was hier liegt

| Datei | Inhalt |
| --- | --- |
| `anleitung.html` | Der Text |
| `anleitung.css` | Aussehen im Druck |
| `bilder/` | Bildschirmfotos, erzeugt aus dem laufenden Backend |
| `build.js` | Erzeugt das PDF |
| `Inhalte-pflegen.pdf` | Das Ergebnis |

## Neu erzeugen

Die Bilder stammen nicht aus einem Zeichenprogramm, sondern aus dem
laufenden Mock-System. Ändert sich die Oberfläche, werden sie neu
aufgenommen - sonst veraltet das Handbuch, ohne dass es jemand merkt.

```bash
php tests/mock/setup.php
bash tests/mock/server.sh start

cd tests/e2e
npx playwright test --project=screenshots screenshots/anleitung.spec.js

cd ../..
node docs/anleitung/build.js
```

Gedruckt wird mit dem Chromium, den auch die Tests benutzen.
Zusammengelegt werden Titelseite und Rumpf mit pymupdf
(`pip install pymupdf`) - nur so bleibt die Titelseite randlos und ohne
Seitenzahl.

## Die roten Ziffern in den Bildern

`markiere()` in `tests/e2e/screenshots/anleitung.spec.js` setzt sie an
echte Elemente der Seite, nicht an feste Koordinaten. Beim nächsten Umbau
der Oberfläche wandern sie also mit. Die Ziffern gehören zu den
nummerierten Schritten im Text daneben; wer eine Marke ergänzt, ergänzt
auch den Schritt.
