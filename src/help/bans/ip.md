Gesperrte Adresse

Die IP-Adresse, die ausgesperrt werden soll — etwa `203.0.113.7`.

Ein Teil einer Adresse sperrt den ganzen Bereich dahinter: `203.0.113.`
trifft alle Adressen von `203.0.113.0` bis `203.0.113.255`. Das hilft
gegen jemanden, der bei jedem Besuch eine andere Adresse aus demselben
Netz bekommt.

**Vorsicht bei kurzen Angaben.** Verglichen wird auf Teilzeichenketten,
nicht auf Netzgrenzen. `203.0.11` trifft deshalb auch `203.0.110.x` bis
`203.0.119.x`. Und eine Angabe wie `1` sperrt jede Adresse, in der
irgendwo eine Eins vorkommt — also praktisch alle Besucher.

Die meisten Anschlüsse bekommen ihre Adresse regelmäßig neu zugeteilt.
Eine Sperre trifft nach einigen Tagen womöglich jemand anderen.
