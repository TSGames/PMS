<?php

namespace Pms\Frontend\View;

use Pms\Support\Url;

/**
 * Fuellt das Template der Website.
 *
 * Das Template ist eine HTML-Datei mit 16 Platzhaltern (#content, #menu,
 * #title …), die der Kunde selbst pflegt. Bisher stand die Fuellung als
 * loser Block am Ende von index.php: zwei ineinandergeschachtelte
 * Aufbereitungslaeufe ueber das ganze Dokument, eine str_replace-Liste aus
 * zwei parallelen Feldern und mittendrin zwei Sonderfaelle.
 *
 * Hier steht sie an einer Stelle, und welcher Platzhalter welchen Wert
 * bekommt, ist eine benannte Zuordnung statt zweier Felder, deren
 * Reihenfolge zusammenpassen muss.
 */
final class Template
{
    /**
     * Platzhalter im Template => Schluessel der Werte.
     *
     * Die Namen sind Teil der Schnittstelle zum Kunden-Template und
     * duerfen sich nicht aendern.
     *
     * @var list<string>
     */
    private const PLACEHOLDERS = [
        'content', 'title', 'menu', 'user_panel', 'poll', 'footer', 'counter',
        'birthday', 'topuser', 'mostdiscussed', 'search', 'position_row',
        'latest_comments', 'comments_list', 'newsletter', 'pms_styles',
    ];

    /** @param array<string, string> $values Platzhaltername => fertiges HTML */
    public function __construct(
        private readonly string $source,
        private readonly array $values,
    ) {
    }

    /**
     * Das gefuellte Dokument.
     *
     * Auch beim Bearbeiten direkt auf der Seite: Frueher gab der
     * Bearbeitungsmodus nur den Inhalt zurueck, ohne Kopf, Menue und
     * Stylesheet - die Seite stand dann nackt im Browser. Dass der
     * rohe Inhalt im Eingabefeld nicht ausgewertet wird, sorgt
     * index.php, indem es make_dynamic() dort ueberspringt; das
     * Template selbst ist zu diesem Zeitpunkt laengst aufbereitet.
     */
    public function render(): string
    {
        $document = $this->prepare($this->ensurePlaceholders($this->source));

        $search = [];
        $replace = [];
        foreach (self::PLACEHOLDERS as $name) {
            $search[] = '#' . $name;
            $replace[] = $this->values[$name] ?? '';
        }

        return str_replace($search, $replace, $document);
    }

    /**
     * Ergaenzt Platzhalter, die ein aelteres Template noch nicht kennt.
     *
     * Ohne #comments_list stuenden die Kommentare nirgends; ohne
     * #pms_styles fehlten das Stylesheet von PMS und die Angabe, wie breit
     * die Seite auf einem Telefon dargestellt werden soll.
     */
    private function ensurePlaceholders(string $document): string
    {
        if (!str_contains($document, '#comments_list')) {
            $document = str_replace('#content', '#content#comments_list', $document);
        }

        if (!str_contains($document, '#pms_styles')) {
            $document = str_replace('</head>', '#pms_styles</head>', $document);
        }

        return $document;
    }

    /**
     * Was hinter #pms_styles steht.
     *
     * Die Angabe zur Darstellungsbreite gehoert dazu: Ohne sie zeichnet ein
     * Telefon die Seite in Desktop-Breite und verkleinert sie, sodass jede
     * Schrift unlesbar wird. Ein Template, das sie selbst mitbringt,
     * behaelt seine eigene - deshalb die Pruefung.
     */
    public static function styles(string $document): string
    {
        $styles = '';
        if (!preg_match('/<meta[^>]+name=["\']?viewport/i', $document)) {
            $styles .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        }

        // Absolut, nicht "pms.css": Unter einer sprechenden Adresse wie
        // /content/probenplan.html zeigte der relative Verweis auf
        // /content/pms.css.
        return $styles . '<link rel="stylesheet" type="text/css" href="' . Url::asset('pms.css') . '">';
    }

    /**
     * Bereitet das Dokument auf: Sondermarken, Verweise, Variablen.
     *
     * Der Durchlauf geschieht zweimal, weil eine Ersetzung neue Marken
     * hervorbringen kann - eine Variable etwa, die selbst einen Verweis
     * enthaelt. Ein dritter Durchlauf hat noch nie etwas veraendert.
     */
    private function prepare(string $document): string
    {
        for ($i = 0; $i < 2; $i++) {
            $document = (string)replace_dynamic(do_check(make_dynamic(trim($document))));
        }

        return $document;
    }

    /** Die Namen aller Platzhalter, fuer Tests und den Editor. */
    public static function placeholders(): array
    {
        return array_map(static fn(string $name): string => '#' . $name, self::PLACEHOLDERS);
    }
}
