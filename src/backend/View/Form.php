<?php

namespace Pms\Backend\View;

use Pms\Backend\Support\Errors;
use Pms\Backend\Support\Html;

/**
 * @psalm-type FieldOptions = array{
 *     name?: string,
 *     hint?: string,
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
     *        hint        Erläuterung unter dem Feld
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

        $html = '<div class="field-row"' . self::searchAttribute($label, $options) . '>'
            . $labelTag
            . '<div class="field-control' . ($error === '' ? '' : ' has-error') . '">' . $control;

        if (($options['hint'] ?? '') !== '') {
            $html .= '<div class="field-hint">' . Html::e((string)$options['hint']) . '</div>';
        }
        if ($error !== '') {
            $html .= '<div class="field-error">' . Icons::render('warning', 'icon icon-sm') . Html::e($error) . '</div>';
        }

        return $html . '</div></div>';
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

        $html = '<div class="field-row field-row-wide"' . self::searchAttribute($label, $options) . '>'
            . '<label class="field-check">' . $control . ($label === '' ? '' : ' ' . Html::e($label)) . '</label>';

        if (($options['hint'] ?? '') !== '') {
            $html .= '<div class="field-hint">' . Html::e((string)$options['hint']) . '</div>';
        }
        if ($error !== '') {
            $html .= '<div class="field-error">' . Icons::render('warning', 'icon icon-sm') . Html::e($error) . '</div>';
        }

        return $html . '</div>';
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
