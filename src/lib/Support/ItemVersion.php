<?php

namespace Pms\Support;

/**
 * Eine gesicherte Fassung eines Inhalts, lesbar gemacht.
 *
 * In den Sicherungen steht je Datensatz eine INSERT-Anweisung:
 *
 *     INSERT INTO pms_item (id,cat,…) VALUES ('5','1',…);
 *
 * Bisher liess sich davon nur der Zeitpunkt anzeigen. Wer eine frueherere
 * Fassung einspielen wollte, musste raten, was sich seitdem geaendert hat
 * - und ob ueberhaupt etwas. Diese Klasse liest die Werte aus der
 * Anweisung und stellt sie der aktuellen Fassung gegenueber.
 */
final class ItemVersion
{
    /** Feldname => Beschriftung. Felder ohne Eintrag werden nicht verglichen. */
    private const LABELS = [
        'name' => 'Titel',
        'description' => 'Kurzbeschreibung',
        'content' => 'Inhalt',
        'cat' => 'Kategorie',
        'subcat' => 'Unterkategorie',
        'typ' => 'Typ',
        'special' => 'Art des Spezialinhalts',
        'sort' => 'Sortierung',
        'link' => 'Download-Link',
        'image' => 'Bild',
        'user' => 'Verfasser',
        'available' => 'Freigegeben',
        'visible' => 'In Listen sichtbar',
        'showuser' => 'Verfasser anzeigen',
        'rate' => 'Bewertung',
        'comments' => 'Kommentare',
    ];

    /** Felder, deren Unterschiede zeilenweise gezeigt werden. */
    private const LONG = ['description', 'content'];

    /**
     * Die Werte einer INSERT-Anweisung als Feldname => Wert.
     *
     * @return array<string, string> Leer, wenn die Anweisung nicht passt
     */
    public static function values(string $statement): array
    {
        $start = strpos($statement, '(');
        $middle = strpos($statement, ') VALUES (');
        if ($start === false || $middle === false || $middle < $start) {
            return [];
        }

        $columns = array_map(
            'trim',
            explode(',', substr($statement, $start + 1, $middle - $start - 1))
        );

        $rest = substr($statement, $middle + strlen(') VALUES ('));
        $values = self::splitValues($rest);

        if (count($columns) !== count($values)) {
            return [];
        }

        return array_combine($columns, $values);
    }

    /**
     * Die Werte einer Zeile, aufgeteilt an den Kommas zwischen ihnen.
     *
     * Ein Komma innerhalb eines Werts zaehlt nicht, und zwei
     * Hochkommata hintereinander sind ein Hochkomma im Text - so
     * schreibt do_export() die Sicherung.
     *
     * @return list<string>
     */
    private static function splitValues(string $rest): array
    {
        $values = [];
        $current = '';
        $inside = false;
        $length = strlen($rest);

        for ($i = 0; $i < $length; $i++) {
            $char = $rest[$i];

            if (!$inside) {
                if ($char === "'") {
                    $inside = true;
                } elseif ($char === ')') {
                    break;
                }
                continue;
            }

            if ($char !== "'") {
                $current .= $char;
                continue;
            }

            if (($rest[$i + 1] ?? '') === "'") {
                $current .= "'";
                $i++;
                continue;
            }

            $values[] = str_replace(['\\r\\n', '\\n', '\\r'], ["\n", "\n", "\n"], $current);
            $current = '';
            $inside = false;
        }

        return $values;
    }

    /**
     * Was sich zwischen der gesicherten und der aktuellen Fassung
     * unterscheidet.
     *
     * @param array<string, string> $saved Werte aus der Sicherung
     * @param object $current Der Datensatz, wie er jetzt in der Datenbank steht
     * @return list<array{field: string, label: string, long: bool, saved: string, now: string}>
     */
    public static function differences(array $saved, object $current): array
    {
        $differences = [];

        foreach (self::LABELS as $field => $label) {
            if (!array_key_exists($field, $saved)) {
                continue;
            }

            $now = self::normalize((string)($current->$field ?? ''));
            $was = self::normalize($saved[$field]);

            if ($was === $now) {
                continue;
            }

            $differences[] = [
                'field' => $field,
                'label' => $label,
                'long' => in_array($field, self::LONG, true),
                'saved' => $was,
                'now' => $now,
            ];
        }

        return $differences;
    }

    /** Zeilenenden vereinheitlichen; sonst zaehlt \r\n gegen \n als Unterschied. */
    private static function normalize(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    /**
     * Zeilenweiser Vergleich zweier Texte.
     *
     * Liefert je Zeile ein Zeichen und den Text: " " unveraendert,
     * "-" nur in der Sicherung, "+" nur in der aktuellen Fassung.
     * Unveraenderte Bloecke werden auf $context Zeilen gekuerzt.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function lineDiff(string $saved, string $now, int $context = 2): array
    {
        $a = explode("\n", $saved);
        $b = explode("\n", $now);
        $script = self::diffLines($a, $b);

        return self::shorten($script, $context);
    }

    /**
     * @param list<string> $a
     * @param list<string> $b
     * @return list<array{0: string, 1: string}>
     */
    private static function diffLines(array $a, array $b): array
    {
        $lengths = self::lcsTable($a, $b);
        $script = [];
        $i = 0;
        $j = 0;

        while ($i < count($a) && $j < count($b)) {
            if ($a[$i] === $b[$j]) {
                $script[] = [' ', $a[$i]];
                $i++;
                $j++;
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $script[] = ['-', $a[$i]];
                $i++;
            } else {
                $script[] = ['+', $b[$j]];
                $j++;
            }
        }

        for (; $i < count($a); $i++) {
            $script[] = ['-', $a[$i]];
        }
        for (; $j < count($b); $j++) {
            $script[] = ['+', $b[$j]];
        }

        return $script;
    }

    /**
     * Laenge der laengsten gemeinsamen Teilfolge, von hinten aufgebaut.
     *
     * @param list<string> $a
     * @param list<string> $b
     * @return list<list<int>>
     */
    private static function lcsTable(array $a, array $b): array
    {
        $rows = count($a);
        $cols = count($b);
        $table = array_fill(0, $rows + 1, array_fill(0, $cols + 1, 0));

        for ($i = $rows - 1; $i >= 0; $i--) {
            for ($j = $cols - 1; $j >= 0; $j--) {
                $table[$i][$j] = $a[$i] === $b[$j]
                    ? $table[$i + 1][$j + 1] + 1
                    : max($table[$i + 1][$j], $table[$i][$j + 1]);
            }
        }

        return $table;
    }

    /**
     * Kuerzt lange unveraenderte Strecken auf $context Zeilen je Seite.
     *
     * @param list<array{0: string, 1: string}> $script
     * @return list<array{0: string, 1: string}>
     */
    private static function shorten(array $script, int $context): array
    {
        $keep = [];
        foreach ($script as $index => [$sign]) {
            if ($sign === ' ') {
                continue;
            }
            for ($i = $index - $context; $i <= $index + $context; $i++) {
                $keep[$i] = true;
            }
        }

        $result = [];
        $skipped = false;
        foreach ($script as $index => $line) {
            if (isset($keep[$index])) {
                if ($skipped) {
                    $result[] = ['…', ''];
                    $skipped = false;
                }
                $result[] = $line;
                continue;
            }
            $skipped = true;
        }

        if ($skipped && $result !== []) {
            $result[] = ['…', ''];
        }

        return $result;
    }
}
