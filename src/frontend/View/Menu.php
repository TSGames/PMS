<?php

namespace Pms\Frontend\View;

use Pms\Data\Db;
use Pms\Frontend\Http\Routes;
use Pms\Frontend\Http\Target;
use Pms\Support\Html;

/**
 * Das Menue der Website.
 *
 * Der Altbestand kannte zwei Bauweisen. "menu_mode=1" lieferte
 * <div class="menu"><ul><li>, "menu_mode=0" verschachtelte Tabellen mit
 * festen Pixelbreiten. Jedes Stylesheet musste beides koennen, und die
 * Markierung der aktuellen Seite gab es nur in der ersten Form.
 *
 * Hier entsteht in beiden Faellen dieselbe Liste. Der Unterschied liegt nur
 * noch darin, ob Unterpunkte ausklappen (menu_mode) und ob die Punkte
 * untereinander oder nebeneinander stehen (vertical) - das entscheidet
 * seitdem das Stylesheet, nicht das Markup.
 */
final class Menu
{
    /** Menueeintrag zeigt auf Kategorie, Unterkategorie oder Inhalt. */
    private const TYPE_CONTENT = 0;
    /** Menueeintrag zeigt auf eine der eingebauten Aktionen. */
    private const TYPE_PLUGIN = 1;
    /** Menueeintrag zeigt nach aussen. */
    private const TYPE_LINK = 2;
    /** Menueeintrag ist nur eine Zwischenueberschrift. */
    private const TYPE_LABEL = 3;

    /**
     * Ab wie vielen Punkten das Menue auf kleinen Schirmen zugeklappt
     * ausgeliefert wird. Darunter lohnt das Auf- und Zuklappen nicht.
     */
    private const COLLAPSE_FROM = 8;

    public function __construct(
        private readonly Target $target,
        private readonly int $userType = 0,
        private readonly bool $frontpage = false,
    ) {
    }

    /** Das fertige Menue. */
    public function render(): string
    {
        $entries = $this->entries();
        if ($entries === []) {
            return '';
        }

        $items = '';
        foreach ($entries as $entry) {
            $items .= $this->item($entry);
        }

        $list = '<ul class="menu_list">' . $items . '</ul>';

        // Viele Punkte passen auf einem Telefon nicht ueber den Inhalt.
        // <details> braucht dafuer kein Skript und laesst sich mit der
        // Tastatur bedienen; offen sieht es aus wie die einfache Liste.
        if (count($entries) < self::COLLAPSE_FROM) {
            return '<nav class="menu" aria-label="' . Html::e(language('MENU_LABEL')) . '">' . $list . '</nav>';
        }

        return '<nav class="menu menu_collapsible" aria-label="' . Html::e(language('MENU_LABEL')) . '">'
            . '<details class="menu_details">'
            . '<summary class="menu_summary">' . Html::e(language('MENU_LABEL'))
            . '<span class="menu_count">' . count($entries) . '</span></summary>'
            . $list
            . '</details></nav>';
    }

    /**
     * Die sichtbaren Eintraege, so weit die Rechtestufe reicht.
     *
     * @return list<object>
     */
    private function entries(): array
    {
        return Db::select(
            'SELECT * FROM ' . Db::table('menu') . ' WHERE visible = 1 AND usertyp <= ? ORDER BY sort, name',
            [$this->userType]
        );
    }

    /** Ein Menuepunkt mit allem, was daran haengt. */
    private function item(object $entry): string
    {
        $active = $this->isActive($entry);
        $class = $active ? ' class="menu_active"' : '';
        $current = $active ? ' aria-current="page"' : '';

        $inner = match ((int)$entry->typ) {
            self::TYPE_CONTENT => $this->contentLink($entry, $class, $current) . $this->submenu($entry),
            self::TYPE_PLUGIN => $this->pluginLink($entry, $class, $current),
            self::TYPE_LINK => $this->externalLink($entry),
            self::TYPE_LABEL => '<span class="menu_label">' . Html::e((string)$entry->name) . '</span>',
            default => '',
        };

        return $inner === '' ? '' : '<li class="menu_item">' . $inner . '</li>';
    }

    /** Verweis auf Kategorie, Unterkategorie oder Inhalt. */
    private function contentLink(object $entry, string $class, string $current): string
    {
        return '<a href="' . Html::e($this->addressOf($entry)) . '"' . $class . $current . '>'
            . Html::e((string)$entry->name) . '</a>';
    }

    /** Verweis auf eine der eingebauten Aktionen. */
    private function pluginLink(object $entry, string $class, string $current): string
    {
        $plugin = $this->plugin($entry);
        if ($plugin === '') {
            return '';
        }

        // Ein fuehrendes # bedeutet: kein Plugin, sondern ein fester Pfad
        $href = str_starts_with($plugin, '#')
            ? Routes::base() . '/' . substr($plugin, 1)
            : Routes::action($plugin);

        return '<a href="' . Html::e($href) . '"' . $class . $current . '>'
            . Html::e((string)$entry->name) . '</a>';
    }

    /**
     * Verweis nach aussen.
     *
     * Das Feld enthaelt im Altbestand ein rohes Fragment ohne spitze
     * Klammern (a href="..." target="_blank"), das direkt ins Markup ging -
     * war es anders gefuellt, entstand kaputtes HTML. Hier wird die Adresse
     * herausgezogen und ein ordentlicher Verweis daraus gebaut.
     */
    private function externalLink(object $entry): string
    {
        $address = self::addressFrom((string)($entry->extern ?? ''));
        if ($address === '') {
            return '';
        }

        $target = str_contains((string)$entry->extern, '_blank')
            ? ' target="_blank" rel="noopener noreferrer"'
            : '';

        return '<a class="menu_extern" href="' . Html::e($address) . '"' . $target . '>'
            . Html::e((string)$entry->name) . '</a>';
    }

