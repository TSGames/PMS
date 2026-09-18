<?php
/**
 * Grundgerüst des angemeldeten Backends.
 *
 * @var string $content      Fertiges HTML des Inhaltsbereichs
 * @var string $activeAction Aktuelle Aktion
 * @var array  $navigation   Navigationspunkte
 * @var array  $modules      Zusätzliche Module
 * @var string $siteName     Name der Website
 * @var string $userName     Angemeldeter Benutzer
 * @var string $messages     Fertig gerendertes HTML der Meldungen
 */

use Pms\Backend\Support\Html;
use Pms\Backend\View\Layout;

echo Layout::head(Layout::title());
?>
<body>
<button class="sidebar-toggle" id="sidebar-toggle" aria-label="Navigation öffnen" title="Navigation öffnen">&#9776;</button>
<div class="admin-layout">
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-header"><?= Html::e($siteName) ?><button class="sidebar-close" id="sidebar-close" aria-label="Navigation schließen" title="Navigation schließen">&#10005;</button></div>
    <div class="sidebar-user">Hallo, <?= Html::e($userName) ?> (<a href="admin.php?action=logout">Logout</a>)</div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Navigation</div>
        <ul class="nav-list">
<?php foreach ($navigation as $entry): ?>
<?php
    $href = $entry['href'] ?? Html::url($entry['action']);
    $target = isset($entry['target']) ? ' target="' . Html::e($entry['target']) . '"' : '';
    $active = $entry['action'] === $activeAction ? ' active' : '';
    $icon = '';
    $iconFile = ($GLOBALS['image_path'] ?? 'images/') . 'admin/' . $entry['action'] . '.png';
    if (file_exists($iconFile)) {
        $icon = '<img src="' . Html::e($iconFile) . '" width="16" height="16" alt="">';
    }
?>
            <li class="nav-item<?= $active ?>"><a href="<?= Html::e($href) ?>"<?= $target ?> title="<?= Html::e($entry['info']) ?>"><span class="nav-icon"><?= $icon ?></span><span class="nav-label"><?= Html::e($entry['label']) ?></span></a></li>
<?php endforeach; ?>
        </ul>
<?php if ($modules !== []): ?>
        <div class="nav-divider"></div>
        <div class="nav-section-label">Module</div>
        <ul class="nav-list">
<?php foreach ($modules as $module): ?>
            <li class="nav-item"><a href="admin.php?modul=<?= Html::e($module['action']) ?>"><span class="nav-icon"></span><span class="nav-label"><?= Html::e($module['label']) ?></span></a></li>
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
<main class="admin-main"><div class="admin-content-inner">
<?= $messages ?>
<?= $content ?>
</div></main>
</div>
<script type="text/javascript" src="js/admin-sidebar.js"></script>
</body>
</html>
