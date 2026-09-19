# Referenz

Diese Übersichten stammen aus dem früheren Hilfe- und Referenzcenter
(`hilfe/`), das seit PHP 7 nicht mehr lief. Sie beschreiben Dinge, die zu
keinem Formularfeld gehören und deshalb nicht in die Feldhilfe des
Backends gewandert sind (die liegt unter `src/help/`).

Stand: übernommen aus dem Bestand, geprüft gegen den heutigen Code. Wo
sich etwas geändert hat, steht ein Hinweis dabei.

## Platzhalter des Templates

### Template-Platzhalter

Hier sind verschiedene Platzhalter zu finden, die innerhalb des Templates (Template.html) verwendet werden können
**#title**
Dieser Platzhalter beinhaltet den Seitentitel. Angewendet werden sollte er wie folgt:
<title>#title</title>

**#menu**
Hierin sind alle Menü-Elemente enthalten. Ob das Menü Horizontal oder Vertikal ausgerichtet werden soll, kann im BackEnd konfiguriert werden.

**#content**
An dieser Stelle erfolgt die gesamte Inhalts-Ausgabe.

**#user_panel**
Dies wird mit dem Login-Panel ersetzt.

**#search**
Die Suchfunktion kann mit diesem Platzhalter eingebunden werden

**#topuser**
Ist dieses Element eingebunden, wird hier zufällig ein Top-User angezeigt. (Funktioniert nur, wenn im BackEnd die Top-Userliste aktiviert ist!)

**#poll**
Dieser Platzhalter ist für die Umfragen-Anzeige

**#position_row**
Dieser Platzhalter wird durch die Anzeige der aktuellen Position ersetzt.
z.B.: Home -> Downloads -> Games

**#mostdiscussed**
Hier wird zufällig ein Artikel ausgegeben, der häufiger diskutiert wurde. Weitere Einstellungen dazu findet man im BackEnd.

**#counter**
Statistikenanzeige wie Besucherzähler, IP-Adresse des Besuchers usw.

**#birthday**
An dieser Stelle wird (nur, wenn zutreffend) den Usern zum Geburtstag gratuliert.

**#mostdiscussed**
Hier wird zufällig ein Artikel ausgegeben, der häufiger diskutiert wurde. Weitere Einstellungen dazu findet man im BackEnd.

**#footer**
Die Fußzeile mit Copyright sowie den System-Informationen (Pflichteinbindung)

## CSS-Klassen der Website

Die folgenden Klassen vergibt PMS beim Erzeugen der Seiten. Ein Template
kann sie im Stylesheet ansprechen.

**Hinweis:** Beim Umbau des Frontends sind Klassen dazugekommen, die hier
noch fehlen — unter anderem `menu_list`, `menu_item`, `menu_active`,
`sidebar_block`, `sidebar_heading`, `user_counter_row`, `poll_result`,
`comment_form` und `align_center`. Die vollständige, gepflegte Liste
steht in der `README.md` des Template-Pakets.

### menu

Diese Styleclasse bezieht sich auf das Menü sowie auf die Menü-Links.

Beispiel:

.menu
{
 background-color:#007700;
 color:#ffffff;
 font-size:13px;
 font-weight:bold;
 text-align:left;
 padding:0px;
 padding-left:6px;
 border-spacing:0px;
}
a.menu:link,a.menu:visited
{
color:#ffffff;
padding-left:8px;
}
a.menu:hover
{
color:#EAEAEA;
padding-left:12px;
}

### poll_answer

Gibt das Layout für alle Antworten auf die gestellte Umfrage an.

Beispiel:

.poll_answer
{
color: #555555;
}

### register_fail

Gibt den Style an für den ausgegebenen Text bei der Registration

Beispiel:

.register_fail
{
color: #995555;
font-weight: bold;
}

### password_recover

Gibt den Style für die Ausgabe bei der Passwort-Wiederherstellung an.

Beispiel:

.password_recover
{
color: #995555;
font-weight: bold;
}

### search_url

Gibt das Layout für die sogenannte "Such-URL" an, welche man unterhalb der Suchergebnisse sieht

