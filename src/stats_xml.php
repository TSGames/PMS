<?php
require "static.php"; // connect db
require "functions.php";
if($config_values->visitors_password==$_GET["pw"])
{
$a=array("COUNTER_ONLINE","COUNTER_TODAY","COUNTER_YESTERDAY","COUNTER_OVERALL","COUNTER_24");
$b=array(count_db_exp("visitors_counter","WHERE time>='".(time()-60*$config_values->visitors_lifetime)."'"),$config_values->visitors_today,$config_values->visitors_yesterday,$config_values->visitors+$config_values->visitors_increment,count(get_24_stats(true)));
}
else
{
$a=array("Passwort ungültig!","Einstellungen überprüfen");
$b=array(chr(1),chr(1));
}

$xml='<?xml version="1.0" encoding="UTF-8" ?>

<counter>';
for($i=0;$i<count($a);$i++)
$xml.='<stat><name>'.language($a[$i]).'</name><value>'.$b[$i].'</value></stat>';

$xml.='</counter>';
echo utf8_encode($xml);
?>