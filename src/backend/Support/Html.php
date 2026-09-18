<?php

namespace Pms\Backend\Support;

/**
 * Bausteine für die Ausgabe. Alle Methoden liefern fertiges HTML und
 * maskieren dabei jeden dynamischen Wert.
 */
final class Html
{
    /** Maskiert einen Wert für die Ausgabe im Text- oder Attributkontext. */
    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Baut eine Adresse im Backend zusammen: url('cat', ['edit' => 5]). */
    public static function url(string $action, array $params = []): string
    {
        $query = array_merge(['action' => $action], $params);
        return 'admin.php?' . http_build_query($query);
    }

    /** Überschrift eines Bereichs. */
    public static function heading(string $title): string
    {
        return '<h2>' . self::e($title) . '</h2>' . "\n";
    }

    /** Öffnendes Formular-Tag inklusive CSRF-Feld. */
    public static function formOpen(string $action = '', array $params = [], array $options = []): string
    {
        $target = $action === '' ? 'admin.php' : self::url($action, $params);
        $method = $options['method'] ?? 'post';
        $upload = ($options['upload'] ?? false) ? ' enctype="multipart/form-data"' : '';

        $html = '<form action="' . self::e($target) . '" name="pms_form" method="' . self::e($method) . '"'
            . $upload . ' accept-charset="utf-8">';
        if (strtolower($method) === 'post') {
            $html .= Csrf::field();
        }
        return $html;
    }

    public static function formClose(): string
    {
        return '</form>';
    }

    public static function hidden(string $name, string|int|null $value): string
    {
        return '<input type="hidden" name="' . self::e($name) . '" value="' . self::e((string)$value) . '">';
    }

    /** Ein Link, der wie ein Schalter aussieht. */
    public static function button(string $label, string $href, string $class = 'button'): string
    {
        return '<a href="' . self::e($href) . '" class="' . self::e($class) . '">' . self::e($label) . '</a>';
    }

    /**
     * Tabelle aus vorbereiteten Zeilen.
     *
     * @param list<string>            $headers Spaltenüberschriften
     * @param list<list<string>>      $rows    Bereits fertiges HTML je Zelle
     */
    public static function table(array $headers, array $rows, string $emptyMessage = 'Keine Einträge vorhanden.'): string
    {
        if ($rows === []) {
            return '<p class="empty-hint">' . self::e($emptyMessage) . '</p>';
        }

        $html = '<div class="table-responsive"><table class="group items">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<td>' . self::e($header) . '</td>';
        }
        $html .= '</tr>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $cell) {
                $label = isset($headers[$index]) ? ' data-label="' . self::e($headers[$index]) . '"' : '';
                $html .= '<td' . $label . '>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table></div>';
    }

    /** Auswahlfeld aus Wert => Beschriftung. */
    public static function select(string $name, array $options, string|int|null $selected = null, array $attributes = []): string
    {
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . self::e($key) . '="' . self::e((string)$value) . '"';
        }

        $html = '<select name="' . self::e($name) . '"' . $attr . '>';
        foreach ($options as $value => $label) {
            $isSelected = (string)$value === (string)$selected ? ' selected' : '';
            $html .= '<option value="' . self::e((string)$value) . '"' . $isSelected . '>'
                . self::e((string)$label) . '</option>';
        }
        return $html . '</select>';
    }

    /** Eine Formularzeile mit Beschriftung. */
    public static function field(string $label, string $control, string $hint = ''): string
    {
        $html = '<tr><td>' . self::e($label) . '</td><td>' . $control;
        if ($hint !== '') {
            $html .= '<div class="example">' . self::e($hint) . '</div>';
        }
        return $html . '</td></tr>';
    }

    public static function input(string $name, string|int|null $value, array $attributes = []): string
    {
        $attributes += ['type' => 'text'];
        $attr = '';
        foreach ($attributes as $key => $attrValue) {
            $attr .= ' ' . self::e($key) . '="' . self::e((string)$attrValue) . '"';
        }
        return '<input name="' . self::e($name) . '" value="' . self::e((string)$value) . '"' . $attr . '>';
    }

    public static function checkbox(string $name, bool $checked, string $label = ''): string
    {
        $html = '<input type="checkbox" name="' . self::e($name) . '" value="1"' . ($checked ? ' checked' : '') . '>';
        if ($label !== '') {
            $html .= ' ' . self::e($label);
        }
        return $html;
    }

    public static function textarea(string $name, ?string $value, int $rows = 6, int $cols = 60): string
    {
        return '<textarea name="' . self::e($name) . '" rows="' . $rows . '" cols="' . $cols . '">'
            . self::e((string)$value) . '</textarea>';
    }

    /** Ja/Nein-Ausgabe für Übersichten. */
    public static function yesNo(mixed $value): string
    {
        return $value ? 'Ja' : 'Nein';
    }

    /** Datum im deutschen Format, leer bei fehlendem Zeitstempel. */
    public static function date(mixed $timestamp, string $format = 'd.m.Y'): string
    {
        $timestamp = (int)$timestamp;
        return $timestamp > 0 ? date($format, $timestamp) : '-';
    }
}
