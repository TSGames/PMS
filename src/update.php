<?php
include_once "static.php";
function copy_update($to,$from,$file_handle=0)
{
global $pms_version;
$status=1;
if(!$file_handle)
{
$file_handle=fopen("update.log","w+");
$close=1;
fwrite($file_handle,"Starting file transfer update to ".get_latest_version()."...");
}
$res=0;
$dir=@opendir($from);
if(!$dir) $dir=@opendir($from."/");
if(!$dir)
{
$a=error_get_last();
fwrite($file_handle,"
Error getting directory list /$to
".$a["message"]);
$res--;
}
$found=0;
while(($file=@readdir($dir))!==false)
{
if($file=="." || $file==".." || $file=="config.php" || $file=="version.txt") continue;
if(strstr($file,"."))
{
$fr=trim($from."/".$file,"/");
$t=trim($to."/".$file,"/");
$t=explode("/",$t);
$special=0;
$module_folder=0;
if(substr($t[count($t)-1],0,10)=="___special")
{
$special=1;
$t[count($t)-1]=substr($t[count($t)-1],10);
}
$f_check=explode("/",trim($from,"/"));
$folder=strtolower($f_check[count($f_check)-1]);
if($folder=="modules") $module_folder=1;
$t=implode("/",$t);
if($special || !$module_folder || file_exists($t))
{
@chmod($t,0777);
fwrite($file_handle,"
Copying $t...");
$ok=@copy($fr,$t);
$res+=$ok;
if(!$ok)
{
$a=error_get_last();
fwrite($file_handle,"Error while copying:
".$a["message"]);
}
else fwrite($file_handle,"ok");
}
else
$res++;
}
else
{
$t=trim($to."/".$file,"/");
fwrite($file_handle,"
Creating directory $t...");
$ok=@mkdir($t);
if($ok || file_exists($t."/"))
{
fwrite($file_handle,"ok");
if(!$ok) fwrite($file_handle," (Already exists)");
}
else
{
$a=error_get_last();
fwrite($file_handle,"Error while creating:
".$a["message"]);
}
@chmod($t,0777);
$r=copy_update($t,$from."/".$file,$file_handle);
$res+=$r[0];
}
$found++;
}
$ok=$file_handle ? 1: 0;
if($ok && $close) fclose($file_handle);
@closedir($dir);
return array($res==$found,$ok);
}
if($admin_center || $_SESSION['pmsglobal'])
{
require_once "functions.php";
$rights=from_db("user",$_SESSION['userid'],"typ")>=3;
if($rights)
{
if($_GET["action"]=="do" && !$pms_update_restricted)
{
$r=copy_update("",$update_ftp);
if($r[0])
echo "Das Update wurde ausgeführt";
else
{
echo "Bei der Ausführung des Updates traten Fehler auf. ";

if($r[1]) echo "Mehr Informationen in der Datei <a href=\"update.log\" target=\"_blank\">update.log</a>";
else echo "Die Datei update.log konnte nicht geschrieben werden! Überprüfen Sie zusätzlich die Schreibrechte.";

echo "<br><br>Hinweis: Häufig liegt das nur an sporadisch auftretenden Zugriffen.<br>Versuchen Sie zunächst, das Update zu wiederholen.<br><br><a href=\"update.php?action=do\">Erneut Versuchen</a>";
}
echo "<br><br><a href=\"admin.php\">Zurück zum Admin-Center</a>";
}
if($modul=="update")
{
if($pms_update_restricted)
echo heading("Update-Modul")."Die Funktionalität wurde in der Konfiguration deaktiviert.";
else
{
$new=get_latest_version();
$new_str=$new ? $new : 'Nicht verfügbar';
echo heading("Updates Suchen")."<table width=\"400px\">
<tr><td>Aktuelle Version:</td><td>$pms_version</td></tr>
<tr><td>Neueste Version</td><td>$new_str</td></tr>
<tr><td style=\"text-align:center;\" colspan=\"2\">[<a href=\"update.php?action=do\">";
if($new>$pms_version)
echo 'Update ausführen</a>]
<br>Hinweis: Nach dem Klicken kann der Vorgang einige Zeit in Anspruch nehmen!';
else if($new==$pms_version)
echo 'Version erneut aktualisieren</a>]
<br>Hinweis: Sie können Ihre aktuelle Version erneut updaten, wenn z.B. das Update beim letzten Versuch nicht funktionierte.';
else
echo 'Update Versuchen</a>]
<br>Hinweis: Der PMS-Updater konnte keine Version vorfinden. Möglicherweise ist der Update-Server momentan nicht erreichbar oder der Zugriff wurde geblockt.<br>Sie können dennoch einen Update Versuch starten, in dem Sie auf den Button klicken.';
echo '</td></tr></table>';
}
}
}
else if($modul=="update")
echo heading("Update-Modul")."Ihre Berechtigungen sind für diesen Vorgang nicht ausreichend";
}
?>