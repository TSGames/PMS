<?php

namespace Pms\Backend\View;

use Pms\Support\Errors;
use Pms\Support\Help;
use Pms\Support\Html;

/**
 * @psalm-type FieldOptions = array{
 *     name?: string,
 *     hint?: string,
 *     help?: string,
 *     required?: bool,
 *     for?: string,
 *     searchable?: bool,
 *     attributes?: array<string, string>
 * }
 *
 * Bausteine für Formulare.
 *
 * Alle Formulare des Backends sehen gleich aus: eine Karte, darin Abschnitte
 * mit einem Raster aus Beschriftung und Feld, unten eine Fußleiste mit
 * "Speichern" links und "Abbrechen" daneben. Bisher waren es Tabellen mit
 * zentrierten Schaltern, die je Bereich anders aussahen.
 *
 * Fehlermeldungen stehen am betroffenen Feld; sie kommen aus Support\Errors.
 */
final class Form
{
    /** Rahmen eines Formulars: Abschnitte und Fußleiste. */
    public static function card(string $sections, string $actions = ''): string
    {
        return '<div class="form-card">' . $sections . $actions . '</div>';
    }

    /**
     * Ein Abschnitt des Formulars.
     *
     * @param array<string, string> $attributes Zusätzliche Attribute, etwa für Alpine
     */
    public static function section(string $title, string $fields, array $attributes = []): string
    {
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Html::e($value) . '"';
        }

