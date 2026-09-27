<?
// This file is static which means it can not be updated trough the automatical update engine

// Ohne das hier war die Sitzung eine reine Browser-Sitzungssitzung: weg,
// sobald der Tab/das Fenster schliesst. Bei der installierten Backend-App
// (siehe manifest.php, sw.js) heisst das in der Praxis "nach jedem
// Beenden neu einloggen", weil das Betriebssystem eine im Hintergrund
// liegende App irgendwann beendet. 30 Tage auf Cookie und
// Server-Aufraeumung (gc_maxlifetime) halten die Sitzung so lange am
// Leben wie das laengst vorhandene "Zugangsdaten speichern"-Cookie es
// ohnehin schon tut - nur ohne dass man das Kaestchen anhaken muss.
$pms_session_lifetime = 60 * 60 * 24 * 30;
ini_set('session.gc_maxlifetime', (string)$pms_session_lifetime);
session_set_cookie_params([
    'lifetime' => $pms_session_lifetime,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

if(@$_GET["config_id"]!="") $_SESSION["config_id"]=$_GET["config_id"]*1;
$config="config_".(@$_SESSION["config_id"]).".php";
if(!file_exists($config) || !($_SESSION["config_id"])) $config="config.php";
require_once $config;

function heading($str)
{
return "<center><h2>".$str."</h2></center>
";
}
function get_latest_version()
{
    return null;
}
?>