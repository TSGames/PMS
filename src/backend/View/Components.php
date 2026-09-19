<?php

namespace Pms\Backend\View;

use Pms\Backend\Support\Html;
use Pms\Backend\Support\Listing;

/**
 * Wiederkehrende Bausteine der Oberfläche.
 *
 * Alle Übersichten des Backends sind aus denselben Teilen gebaut:
 * Seitenkopf, Werkzeugleiste, Tabelle, Seitenaufteilung. Damit sie sich
 * gleich verhalten und gleich aussehen, stehen sie hier und nicht in den
 * einzelnen Controllern.
 *
 * Jede Methode liefert fertiges HTML und maskiert dabei jeden Wert.
 */
final class Components
{
    /** Kopf einer Seite: Überschrift, Erläuterung und Aktionen rechts. */
    public static function pageHeader(string $title, string $description = '', string $actions = ''): string
    {
        $html = '<div class="page-header"><div class="page-header-text"><h2>' . Html::e($title) . '</h2>';
        if ($description !== '') {
            $html .= '<p class="page-header-description">' . Html::e($description) . '</p>';
        }
        $html .= '</div>';
        if ($actions !== '') {
            $html .= '<div class="page-header-actions">' . $actions . '</div>';
        }
        return $html . '</div>';
    }

    /**
     * Werkzeugleiste über einer Übersicht.
     *
     * Die Filter senden bei Auswahl selbst ab; einen Schalter zum Bestätigen
     * gibt es nicht mehr.
     *
     * @param list<array{name: string, label: string, options: array<string|int, string>, value: string|int}> $filters
     */
    public static function toolbar(
        string $action,
        Listing $list,
        array $filters = [],
        string $placeholder = 'Suchen'
    ): string {
        $target = Html::url($action);

        $html = '<form class="toolbar" method="get" action="' . Html::e($target) . '" role="search" x-data>';

        // Eine gewählte Sortierung überlebt Suche und Filter
        if (!$list->isDefaultOrder()) {
            $html .= Html::hidden(Listing::ORDER, $list->order)
                . Html::hidden(Listing::DIRECTION, $list->direction);
        }

        $html .= '<div class="toolbar-group toolbar-search">'
            . Icons::render('search', 'icon icon-sm')
            . '<label class="visually-hidden" for="listing-search">' . Html::e($placeholder) . '</label>'
            . '<input type="search" id="listing-search" name="' . Listing::SEARCH . '"'
            . ' value="' . Html::e($list->search) . '" placeholder="' . Html::e($placeholder) . '"'
            . ' autocomplete="off">'
            . '</div>';

        foreach ($filters as $filter) {
            $html .= '<div class="toolbar-group">'
                . Icons::render('filter', 'icon icon-sm')
                . '<label class="visually-hidden" for="filter-' . Html::e($filter['name']) . '">'
                . Html::e($filter['label']) . '</label>'
                . Html::select(
                    $filter['name'],
                    $filter['options'],
                    $filter['value'],
                    ['id' => 'filter-' . $filter['name'], '@change' => '$el.form.submit()']
                )
                . '</div>';
        }

        // Ohne JavaScript bleibt die Liste bedienbar
        $html .= '<noscript><button type="submit" class="btn-secondary">Anwenden</button></noscript>';

        if ($list->isFiltered()) {
            $html .= '<a class="btn-ghost" href="' . Html::e($target) . '">'
                . Icons::render('close', 'icon icon-sm') . 'Zurücksetzen</a>';
        }

        $html .= '<span class="toolbar-spacer"></span>'
            . '<span class="toolbar-group toolbar-count">' . self::countLabel($list) . '</span>';

        return $html . '</form>';
    }

