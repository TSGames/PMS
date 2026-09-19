Seitenausgabe komprimieren

Die fertige Seite wird vor dem Versand gepackt, wenn der Browser das
unterstützt. Das spart je nach Seite die Hälfte bis zwei Drittel der
übertragenen Daten und ist vor allem über Mobilfunk spürbar.

Gepackt wird nur, wenn die PHP-Erweiterung `zlib` vorhanden ist und der
Browser mitteilt, dass er damit umgehen kann. Andernfalls geht die Seite
ungepackt hinaus — es gibt also kein Risiko, die Einstellung
anzuschalten.

Nicht nötig ist sie, wenn der Webserver bereits selbst komprimiert; dann
geschieht die Arbeit doppelt, ohne dass etwas kleiner wird.
