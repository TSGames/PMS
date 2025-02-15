<?php
function rss_entities(string $a, int $dynamic = 0): string
{
    global $dyn;
    if ($dynamic) {
        for ($i = 0; $i < 2; $i++) {
            $a = replace_dynamic($a, $dyn, !$dyn);
            if (!$dyn) {
                $dyn = $a[1];
                $a = $a[0];
            }
            $a = make_dynamic($a, 0);
            $a = do_check(make_dynamic(trim($a), 0));
        }
    }
    return str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $a);
}

require "functions.php";
ob_start();

$cat = (int)$_GET["cat"];
$subcat = (int)$_GET["subcat"];

if ($_GET["follow"]) {
    $follow = explode("-", $_GET["follow"]);
    $key = substr($follow[count($follow) - 1], -1);
    $id = (int)substr($follow[count($follow) - 1], 0, -1);
    if ($key == "s") $subcat = $id;
    else if ($key == "c") $cat = $id;
}

session_start();
$admin = $_SESSION['pmsglobal'] && from_db("user", $_SESSION['userid'], "typ") >= 2;

if ($subcat) {
    $w = "a.subcat = '$subcat'";
} else if ($cat) {
    $w = "a.cat = '$cat'";
} else {
    $w = "1";
}

$av = $admin ? "" : " AND available = 1 AND visible = 1";

$link = mysql_query("SELECT a.id, a.name, a.description, a.time, b.name AS user FROM ".$pms_db_prefix."item as a LEFT JOIN ".$pms_db_prefix."user as b ON (b.id=a.user) WHERE ".$w.$av." ORDER BY a.time DESC, a.id DESC LIMIT 20");

if ($subcat) {
    $title = from_db("subcat", $subcat, "name") . ' - ';
} else if ($cat) {
    $title = from_db("cat", $cat, "name") . ' - ';
}

$title .= $config_values->name;
$l = $config_values->page;

if ($config_values->speciallinks) {
    if ($subcat) $l .= "/content/" . link_name(from_db("subcat", $subcat, "name")) . "-" . $subcat . "s.html";
    else if ($cat) $l .= "/content/" . link_name(from_db("cat", $cat, "name")) . "-" . $cat . "c.html";
} else {
    if ($subcat) $l .= "/?subcat=" . $subcat;
    else if ($cat) $l .= "/?cat=" . $cat;
}

echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
<channel>
<title>' . rss_entities($title) . '</title>
<link>' . rss_entities($l) . '</link>
<description>' . from_db("subcat", $subcat, "description") . '</description>';

while ($link && $a = mysql_fetch_object($link)) {
    foreach ($a as $k => $v) $a->$k = rss_entities($v, $k == "name" || $k == "description");
    $l = $config_values->speciallinks ? $config_values->page . '/content/' . link_name($a->name) . '-' . $a->id . '.html' : $config_values->page . '/?item=' . $a->id;
    echo '
<item>
<title>' . $a->name . '</title>
<description>' . $a->description . '</description>
<link>' . $l . '</link>
<author>' . $a->user . '</author>
<guid>' . $subcat . "-" . $a->id . '</guid>
<pubDate>' . date("D, d M Y H:i:s", $a->time) . ' GMT</pubDate>
</item>';
}

echo '
</channel>
</rss>';
echo utf8_encode(ob_get_clean());
?>
