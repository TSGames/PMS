<?php

namespace Pms\Support;

use Pms\Data\Db;

/**
 * Suche, Sortierung und Seitenaufteilung einer Übersicht.
 *
 * Der Altbestand hat jede Liste vollständig geladen und ungefiltert
 * ausgegeben - bei einigen hundert Inhalten war das unbedienbar. Diese Klasse
 * baut die Abfrage stattdessen aus dem, was in der Adresse steht:
 *
 *     $list = Listing::from('cat')
 *         ->searchIn(['name'])
 *         ->sortableBy(['id' => 'id', 'name' => 'name', 'sort' => 'sort'])
 *         ->orderedBy('sort, name')
 *         ->load();
 *
 * Spaltennamen stehen ausschließlich im Code; aus der Adresse kommen nur
 * Schlüssel, die gegen die erlaubte Liste geprüft werden. Werte werden
 * gebunden, nie eingesetzt.
 */
final class Listing
{
    /** Name des Suchfeldes in der Adresse. */
    public const SEARCH = 'q';
    /** Schlüssel der Sortierspalte. */
    public const ORDER = 'order';
    /** Sortierrichtung: asc oder desc. */
    public const DIRECTION = 'dir';
    /** Angezeigte Seite, 1-basiert. */
    public const PAGE = 'page';

    /** @var list<object> Datensätze der aktuellen Seite */
    public array $rows = [];
    /** Gesamtzahl der Treffer über alle Seiten. */
    public int $total = 0;
    /** Angezeigte Seite. */
    public int $page = 1;
    /** Zahl der Seiten insgesamt. */
    public int $pages = 1;

    /** Suchbegriff aus der Adresse. */
    public readonly string $search;
    /** Gewählte Sortierspalte (Schlüssel) oder leer. */
    public readonly string $order;
    /** Sortierrichtung: asc oder desc. */
    public readonly string $direction;

    private string $select = '*';
    private string $alias = '';
    /** @var list<string> Verknüpfungen, fest im Code formuliert */
    private array $joins = [];
    private string $groupBy = '';
    private string $countExpression = 'COUNT(*)';
    /** @var list<string> Spalten, die die Suche durchsucht */
    private array $searchColumns = [];
    /** @var array<string, string> Schlüssel aus der Adresse => Spalte für ORDER BY */
    private array $sortable = [];
    private string $defaultOrder = 'id';
    /** @var list<string> Zusätzliche Bedingungen */
    private array $conditions = [];
    /** @var array<string, scalar|null> Gebundene Werte */
    private array $bindings = [];
    /** @var array<string, string|int> Parameter, die Links erhalten bleiben */
    private array $keep = [];
    private int $limit;

    private function __construct(private readonly string $table)
    {
        $this->search = Request::string(self::SEARCH);
        $this->order = Request::string(self::ORDER);
        $this->direction = Request::string(self::DIRECTION) === 'desc' ? 'desc' : 'asc';
        $this->page = max(1, Request::queryInt(self::PAGE, 1));
        $this->limit = self::pageLimit();
    }

    /** Beginnt eine Übersicht über die angegebene Tabelle (ohne Präfix). */
    public static function from(string $table): self
    {
        return new self($table);
    }

