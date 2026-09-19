Link-Code

Die Adresse, auf die der Menüpunkt zeigt — etwa
`https://www.beispiel.de/`.

Aus historischen Gründen nimmt das Feld auch ein Attribut-Fragment ohne
spitze Klammern entgegen:

    a href="https://www.beispiel.de/" target="_blank"

Beide Formen funktionieren; die Adresse wird herausgelöst und daraus ein
ordentlicher Verweis gebaut. Enthält die Angabe `_blank`, öffnet der
Verweis in einem neuen Fenster.

Erlaubt sind `http`, `https`, `mailto` und `ftp`. Andere Formen —
besonders `javascript:` — werden verworfen, und der Menüpunkt erscheint
dann nicht.