    /**
     * Zieht die Adresse aus dem Feld, gleich in welcher Form sie dort steht:
     * als nackte Adresse, als Attributfragment oder als ganzes Anker-Element.
     */
    public static function addressFrom(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/href\s*=\s*"([^"]*)"/i', $raw, $treffer)
            || preg_match("/href\s*=\s*'([^']*)'/i", $raw, $treffer)) {
            return self::safeAddress(trim($treffer[1]));
        }

        // Kein href: Dann steht dort die Adresse selbst
        return self::safeAddress($raw);
    }

    /**
     * Laesst nur Adressen durch, die im Browser ein Ziel ansteuern.
     *
     * javascript: und data: gehoeren nicht dazu - das Feld fuellt zwar eine
     * Redaktion, aber ein Verweis soll verweisen und nichts ausfuehren.
     */
    private static function safeAddress(string $address): string
    {
        if ($address === '' || str_contains($address, '"') || str_contains($address, '<')) {
            return '';
        }

        $schema = strtolower((string)parse_url($address, PHP_URL_SCHEME));
        if ($schema !== '' && !in_array($schema, ['http', 'https', 'mailto', 'ftp'], true)) {
            return '';
        }

        return $address;
    }

    /** Die ausklappbaren Unterpunkte eines Eintrags. */
    private function submenu(object $entry): string
    {
        if (!$entry->popup || $entry->item) {
            return '';
        }

        $children = $this->children($entry);
        if ($children === []) {
            return '';
        }

        $items = '';
        foreach ($children as $child) {
            $items .= '<li class="menu_item"><a href="' . Html::e($this->childAddress($entry, $child)) . '">'
                . Html::e((string)$child->name) . '</a></li>';
        }

        return '<ul class="menu_sub">' . $items . '</ul>';
    }

    /**
     * Die Unterkategorien einer Kategorie, oder die Inhalte einer
     * Unterkategorie.
     *
     * @return list<object>
     */
    private function children(object $entry): array
    {
        $isSubcat = (int)$entry->subcat > 0;
        $table = $isSubcat ? 'item' : 'subcat';
        $column = $isSubcat ? 'subcat' : 'cat';
        $id = $isSubcat ? (int)$entry->subcat : (int)$entry->cat;

        $where = $column . ' = ?';
        if ($this->userType <= 1) {
            $where = ($isSubcat ? 'visible = 1 AND available = 1 AND ' : 'available = 1 AND ') . $where;
        }

        return Db::select(
            'SELECT id, name FROM ' . Db::table($table) . ' WHERE ' . $where . ' ORDER BY sort, name',
            [$id]
        );
    }

    private function childAddress(object $entry, object $child): string
    {
        if ((int)$entry->subcat > 0) {
            return Routes::item((int)$child->id, (string)$child->name);
        }
        return Routes::subcat((int)$child->id, (string)$child->name);
    }

    /** Adresse eines Eintrags, der auf einen Inhalt zeigt. */
    private function addressOf(object $entry): string
    {
        if ((int)$entry->item > 0) {
            return Routes::item((int)$entry->item, (string)from_db('item', (int)$entry->item, 'name'));
        }
        if ((int)$entry->subcat > 0) {
            return Routes::subcat((int)$entry->subcat, (string)from_db('subcat', (int)$entry->subcat, 'name'));
        }
        if ((int)$entry->cat > 0) {
            return Routes::cat((int)$entry->cat, (string)from_db('cat', (int)$entry->cat, 'name'));
        }
        return Routes::base() . '/';
    }

    /**
     * Zeigt dieser Eintrag auf die Seite, die gerade offen ist?
     *
     * Im Altbestand gab es diese Markierung nur in einem der beiden
     * Menuemodi; im anderen blieb sie ungenutzt.
     */
    private function isActive(object $entry): bool
    {
        return match ((int)$entry->typ) {
            self::TYPE_CONTENT => $this->contentIsActive($entry),
            self::TYPE_PLUGIN => $this->pluginIsActive($entry),
            default => false,
        };
    }

    private function contentIsActive(object $entry): bool
    {
        if ((int)$entry->item > 0) {
            return (int)$entry->item === $this->target->item;
        }
        if ((int)$entry->subcat > 0) {
            return (int)$entry->subcat === $this->target->subcat;
        }
        if ((int)$entry->cat > 0) {
            return (int)$entry->cat === $this->target->cat;
        }
        return false;
    }

    private function pluginIsActive(object $entry): bool
    {
        $plugin = $this->plugin($entry);
        if ($plugin === '') {
            return false;
        }
        if (str_starts_with($plugin, '#')) {
            // Der Punkt "Startseite" ist aktiv, wenn keine Seite gewaehlt ist
            return $plugin === '#index.php' && $this->frontpage;
        }
        return $plugin === $this->target->action;
    }

    /** Die Aktion hinter einem Plugin-Eintrag. */
    private function plugin(object $entry): string
    {
        $table = $GLOBALS['plugin_intern'] ?? [];
        $key = (int)$entry->plugin;
        return isset($table[$key][1]) ? (string)$table[$key][1] : '';
    }
}
