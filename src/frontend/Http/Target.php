<?php

namespace Pms\Frontend\Http;

/**
 * Wohin eine Anfrage zielt.
 *
 * Bisher standen diese Angaben als ein Dutzend loser Variablen im Kopf von
 * index.php und wurden von dort quer durch die Datei gelesen und wieder
 * ueberschrieben. Hier stehen sie beieinander und sind unveraenderlich:
 * Wer etwas anderes braucht, erzeugt mit with() ein neues Ziel.
 */
final class Target
{
    public function __construct(
        /** Eine der Aktionen aus Routes::ACTIONS, sonst leer. */
        public readonly string $action = '',
        public readonly int $cat = 0,
        public readonly int $subcat = 0,
        public readonly int $item = 0,
        /** Zusatzkennung der Aktion, z.B. der Benutzer eines Profils. */
        public readonly int $id = 0,
        /** Seitenzahl mehrseitiger Listen, immer mindestens 1. */
        public readonly int $page = 1,
        public readonly string $search = '',
        /** Wurde die Fehlerseite ueber .htaccess angefordert? */
        public readonly bool $notFound = false,
    ) {
    }

    /** Liefert ein neues Ziel mit einzelnen geaenderten Angaben. */
    public function with(
        ?string $action = null,
        ?int $cat = null,
        ?int $subcat = null,
        ?int $item = null,
        ?int $id = null,
        ?int $page = null,
        ?string $search = null,
        ?bool $notFound = null,
    ): self {
        return new self(
            action: $action ?? $this->action,
            cat: $cat ?? $this->cat,
            subcat: $subcat ?? $this->subcat,
            item: $item ?? $this->item,
            id: $id ?? $this->id,
            page: $page ?? $this->page,
            search: $search ?? $this->search,
            notFound: $notFound ?? $this->notFound,
        );
    }

    /**
     * Legt ein zweites Ziel darueber: Alles, was dort gesetzt ist, gewinnt.
     *
     * Damit setzen sich ausdrueckliche Angaben in der Abfragezeichenfolge
     * gegen den sprechenden Pfad durch.
     */
    public function overlay(self $other): self
    {
        return new self(
            action: $other->action !== '' ? $other->action : $this->action,
            cat: $other->cat !== 0 ? $other->cat : $this->cat,
            subcat: $other->subcat !== 0 ? $other->subcat : $this->subcat,
            item: $other->item !== 0 ? $other->item : $this->item,
            id: $other->id !== 0 ? $other->id : $this->id,
            page: $other->page > 1 ? $other->page : $this->page,
            search: $other->search !== '' ? $other->search : $this->search,
            notFound: $other->notFound || $this->notFound,
        );
    }

    /**
     * Die Angaben in der Form, die der Altbestand in $_GET erwartet.
     *
     * Solange index.php seine Werte noch aus $_GET zieht, schreibt der Kernel
     * das Ziel dorthin zurueck. Die Methode entfaellt, sobald alle Bereiche
     * eigene Controller haben.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        $query = [];
        foreach (['action' => $this->action, 'search_query' => $this->search] as $key => $value) {
            if ($value !== '') {
                $query[$key] = $value;
            }
        }
        foreach (['cat' => $this->cat, 'subcat' => $this->subcat, 'item' => $this->item, 'id' => $this->id] as $key => $value) {
            if ($value !== 0) {
                $query[$key] = (string)$value;
            }
        }
        if ($this->page > 1) {
            $query['page'] = (string)$this->page;
        }
        return $query;
    }
}