    /** Auszulesende Spalten, wenn nicht alle gebraucht werden. */
    public function select(string $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Kurzname der Tabelle, wenn mit Verknüpfungen gearbeitet wird.
     * Ab dann tragen alle Spaltenangaben diesen Namen als Präfix.
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Eine Verknüpfung, vollständig im Code formuliert - aus der Adresse
     * kommt hier nichts hinein.
     */
    public function join(string $sql): self
    {
        $this->joins[] = $sql;
        return $this;
    }

    /**
     * Gruppierung und der dazu passende Ausdruck zum Zählen. Ohne den
     * zweiten Wert zählt COUNT(*) die Gruppen statt der Datensätze.
     */
    public function groupBy(string $columns, string $countExpression = ''): self
    {
        $this->groupBy = $columns;
        if ($countExpression !== '') {
            $this->countExpression = $countExpression;
        }
        return $this;
    }

    /**
     * Spalten, die der Suchbegriff durchsucht.
     *
     * @param list<string> $columns
     */
    public function searchIn(array $columns): self
    {
        $this->searchColumns = $columns;
        return $this;
    }

    /**
     * Sortierbare Spalten: Schlüssel aus der Adresse => Ausdruck für ORDER BY.
     *
     * @param array<string, string> $columns
     */
    public function sortableBy(array $columns): self
    {
        $this->sortable = $columns;
        return $this;
    }

    /** Reihenfolge, solange der Benutzer keine Spalte gewählt hat. */
    public function orderedBy(string $order): self
    {
        $this->defaultOrder = $order;
        return $this;
    }

    /**
     * Zusätzliche Bedingung, zum Beispiel ein Filter.
     *
     * @param array<string, scalar|null> $bindings
     */
    public function where(string $condition, array $bindings = []): self
    {
        $this->conditions[] = $condition;
        $this->bindings += $bindings;
        return $this;
    }

    /**
     * Parameter, den Sortier-, Such- und Seitenlinks mitnehmen sollen
     * (typischerweise der Wert eines Filters). Eine leere Zeichenkette steht
     * für "nicht gesetzt" und entfällt.
     */
    public function keep(string $key, string|int $value): self
    {
        if ($value !== '') {
            $this->keep[$key] = $value;
        }
        return $this;
    }

    /** Alles auf einer Seite zeigen. */
    public function unpaged(): self
    {
        $this->limit = 0;
        return $this;
    }

    /** Führt die Abfrage aus. */
    public function load(): self
    {
        $where = $this->conditions;
        $bindings = $this->bindings;

        if ($this->search !== '' && $this->searchColumns !== []) {
            $parts = [];
            foreach ($this->searchColumns as $index => $column) {
                $name = 'search' . $index;
                $parts[] = $column . ' LIKE :' . $name;
                $bindings[$name] = '%' . $this->search . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        $from = Db::table($this->table)
            . ($this->alias === '' ? '' : ' ' . $this->alias)
            . ($this->joins === [] ? '' : ' ' . implode(' ', $this->joins));
        $clause = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $group = $this->groupBy === '' ? '' : ' GROUP BY ' . $this->groupBy;

        $this->total = (int)Db::value('SELECT ' . $this->countExpression . ' FROM ' . $from . $clause, $bindings);

        $limit = '';
        if ($this->limit > 0) {
            $this->pages = max(1, (int)ceil($this->total / $this->limit));
            $this->page = min($this->page, $this->pages);
            $limit = ' LIMIT ' . $this->limit . ' OFFSET ' . (($this->page - 1) * $this->limit);
        }

        $this->rows = Db::select(
            'SELECT ' . $this->select . ' FROM ' . $from . $clause . $group
            . ' ORDER BY ' . $this->orderSql() . $limit,
            $bindings
        );

        return $this;
    }

    /** Sortiert der Benutzer gerade selbst, oder gilt die Grundreihenfolge? */
    public function isDefaultOrder(): bool
    {
        return !isset($this->sortable[$this->order]);
    }

    /** Ist die Liste eingeschränkt - durch Suche oder Filter? */
    public function isFiltered(): bool
    {
        return $this->search !== '' || $this->keep !== [];
    }

    /**
     * Die Parameter der aktuellen Ansicht, für Links innerhalb der Liste.
     *
     * @param array<string, string|int> $overrides Werte, die abweichen sollen;
     *        eine leere Zeichenkette entfernt den Parameter
     * @return array<string, string|int>
     */
    public function params(array $overrides = []): array
    {
        $params = $this->keep;
        if ($this->search !== '') {
            $params[self::SEARCH] = $this->search;
        }
        if (!$this->isDefaultOrder()) {
            $params[self::ORDER] = $this->order;
            $params[self::DIRECTION] = $this->direction;
        }
        if ($this->page > 1) {
            $params[self::PAGE] = $this->page;
        }

        foreach ($overrides as $key => $value) {
            if ($value === '') {
                unset($params[$key]);
                continue;
            }
            $params[$key] = $value;
        }

        return $params;
    }

    /** Darf nach dieser Spalte sortiert werden? */
    public function isSortable(string $key): bool
    {
        return isset($this->sortable[$key]);
    }

    private function orderSql(): string
    {
        if (!isset($this->sortable[$this->order])) {
            return $this->defaultOrder;
        }
        return $this->sortable[$this->order] . ' ' . ($this->direction === 'desc' ? 'DESC' : 'ASC');
    }

    /** Einträge je Seite aus der Website-Konfiguration. */
    private static function pageLimit(): int
    {
        $limit = (int)($GLOBALS['config_values']->page_limit ?? 0);
        return $limit > 0 ? $limit : 15;
    }
}