Beispiel:

.search_url
{
display:none; /*Keine Such-URL anzeigen*/
}

### download_button

Gibt das Style-Layout für den Download-Button auf der Download-Vorschaltseite an, welchen man mit #button einfügt.

.download_button
{
font-size: 20px;
text-align:center;
}

### user_counter

Das Layout für die Statistiken

Beispiel:

.user_counter
{
color: #181f5d;
}

### birthday

Das Layout für das #birthday - Plugin, welches nur sichtbar ist, wenn ein Benutzer Geburtstag hat.

Beispiel:

.birthday
{
color: #181f5d;
}

### code

Der Style für [code] ... [/code] markierte Bereiche

Beispiel:

.code
{
font-family:Lucida Console;
font-size:12px;
background-color:#AAAAAA;
}

### rating

Der Style für den Text "Aktuelle Bewertung: ..."

Beispiel:

.rating
{
 color: #666666;
	font-size: 12px;
}

### login_fail

Die Textgestaltung bei einem fehlgeschlagenen Login.

Beispiel:

.login_fail
{
color: #995555;
font-weight: bold;
}

### item_heading

Der Titel eines Inhaltsobjekts

Beispiel:

.item_heading
{
 color: #222232;
	font-size: 22px;
	font-weight: bold;
}

### comment_write

Der Style für den Bereich "Neuen Kommentar schreiben"

Beispiel:

.comment_write
{
color: #555555;
font-weight: bold;
}

### comment_heading

Das Layout für die Titel der Kommentare

Beispiel:

.comment_heading
{
color: #555555;
font-weight: bold;
}

### comment_content

Der eigentliche Inhalt der Kommentare wird mit diesem Layout versehen.

Beispiel:

.comment_content
{
color: #555555;
}

### comment_error

Wurde das Kommentar nicht gespeichert, wird die zurückgebrachte Fehlermeldung mit diesem Style versehen.

Beispiel:

.comment_error
{
color: #995555;
font-weight: bold;
}

### signatur

Der Style für die User-Signatur unter einem Kommentar.

Beispiel:

.signatur
{
color: #888888;
}

### signatur_line

Der Style für die Trennlinie zwischen Kommentar-Inhalt und Benutzer-Signatur

Beispiel:

.signatur_line
{
height:1px;
border:0px;
background-color:#DDDDDD;
}

### description

Der Style für die Objektbeschreibung auf einer Listenseite (nicht im Artikel, siehe dazu Klasse "item_intro")

### pms_footer

Der Style für die Links im Footer

Beispiel:

a.pms_footer:link,a.pms_footer:visited
{
color:#1424c0;
}
a.pms_footer:hover
{
color:#121a6b;
}

### position_row

Der Style für die Links im Plugin #position_row, welches die aktuelle Inhaltsposition anzeigt.

Beispiel:

a.position_row:link,a.pms_footer:visited
{
color:#660000;
}
a.position_row:hover
{
color:#881111;
}

### user_heading

Der Style für die Ausgabe für die Benutzerüberschrift eines Benutzers (?action=user&id=id)

Beispiel:

.user_heading
{
 color: #222232;
	font-size: 22px;
	font-weight: bold;
}

### list_heading

Bezieht sich auf die Titel von Kategorien und Unterkategorien, wenn die Liste angezeigt wird.

Beispiel:

.list_heading
{
 color: #222232;
	font-size: 22px;
	font-weight: bold;
}

### switch

Der Style für die Ausgabe der Wechsel-Funktion in den Inhalten (Vorheriges / Nächstes Button)
Beispiel:

.switch
{
display:none; /* Keine Ausgabe der Buttons */
}

### num_comments

Der Style für die Ausgabe des Links für die Anzahl der Kommentare in der Listenansicht, nur sichtbar wenn "Zahl der Kommentare bei Inhalts-Liste anzeigen" im BackEnd aktiviert ist.

a.num_comments:hover
{
font-weight:bold;
}

### item_user

Gibt die Klasse für den Dialog "Geschrieben von..." an

