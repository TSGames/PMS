<?
// This file is static which means it can not be updated trough the automatical update engine
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