        $html = '<div class="form-section"' . $attr . '>';
        if ($title !== '') {
            $html .= '<div class="form-section-title">' . Html::e($title) . '</div>';
        }
        return $html . '<div class="form-grid">' . $fields . '</div></div>';
    }

    /**
     * Eine Zeile aus Beschriftung und Bedienelement.
     *
     * @param FieldOptions $options
     *        name        Feldname, unter dem eine Fehlermeldung abgelegt wurde
     *        hint        Erläuterung unter dem Feld, ein Halbsatz
     *        help        Verweis auf einen längeren Text, etwa "config/page_limit"
     *        for         Kennung des Bedienelements für die Beschriftung
     *        searchable  Zeile für das Suchfeld des Konfigurators auffindbar machen
     */
    public static function field(string $label, string $control, array $options = []): string
    {
        $name = $options['name'] ?? '';
        $error = $name === '' ? '' : Errors::get($name);
        $required = ($options['required'] ?? false) ? '<span class="required" title="Pflichtfeld">*</span>' : '';
        $for = $options['for'] ?? $name;

        $labelTag = $for === ''
            ? '<span class="field-label">' . Html::e($label) . $required . '</span>'
            : '<label class="field-label" for="' . Html::e($for) . '">' . Html::e($label) . $required . '</label>';

        $html = '<div class="field-row"' . self::searchAttribute($label, $options) . self::helpState($options) . '>'
            . '<div class="field-label-cell">' . $labelTag . self::helpToggle($options) . '</div>'
            . '<div class="field-control' . ($error === '' ? '' : ' has-error') . '">' . $control;

        if (($options['hint'] ?? '') !== '') {
            $html .= '<div class="field-hint">' . Html::e((string)$options['hint']) . '</div>';
        }
        if ($error !== '') {
            $html .= '<div class="field-error">' . Icons::render('warning', 'icon icon-sm') . Html::e($error) . '</div>';
        }

        return $html . self::helpText($options) . '</div></div>';
    }

    /**
     * Das Fragezeichen neben der Beschriftung.
     *
     * Es ist eine Schaltfläche und kein Verweis: Es führt nirgendwo hin,
     * sondern klappt den Text unter dem Feld auf. Ohne JavaScript bleibt der
     * Text trotzdem lesbar - dann steht er von vornherein offen da, siehe
     * helpText().
     *
     * @param FieldOptions $options
     */
    private static function helpToggle(array $options): string
    {
        $help = self::help($options);
        if ($help === null) {
            return '';
        }

        return '<button type="button" class="field-help-toggle" aria-expanded="false"'
            . ' aria-controls="' . Html::e(self::helpId($options)) . '"'
            . ' title="' . Html::e($help['title']) . '"'
            . ' @click="offen = !offen" :aria-expanded="offen ? \'true\' : \'false\'">'
            . Icons::render('help', 'icon icon-sm')
            . '<span class="visually-hidden">Erklärung zu "' . Html::e($help['title']) . '"</span>'
            . '</button>';
    }

    /**
     * Der Erklärungstext unter dem Feld.
     *
     * Er steht im Markup, auch wenn er zugeklappt ist - nur so findet ihn
     * die Suche des Browsers, und ohne JavaScript ist er überhaupt zu sehen.
     * Alpine blendet ihn erst beim Zeichnen aus (x-cloak).
     *
     * @param FieldOptions $options
     */
    private static function helpText(array $options): string
    {
        $help = self::help($options);
        if ($help === null) {
            return '';
        }

        return '<div class="field-help" id="' . Html::e(self::helpId($options)) . '"'
            . ' x-show="offen" x-cloak>'
            . '<div class="field-help-title">' . Html::e($help['title']) . '</div>'
            . self::helpBody($help['body'])
            . '</div>';
    }

    /**
     * Macht aus dem gespeicherten Text HTML.
     *
     * Die Texte sind schlichtes Markdown: Absätze durch Leerzeilen,
     * **fett**, Listen mit *, eingerückte Blöcke als Beispiel. Mehr kann
     * und soll hier nicht entstehen - der Text wird maskiert, bevor die
     * wenigen Auszeichnungen wieder eingesetzt werden.
     */
    private static function helpBody(string $body): string
    {
        $html = '';
        foreach (preg_split('/\n{2,}/', trim($body)) ?: [] as $block) {
            $block = trim($block, "\n");
            if ($block === '') {
                continue;
            }

            // Eingerückte Zeilen sind ein Beispiel und bleiben, wie sie sind
            if (str_starts_with($block, '    ')) {
                $html .= '<pre class="field-help-example">'
                    . Html::e(preg_replace('/^    /m', '', $block) ?? '') . '</pre>';
                continue;
            }

            $lines = preg_split('/\R/', $block) ?: [];
            $isList = $lines !== [] && str_starts_with(trim($lines[0]), '* ');

            if ($isList) {
                $items = '';
                foreach (self::listItems($lines) as $item) {
                    $items .= '<li>' . self::inline($item) . '</li>';
                }
                $html .= '<ul>' . $items . '</ul>';
                continue;
            }

            $html .= '<p>' . self::inline(implode(' ', array_map('trim', $lines))) . '</p>';
        }

        return $html;
    }

    /**
     * Fasst die Zeilen eines Listenblocks zu Einträgen zusammen; eine
     * eingerückte Folgezeile gehört zum Eintrag davor.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private static function listItems(array $lines): array
    {
        $items = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '* ')) {
                $items[] = substr($trimmed, 2);
            } elseif ($items !== [] && $trimmed !== '') {
                $items[count($items) - 1] .= ' ' . $trimmed;
            }
        }
        return $items;
    }

    /** Maskiert den Text und setzt danach **fett** und `Code` ein. */
    private static function inline(string $text): string
    {
        $html = Html::e($text);
        $html = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html) ?? $html;
        return preg_replace('/`(.+?)`/u', '<code>$1</code>', $html) ?? $html;
    }

    /**
     * @param FieldOptions $options
     * @return array{title: string, body: string}|null
     */
    private static function help(array $options): ?array
    {
        $reference = (string)($options['help'] ?? '');
        return $reference === '' ? null : Help::load($reference);
    }

    /** @param FieldOptions $options */
    private static function helpId(array $options): string
    {
        return 'help-' . str_replace('/', '-', (string)($options['help'] ?? ''));
    }

    /**
     * Der Zustand des Aufklappens, an der Zeile gehalten.
     *
     * Nur Zeilen mit Erklärung bekommen ihn - sonst legte jede Zeile des
     * Formulars einen Alpine-Bereich an, den niemand braucht.
     *
     * @param FieldOptions $options
     */
    private static function helpState(array $options): string
    {
        return self::help($options) === null ? '' : ' x-data="{ offen: false }"';
    }

    /**
     * Eine Zeile ohne Beschriftungsspalte - für Kontrollkästchen, deren Text
     * bereits neben dem Kästchen steht.
     *
     * @param FieldOptions $options
     */
    public static function check(string $control, string $label = '', array $options = []): string
    {
        $error = ($options['name'] ?? '') === '' ? '' : Errors::get((string)$options['name']);

        $html = '<div class="field-row field-row-wide"' . self::searchAttribute($label, $options) . self::helpState($options) . '>'
            . '<div class="field-check-row">'
            . '<label class="field-check">' . $control . ($label === '' ? '' : ' ' . Html::e($label)) . '</label>'
            . self::helpToggle($options)
            . '</div>';

        if (($options['hint'] ?? '') !== '') {
            $html .= '<div class="field-hint">' . Html::e((string)$options['hint']) . '</div>';
        }
        if ($error !== '') {
            $html .= '<div class="field-error">' . Icons::render('warning', 'icon icon-sm') . Html::e($error) . '</div>';
        }

        return $html . self::helpText($options) . '</div>';
    }

    /** Eine Zeile, die die volle Breite einnimmt (Editor, Vorschau, Tabelle). */
    public static function wide(string $content, array $attributes = []): string
    {
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Html::e($value) . '"';
        }
        return '<div class="field-row field-row-wide"' . $attr . '>' . $content . '</div>';
    }

    /**
     * Fußleiste: Speichern links, Abbrechen daneben, weitere Schalter rechts.
     *
     * @param string $name  Name des Absende-Schalters
     */
    public static function actions(string $name, string $label, string $cancelUrl, string $extra = ''): string
    {
        return '<div class="form-actions">'
            . '<input type="submit" name="' . Html::e($name) . '" value="' . Html::e($label) . '">'
            . ($cancelUrl === '' ? '' : Html::button('Abbrechen', $cancelUrl, 'btn btn-secondary'))
            . ($extra === '' ? '' : '<span class="form-actions-end">' . $extra . '</span>')
            . '</div>';
    }

    /**
     * Auswahl aus wenigen Möglichkeiten als Schalterreihe statt als
     * Auswahlfeld - Alpine hält den Wert fest, damit abhängige Felder
     * unmittelbar reagieren.
     *
     * @param array<int|string, string> $options Wert => Beschriftung
     */
    public static function segmented(string $name, array $options, int|string $selected, string $model = ''): string
    {
        $html = '<div class="segmented" role="radiogroup">';
        foreach ($options as $value => $label) {
            $id = $name . '-' . $value;
            $checked = (string)$value === (string)$selected ? ' checked' : '';
            $bind = $model === '' ? '' : ' x-model.number="' . Html::e($model) . '"';
            $html .= '<input type="radio" class="visually-hidden" id="' . Html::e($id) . '"'
                . ' name="' . Html::e($name) . '" value="' . Html::e((string)$value) . '"' . $checked . $bind . '>'
                . '<label class="segmented-option" for="' . Html::e($id) . '">' . Html::e($label) . '</label>';
        }
        return $html . '</div>';
    }

    /**
     * Zusätzliche Attribute einer Zeile.
     *
     * Mit searchable bekommt die Zeile ihren Beschriftungstext als
     * data-search und blendet sich aus, wenn sie nicht zur Suche im
     * Konfigurator passt.
     *
     * @param FieldOptions $options
     */
    private static function searchAttribute(string $label, array $options): string
    {
        $attributes = $options['attributes'] ?? [];

        if (($options['searchable'] ?? false) === true) {
            $text = mb_strtolower($label . ' ' . (string)($options['hint'] ?? ''));
            $attributes['data-search'] = trim($text);
            $attributes['x-show'] = 'matches($el)';
        }

        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Html::e($value) . '"';
        }
        return $attr;
    }
}