Beispiel:

.item_user
{
 color: #666666;
	font-size: 10px;
}

### download

Gibt die Klasse für das Feld "Download" an

Beispiel:

.download
{
font-size:20px;
}

### item_intro

Gibt die Art für den Layout der Einleitung zum Text (Kurzbeschreibung) an. Gilt NICHT für die Listenansicht, sondern für die Artikelansicht.

Beispiel:

.item_intro
{
 color: #666666;
	font-size: 12px;
	font-weight: bold;
}

### content_text

Gibt den Style für den eigentlichen Inhaltstext an

Beispiel:

.content_text
{
 color: #222222;
	font-size: 12px;
}

### poll_bar

Gibt den Style für die einzelnen Umfrage-Balken (bis zu 10) an

Beispiel:

.poll_bar1
{
background-color: #AA0000;
}
.poll_bar2
{
background-color: #00AA00;
}
.poll_bar3
{
background-color: #0000AA;
}
.poll_bar4
{
background-color: #AAAA00;
}
.poll_bar5
{
background-color: #AA00AA;
}
.poll_bar6
{
background-color: #00AAAA;
}
.poll_bar7
{
background-color: #001080;
}
.poll_bar8
{
background-color: #000000;
}
.poll_bar9
{
background-color: #777777;
}
.poll_bar10
{
background-color: #FC8612;
}

### poll_question

Gibt das Layout für die gestellte Frage bei den Umfragen an.

Beispiel:

.poll_question
{
color: #555555;
font-weight: bold;
}

## Funktionsreferenz

### Allgemeines

Dieser Teil beschäftigt sich mit der Programmierung bzw. Modifikation und Funktionserweiterung des Systems. Für diesen Teil werden rudimentäre Programmierkenntnisse in PHP vorausgesetzt. Außerdem sollten Grundlagen über MySQL vorhanden sein. Für Einsteiger bzw. Neulinge ist dieser Part nicht gedacht.

Hinweis: Wir empfehlen immer, PHP-Code über die Tags [php] und [/php] einzubinden. Das direkte bearbeiten der Scripte ist nicht zu empfehlen, da bei einem Systemupdate diese wieder überschrieben werden!

Der PHP-Code lässt sich mit diesen Tags sowohl in jeden Artikel, als auch direkt in das Template einbinden. Um die Funktion zu prüfen, schreiben Sie einfach in ein Inhaltsobjekt:

[php]echo "Ein kleiner Test: Ausgabe einer Zahl zwischen 1 und 100: ".rand(1,100);[/php]

Nun müsste das System eine Zufallszahl und den Text ausgeben.
Wichtig! Wir empfehlen immer, bei der Eingabe von PHP-Scripten den Grafischen HTML-Editor (TinyMCE) NICHT zu verwenden, da er eventuelle Zeichen nicht richtig in den Code schreibt und somit Fehler verursachen kann.

Tipp: PHP-Code kann mit diesen Tags auch in eine Variable eingebettet werden.

### Platzhalter

Dies ist noch keine direkte Programmierung, aber eher für fortgeschrittene Benutzer empfohlen.

Das System hat ein integrietes Modul für dynamische Verlinkungen. Es gibt dabei folgende Platzhalter, wodurch sich das Modul aktiviert:

#subcat:id (Umfangreiche Verlinkung einer Unterkategorie)
#item:id (Umfangreiche Verlinkung eines Inhaltes)
#user:id (Umfangreiche Verlinkung eines Benutzers)
#cat_link:id (Simple Verlinkung einer Kategorie)
#subcat_link:id (Simple Verlinkung einer Unterkategorie)
#item_link:id (Simple Verlinkung eines Inhaltes)
#user_link:id (Simple Verlinkung eines Benutzers)

Der große Vorteil hierbei: Die Verlinkungen sind dynamisch. Das bedeutet, ändert sich einmal der Name des verlinkten Artikels, ändert sich dieser automatisch in der Verlinkung. Die ID's der Objekte können Sie auf der jeweiligen Übersichtsseite im BackEnd finden.

