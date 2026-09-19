<?php
/**
 * Grundgerüst des angemeldeten Backends.
 *
 * @var string $content      Fertiges HTML des Inhaltsbereichs
 * @var string $activeAction Aktuelle Aktion
 * @var array  $navigation   Navigationspunkte, nach Gruppen geordnet
 * @var string $pageLabel    Beschriftung der aktuellen Seite
 * @var array  $modules      Zusätzliche Module
 * @var string $siteName     Name der Website
 * @var string $userName     Angemeldeter Benutzer
 * @var string $messages     Fertig gerendertes HTML der Meldungen
 * @var string $searchTerm   Suchbegriff, wenn gerade gesucht wird
 */

use Pms\Support\Html;
use Pms\Support\Listing;
use Pms\Backend\View\Icons;
use Pms\Backend\View\Layout;

echo Layout::head(Layout::title());
?>
<body>
<button class="sidebar-toggle" id="sidebar-toggle" aria-label="Navigation öffnen" title="Navigation öffnen"><?= Icons::render('menu') ?></button>
<div class="admin-layout">
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-header">
        <span class="sidebar-brand"><?= Html::e($siteName) ?></span>
        <button class="sidebar-close" id="sidebar-close" aria-label="Navigation schließen" title="Navigation schließen"><?= Icons::render('close') ?></button>
    </div>
    <nav class="sidebar-nav" aria-label="Hauptnavigation">
<?php foreach ($navigation as $group): ?>
<?php if ($group['label'] !== ''): ?>
        <div class="nav-section-label"><?= Html::e($group['label']) ?></div>
<?php endif; ?>
        <ul class="nav-list">
<?php foreach ($group['items'] as $entry): ?>
<?php
    $href = $entry['href'] ?? Html::url($entry['action']);
    $target = isset($entry['target']) ? ' target="' . Html::e($entry['target']) . '" rel="noopener"' : '';
    $active = $entry['action'] === $activeAction ? ' active' : '';
?>
            <li class="nav-item<?= $active ?>"><a href="<?= Html::e($href) ?>"<?= $target ?> title="<?= Html::e($entry['info']) ?>"><span class="nav-icon"><?= Icons::render($entry['icon']) ?></span><span class="nav-label"><?= Html::e($entry['label']) ?></span></a></li>
<?php endforeach; ?>
        </ul>
<?php endforeach; ?>
<?php if ($modules !== []): ?>
        <div class="nav-divider"></div>
        <div class="nav-section-label">Module</div>
        <ul class="nav-list">
<?php foreach ($modules as $module): ?>
            <li class="nav-item"><a href="<?= Html::e(Html::asset('admin/modul/' . $module['action'])) ?>"><span class="nav-icon"><?= Icons::render('info') ?></span><span class="nav-label"><?= Html::e($module['label']) ?></span></a></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <button class="theme-toggle" id="theme-toggle" aria-label="Farbschema wechseln" title="Farbschema wechseln">
            <span class="theme-toggle-icon" id="theme-toggle-icon">&#9790;</span>
            <span class="theme-toggle-label" id="theme-toggle-label">Dunkler Modus</span>
        </button>
    </div>
</aside>
<main class="admin-main">
    <header class="app-bar">
        <nav class="breadcrumb" aria-label="Pfad">
            <a href="<?= Html::e(Html::url('home')) ?>">Administration</a>
<?php if ($pageLabel !== '' && $activeAction !== 'home'): ?>
            <span class="breadcrumb-separator" aria-hidden="true"><?= Icons::render('chevron-right', 'icon icon-sm') ?></span>
            <span class="breadcrumb-current"><?= Html::e($pageLabel) ?></span>
<?php endif; ?>
        </nav>
        <span class="app-bar-spacer"></span>
        <form class="app-search" method="get" action="<?= Html::e(Html::url('item')) ?>" role="search">
            <?= Icons::render('search', 'icon icon-sm') ?>
            <label class="visually-hidden" for="app-search-input">Inhalte durchsuchen</label>
            <input type="search" id="app-search-input" name="<?= Listing::SEARCH ?>" value="<?= Html::e($searchTerm) ?>" placeholder="Inhalte durchsuchen" autocomplete="off">
        </form>
        <div class="user-menu" x-data="{ open: false }" @keydown.escape="open = false">
            <button type="button" class="user-chip" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-haspopup="true">
                <span class="user-avatar" aria-hidden="true"><?= Html::e(mb_strtoupper(mb_substr($userName, 0, 1))) ?></span>
                <strong><?= Html::e($userName) ?></strong>
                <?= Icons::render('chevron-down', 'icon icon-sm') ?>
            </button>
            <div class="user-menu-panel" x-show="open" x-cloak @click.outside="open = false">
                <a href="index.php" target="_blank" rel="noopener"><?= Icons::render('globe', 'icon icon-sm') ?> Website ansehen</a>
                <a href="<?= Html::e(Html::url('logout')) ?>"><?= Icons::render('logout', 'icon icon-sm') ?> Abmelden</a>
            </div>
        </div>
    </header>
    <div class="admin-content-inner">
<?= $messages ?>
<?= $content ?>
    </div>
</main>
</div>
<script type="text/javascript" src="js/admin-sidebar.js"></script>
<script type="text/javascript" src="js/admin-linked-select.js"></script>
<script type="text/javascript" src="js/admin-config.js"></script>
<script type="text/javascript" src="js/admin-image-dialog.js"></script>
</body>
</html>
