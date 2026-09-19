<?php

namespace Pms\Backend\Support;

use Pms\Backend\Data\Db;
use Pms\Backend\View\Icons;

/**
 * Sortierung von Listen über Pfeil-Schalter.
 *
 * Die Einträge tragen eine Sortiernummer. Ein Klick auf einen Pfeil setzt
 * die Nummer des Eintrags knapp vor bzw. hinter seinen Nachbarn.
 */
final class Sorting
{
    /**
     * Führt eine angeforderte Verschiebung aus.
     *
     * @return bool true, wenn sortiert wurde
     */
    public static function handleRequest(string $table): bool
    {
        if (Request::string('sort') === '') {
            return false;
        }
        if (!Csrf::check()) {
            Flash::error('Die Sortierung konnte nicht übernommen werden (ungültiges Sicherheitstoken).');
            return false;
        }

        $id = Request::queryInt('id');
        if ($id <= 0) {
            return false;
        }

        return Db::update($table, $id, ['sort' => Request::queryInt('pos')]);
    }

    /**
     * Sortiernummer mit Pfeilen für eine Zeile.
     *
     * @param object|null $previous Vorheriger Eintrag der Liste
     * @param object      $current  Aktueller Eintrag
     * @param object|null $next     Nächster Eintrag der Liste
     */
    public static function cell(string $action, ?object $previous, object $current, ?object $next): string
    {
        $html = '<span class="sort-cell"><span class="sort-value">' . Html::e((string)(int)$current->sort) . '</span>'
            . '<span class="row-actions">';

        if ($previous !== null) {
            $html .= self::link($action, (int)$current->id, (int)$previous->sort - 1, 'chevron-up', 'Nach oben');
        }
        if ($next !== null) {
            $html .= self::link($action, (int)$current->id, (int)$next->sort + 1, 'chevron-down', 'Nach unten');
        }

        return $html . '</span></span>';
    }

    private static function link(string $action, int $id, int $position, string $icon, string $title): string
    {
        $url = Html::url($action, ['sort' => 'yes', 'pos' => $position, 'id' => $id] + Csrf::queryParam());
        return '<a class="icon-btn icon-btn-sm" href="' . Html::e($url) . '" title="' . Html::e($title) . '"'
            . ' aria-label="' . Html::e($title) . '">' . Icons::render($icon, 'icon icon-sm') . '</a>';
    }
}