Tipp: Diese Funktionalität kann auch in eine Variable eingebunden werden

Hinweis: Existiert das Objekt mit der gegebenen ID nicht, wird nichts ausgegeben.

Beispiele:

#item:100
Verlinkt den Artikel mit der ID 100.

#subcat:12
Verlinkt auf die Unterkategorie mit der ID 12

#user:1
Verlinkt den User mit der ID 1 (in der Regel der Administrator)

### Verlinken auf Inhalte

Dieser Teil beschäftigt sich mit dem manuellen Verfassen von Link-Codes auf Kategorien, Unterkategorien, Inhalte, Benutzer bzw. Spezialmodule.

Das Prinzip dabei ist relativ einfach. Ein Link hat immer folgende Struktur.

index.php?angabe1=wert1&angabe2=wert2 ...

Die genannten Angaben beziehen sich in dem Fall auf die Kategorie, Unterkategorie oder Inhalte, und die Werte sind die jeweilige ID.

Beispiel:

index.php?item=15
Verlinkt auf das Inhaltsobjekt mit der ID 15

index.php?subcat=12
Verlinkt auf die Liste der Inhalte in der Unterkategorie mit der ID 12

index.php?cat=2
Verlinkt auf die Liste der Unterkategorien in der Kategorie mit der ID 2

index.php?action=user&id=1
Zeigt die Seite des Benutzers mit der ID 1 an (normalerweise der Systemadmin)

index.php?action=guestbook
Verlinkt auf das Gästebuch

index.php?action=download&id=50
Verlinkt auf die Download-Vorschaltseite des Inhaltobjekts mit der ID 50. Funktioniert nur, wenn "Download-Vorschaltseite verwenden" im BackEnd aktiviert ist

index.php?action=register
Verlinkt auf die Seite, auf der sich die Benutzer Registrieren können.

index.php?action=password_recover
Verlinkt auf die Seite, wo die Benutzer ihr Passwort anfordern können.

index.php?action=topuser
Zeigt die Top-Userliste an

index.php?action=logout
Loggt den Benutzer aus

index.php?action=sitemap
Öffnet die Sitemap

index.php?action=user_panel
Öffnet das Benutzerkonto des aktuell eingeloggten Benutzers. Ist der Benutzer nicht eingeloggt, wird die Spezialseite "Gesperrter/Ungültiger Content" angezeigt.

Hinweis: Wurde das gegebene Element nicht gefunden/ist nicht verfügbar bzw. ist das Systemmodul nicht aktiviert, wird die Spezialseite "Gesperrter/Ungültiger Content" angezeigt.

### Grundelemente des Systems

Dieser Teil bezieht sich nun explizit auf die Programmierung.
Es gibt folgende Grundelemente, auf die der PHP-Code, der eingebunden wurde, zugreifen kann:

$cat
Diese Variable beinhaltet die aktuelle Kategorie bzw. die übergeordnete Kategorie, wenn die aktuelle Position z.b. eine Unterkategorie ist. Ist keine Kategorie gewählt, ist der Wert 0. Andernfalls ein Wert > 0, der die ID der Kategorie angibt.

Beispiel: 
[php]echo $cat;[/php]
Gibt die aktuelle Kategorie aus

$subcat
Diese Variable beinhaltet die aktuelle Unterkategorie bzw. die übergeordnete Unterkategorie, wenn die aktuelle Position ein Inhaltsobjekt ist. Ist keine Unterkategorie gewählt, ist der Wert 0. Andernfalls ein Wert > 0, der die ID der Unterkategorie angibt.

Beispiel:
#subcat:[php]echo $subcat;[/php]
Würde eine dynamische Verlinkung auf die aktuelle Unterkategorie bzw. die Unterkategorie, inder sich das aktuelle Inhaltsobjekt befindet, ausgeben (siehe Platzhalter)

$item
Diese Variable beinhaltet die ID des aktuellen Inhaltsobjekts. 
Hinweis: Auch Spezialseiten haben eine ID!
Ist gerade eine Kategorie bzw. Unterkategorie geöffnet und kein Inhaltsobjekt gewählt, ist dieser Wert 0. Andernfalls ein Wert >0.