    /**
     * Tabelle einer Übersicht.
     *
     * @param list<array{key?: string, label: string, class?: string}> $columns
     *        key setzt die Spalte auf sortierbar, sofern das Listing sie erlaubt
     * @param list<list<string>> $rows Fertiges HTML je Zelle
     */
    public static function table(array $columns, array $rows, string $action, ?Listing $list = null, string $empty = ''): string
    {
        if ($rows === []) {
            return self::emptyState($empty !== '' ? $empty : 'Keine Einträge vorhanden.');
        }

        $html = '<div class="table-wrap"><table class="data-table"><thead><tr>';
        foreach ($columns as $column) {
            $class = isset($column['class']) ? ' class="' . Html::e($column['class']) . '"' : '';
            $html .= '<th' . $class . '>' . self::columnHeader($column, $action, $list) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $cell) {
                $column = $columns[$index] ?? ['label' => ''];
                $class = isset($column['class']) ? ' class="' . Html::e($column['class']) . '"' : '';
                $html .= '<td' . $class . ' data-label="' . Html::e($column['label']) . '">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table></div>';
    }

    /** Seitenaufteilung unter einer Übersicht. Entfällt bei einer Seite. */
    public static function pagination(Listing $list, string $action): string
    {
        if ($list->pages < 2) {
            return '';
        }

        $link = static function (int $page, string $label, bool $current = false) use ($list, $action): string {
            if ($current) {
                return '<span class="current" aria-current="page">' . $label . '</span>';
            }
            $url = Html::url($action, $list->params([Listing::PAGE => $page > 1 ? $page : '']));
            return '<a href="' . Html::e($url) . '">' . $label . '</a>';
        };

        $html = '<nav class="pagination" aria-label="Seiten">'
            . '<span class="pagination-info">Seite ' . $list->page . ' von ' . $list->pages . '</span>'
            . '<span class="pagination-pages">';

        if ($list->page > 1) {
            $html .= $link($list->page - 1, Icons::render('chevron-left', 'icon icon-sm'));
        }
        foreach (self::pageNumbers($list) as $page) {
            $html .= $page === 0
                ? '<span class="pagination-gap">…</span>'
                : $link($page, (string)$page, $page === $list->page);
        }
        if ($list->page < $list->pages) {
            $html .= $link($list->page + 1, Icons::render('chevron-right', 'icon icon-sm'));
        }

        return $html . '</span></nav>';
    }

    /** Hinweis, wenn eine Liste nichts enthält. */
    public static function emptyState(string $title, string $hint = '', string $actions = ''): string
    {
        $html = '<div class="empty-state"><div class="empty-state-title">' . Html::e($title) . '</div>';
        if ($hint !== '') {
            $html .= '<p>' . Html::e($hint) . '</p>';
        }
        if ($actions !== '') {
            $html .= '<div class="page-header-actions" style="justify-content:center">' . $actions . '</div>';
        }
        return $html . '</div>';
    }

    /** Kurzer Status als farbige Markierung. */
    public static function chip(string $label, string $tone = ''): string
    {
        $class = 'chip' . ($tone === '' ? '' : ' chip-' . $tone);
        return '<span class="' . Html::e($class) . '">' . Html::e($label) . '</span>';
    }

    /** Ja/Nein als Markierung statt als Wort. */
    public static function booleanChip(mixed $value, string $yes = 'Ja', string $no = 'Nein'): string
    {
        return $value ? self::chip($yes, 'ok') : self::chip($no);
    }

    /** Eine Aktion der Aktionsspalte. */
    public static function action(string $icon, string $href, string $title, string $tone = ''): string
    {
        $class = 'icon-btn' . ($tone === '' ? '' : ' icon-btn-' . $tone);
        return '<a class="' . Html::e($class) . '" href="' . Html::e($href) . '"'
            . ' title="' . Html::e($title) . '" aria-label="' . Html::e($title) . '">'
            . Icons::render($icon) . '</a>';
    }

    /** Sammelt die Aktionen einer Zeile. */
    public static function actions(string ...$actions): string
    {
        return '<span class="row-actions">' . implode('', $actions) . '</span>';
    }

    /** Ein Schalter, der wie die primäre Aktion aussieht. */
    public static function primary(string $label, string $href, string $icon = 'plus'): string
    {
        return '<a class="btn" href="' . Html::e($href) . '">' . Icons::render($icon, 'icon icon-sm')
            . Html::e($label) . '</a>';
    }

    /** Ein zurückhaltender Schalter. */
    public static function secondary(string $label, string $href, string $icon = ''): string
    {
        return '<a class="btn btn-secondary" href="' . Html::e($href) . '">'
            . ($icon === '' ? '' : Icons::render($icon, 'icon icon-sm')) . Html::e($label) . '</a>';
    }

    /** @param array{key?: string, label: string, class?: string} $column */
    private static function columnHeader(array $column, string $action, ?Listing $list): string
    {
        $label = Html::e($column['label']);
        $key = $column['key'] ?? '';

        if ($key === '' || $list === null || !$list->isSortable($key)) {
            return $label;
        }

        $active = $list->order === $key;
        $direction = ($active && $list->direction === 'asc') ? 'desc' : 'asc';
        $url = Html::url($action, $list->params([
            Listing::ORDER => $key,
            Listing::DIRECTION => $direction,
            Listing::PAGE => '',
        ]));

        $arrow = $active
            ? Icons::render($list->direction === 'asc' ? 'chevron-up' : 'chevron-down', 'icon icon-sm')
            : '';

        return '<a href="' . Html::e($url) . '" title="Nach dieser Spalte sortieren">' . $label . $arrow . '</a>';
    }

    private static function countLabel(Listing $list): string
    {
        if ($list->total === 1) {
            return '1 Eintrag';
        }
        return Html::e(number_format($list->total, 0, ',', '.')) . ' Einträge';
    }

    /**
     * Die anzuzeigenden Seitenzahlen; 0 steht für eine Auslassung.
     *
     * @return list<int>
     */
    private static function pageNumbers(Listing $list): array
    {
        if ($list->pages <= 7) {
            return range(1, $list->pages);
        }

        $pages = [1];
        $from = max(2, $list->page - 1);
        $to = min($list->pages - 1, $list->page + 1);

        if ($from > 2) {
            $pages[] = 0;
        }
        for ($page = $from; $page <= $to; $page++) {
            $pages[] = $page;
        }
        if ($to < $list->pages - 1) {
            $pages[] = 0;
        }
        $pages[] = $list->pages;

        return $pages;
    }
}