Beispiel:
#item:[php]echo $item;[/php]
Würde eine dynamische Verlinkung auf die aktuelle Seite erzeugen.

$id
Dies ist ein Wert, der nur verfügbar ist, wenn die Download-Seite gewählt ist. Dann enthält dieser Wert die ID des Download-Objektes (>0), dass gedownloadet werden soll. Andernfalls ist dieser Wert 0.

Beispiel:
#item:[php]echo $id;[/php]
Würde eine dynamische Verlinkung zum aktuellen Download erzeugen. Funktioniert nur auf der Spezialseite "Download-Seite"

### check_mail (Adresse)

Überprüft, ob die gegebene E-Mailadresse gültig ist und gibt bei Erfolg 1 zurück, sonst 0.

Beispiel:
[php]
$mail="webmaster@tsgames.de";
if(check_mail($mail)==1)
{
echo "Die Mailadresse $mail ist gültig!";
}
else
{
echo "Die Mailadresse $mail ist nicht gültig!";
}
[/php]

### make_mail(Mail)

Erzeugt eine Mail-Ausgabe. Ist die Funktion E-Mailadressen verschlüsseln zusätzlich aktiviert, wird die Mail verschlüsselt.

Beispiel:

[php]
echo make_mail("webmaster@tsgames.de");
[/php]

### count_db_exp(Tabelle,Bedingung)

Zählt die Inhalte einer Tabelle. Optional kann eine Bedingung angegeben werden.

Beispiel:

[php]
echo "Insgesamt gibt es ".count_db_exp("comments","")." Kommentare.";
echo "Das Inhaltsobjekt mit der ID 5 hat ".count_db_exp("comments","WHERE item = 5")." Kommentare.";
[/php]

### [nohtml]

def(String)

Ersetzt alle Umbrüche von "String" mit 
, um Sie in HTML korrekt anzuzeigen.

Beispiel:

[php]
echo def("Dies
ist
ein
Test!");
[/php]

### delete_sessions()

Löscht alle aktuellen Sessions, d.h. der User ist danach nicht mehr eingeloggt und muss sich erneut einloggen.

Beispiel:

[php]
echo "Bitte neu einloggen!";
delete_sessions();
[/php]

### do_export()

Macht ein Backup des aktuellen Datenbestandes (Selbe Funktion wie beim Klicken im Backup-Manager auf "Neues Backup erstellen")

Beispiel:

[php]
echo "Erzeuge Backup...";
$result=do_export();
if($result[0]==$result[1])
{
echo "Backup erfolgreich!";
}
elseif($result[0]>0)
{
echo "Backup nur teilweise erfolgreich!";
}
else
{
echo "Backup fehlgeschlagen!";
}
[/php]

### from_db(Tabelle,ID,Spalte)

Gibt eine Zelle aus der Datenbank zurück.

Beispiel

[php]
echo "Name: ".from_db("item",$item,"name").", Beschreibung: ".from_db("item",$item,"description");
[/php]

[php]
echo "Kategorie: ".from_db("cat",$cat,"name");
[/php]

### get_weekday(Time)

Gibt den Wochentag, z.B. "Montag" zurück. Als Time muss ein UNIX-Timestamp gegeben werden.

Beispiel:

[php]
echo "Heute ist ".get_weekday(time()).", der ".date("d.m.Y");
[/php]

### make_contentsmall(Typ,ID)

Erzeugt eine kleine Content-Ausgabe, ähnlich wie bei den Platzhaltern #item, #subcat oder #user.

Beispiel:

[php]
echo make_contentsmall("item",10);
[/php]
Erzeugt dieselbe Ausgabe wie #item:10

### make_date(Time,long)

Gibt eine formatierte Datumsangabe zurück. 

Beispiel:

[php]
make_date(time(),1);
[/php]
Gibt aus: Heute, um xx:xx Uhr

[php]
make_date(time(),0);
[/php]
Gibt aus: Heute, xx:xx
