<?
session_start();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<meta http-equiv="language" content="de">
<script type="text/javascript">
function do_switch(id)
{
if(document.getElementById(id).style.display=="none")
{
document.getElementById(id).style.display="";
document.getElementById(id+1).style.display="none";
}
else
{
document.getElementById(id).style.display="none";
document.getElementById(id+1).style.display="";
}
}
</script>
<link rel="stylesheet" type="text/css" href="style.css">
<title>PMS - Hilfe- & Referenzcenter</title>
</head>
<body>
<table valign="top" height="90%" width="800px" align="center" class="border"><tr valign="top"><td>
<?

if($_POST["login"])
{
}
$login=$_SESSION["login"];
if($_POST["item"] && $login)
{
if($_POST["id"])
mysql_query("UPDATE item SET name = '".$_POST["name"]."', description = '".$_POST["description"]."', content = '".$_POST["content"]."', html = '".$_POST["html"]."' WHERE id = '".$_POST["id"]."'");
else
mysql_query("INSERT INTO item (name,subcat,description,content,html) VALUES ('".$_POST["name"]."','".$_POST["subcat"]."','".$_POST["description"]."','".$_POST["content"]."','".$_POST["html"]."')");
$_GET["subcat"]=$_POST["subcat"];
}
$_GET["cat"]*=1;
$temp=$_GET["subcat"];
$_GET["subcat"]*=1;
$_GET["item"]*=1;
$link=mysql_query("SELECT id FROM cat WHERE id = ".$_GET["cat"]);
$a=@mysql_fetch_object($link);
$_GET["cat"]=$a->id;
$link=mysql_query("SELECT id FROM subcat WHERE id = ".$_GET["subcat"]);
$a=@mysql_fetch_object($link);
$_GET["subcat"]=$a->id;

if(!$_GET["subcat"] && $temp && $_GET["cat"])
{
$link=mysql_query("SELECT id FROM subcat WHERE cat = ".$_GET["cat"]." AND name LIKE '".mysql_real_escape_string($temp)."' LIMIT 1");
$a=@mysql_fetch_object($link);

$_GET["subcat"]=$a->id;
}

$link=mysql_query("SELECT id FROM item WHERE id = ".$_GET["item"]);
$a=@mysql_fetch_object($link);
$_GET["item"]=$a->id;
function edit_mode($subcat,$edit=0)
{
if($edit) echo heading("Artikel bearbeiten");
else echo heading("Artikel hinzuf�gen");
if($edit)
{
$link=mysql_query("SELECT name,description,content,html FROM item WHERE id = '$edit'");
if($link) $a=mysql_fetch_object($link);
}
else
$a->html=1;

$check=$a->html ? "checked" : "";
echo '<form action="index.php" method="post"><div align="center">
<input type="hidden" name="id" value="'.$edit.'">
<input type="hidden" name="subcat" value="'.$subcat.'">
Name: <input type="text" name="name" value="'.str_replace('"','&quot',$a->name).'"><br><br>
Beschreibung:<br><textarea name="description" rows="2" cols="40">'.$a->description.'</textarea>
<br><br>
Inhalt:<br><textarea name="content" rows="20" cols="90">'.$a->content.'</textarea>
<br>
<input type="checkbox" name="html" value="1"'.$check.'> HTML-Modus
<br><br>
<input type="submit" name="item" value="Speichern">
</form></div>';
}
function tags($tags,$str)
{
$rep[0]="<span class=\"search_mark\">";
$rep[1]="</span>";
foreach($tags as $tag)
{
for($i=0;$i<strlen($str);$i++)
{
$ok=1;
for($j=$i,$k=0;$k<strlen($tag);$j++,$k++)
{
if(strtolower($tag[$k])!=strtolower($str[$j]))
{
$ok=0;
break;
}
}
if($ok)
{
$end=$i+strlen($tag);
$str=substr($str,0,$i).$rep[0].substr($str,$i,strlen($tag)).$rep[1].substr($str,$end);
$i+=strlen($rep[0])+strlen($rep[1])+strlen($tag)-1;
}
}
}
return $str;
}
if(!function_exists('scandir'))
{
function scandir($dir)
{
    if ($dh = opendir($dir)) {
        for ($i=0;($file = readdir($dh)) !== false;$i++) {
            $s[$i]=$file;
        }
        closedir($dh);
    }
	return($s);
}
}
function checked($a)
{
return $a ? " checked" : "";
}
function search()
{
global $_GET;
if(!$_GET["search"])
$_GET["cat_1"]=$_GET["cat_2"]=1;

return "<form action=\"index.php\" method=\"get\">
<input type=\"hidden\" name=\"search\" value=\"1\">
<input type=\"text\" name=\"query\" value=\"".$_GET["query"]."\"> <input type=\"submit\" name=\"search_btn\" value=\"Suchen\"><br>
<input type=\"checkbox\" name=\"cat_1\" value=\"1\"".checked($_GET["cat_1"])."> Frontend 
<input type=\"checkbox\" name=\"cat_2\" value=\"1\"".checked($_GET["cat_2"])."> Backend 
<input type=\"checkbox\" name=\"cat_3\" value=\"1\"".checked($_GET["cat_3"])."> Programmierung / SDK
</form>";
}
function heading($a,$type=1)
{
return "<h".$type.">".$a."</h".$type."><hr size=\"1\" color=\"#770000\">";
}
function make_link($name,$cat=0,$sub=0,$item=0)
{
if(!$name)
{return "";}
$str="<a href=\"index.php?cat=".$cat."&subcat=".$sub."&item=".$item."\">".$name."</a>";
return $str;
}
function content($a,$html)
{
if(!$html)
{
$a=str_replace(array('<','>'),array('&lt;','&gt;'),$a);
}
$new=$a;
$new=str_replace("BackEnd",make_link("BackEnd",2),$new);
$new=str_replace("Programmierung/SDK",make_link("Programmierung/SDK",3),$new);
return str_replace("
","<br>",$new);
}

if($_GET["search"])
{
$m1="<a href=\"index.php?search=".$_GET["search"]."&query=".$_GET["query"]."\">Suche</a>";
}
else
{
if($_GET["item"])$link=mysql_query("SELECT cat.id as c_id,cat.name as c_name,subcat.id as s_id,subcat.name as s_name,item.id,item.name FROM item LEFT JOIN subcat ON (subcat.id=cat.subcat) LEFT JOIN cat ON (cat.id=subcat.cat) WHERE item.id = ".$_GET["item"]);
else if($_GET["subcat"])$link=mysql_query("SELECT cat.id as c_id,cat.name as c_name,subcat.id as s_id,subcat.name as s_name FROM subcat LEFT JOIN cat ON (cat.id=subcat.cat) WHERE subcat.id = ".$_GET["subcat"]);
else if($_GET["cat"])$link=mysql_query("SELECT cat.id as c_id,cat.name as c_name FROM cat WHERE cat.id = ".$_GET["cat"]);
if($link) $a=mysql_fetch_object($link);
$m1=make_link($a->c_name,$a->c_id);
$m2=make_link($a->s_name,$a->c_id,$a->s_id);
$m3=make_link($a->name,$a->c_id,$a->s_id,$_GET["item"]);
}
echo make_link("PMS-Referenz",0,0,0);
if($m1)
{
echo " -&gt; ".$m1;
}
if($m2)
{
echo " -&gt; ".$m2;
}
if($m3)
{
echo " -&gt; ".$m3;
}
if($_GET["search"])
{
echo heading("Suche nach ".$_GET["query"]);
if(!$_GET["query"] || ctype_space($_GET["query"]))
{
echo "Bitte geben Sie einen Suchbegriff ein.";
$overall_found=1;
}
else if(!($_GET["cat_1"]+$_GET["cat_2"]+$_GET["cat_3"]))
{
echo "Bitte geben Sie mindestens einen Suchort an.";
$overall_found=1;
}
else
{
$search=explode(" ",$_GET["query"]);
unset($query);
foreach($search as $s)
{
$s=mysql_real_escape_string($s);
if($query) $query.=" AND ";
$query.="(item.name LIKE '%$s%' OR description LIKE '%$s%' OR content LIKE '%$s%')";
}
if($_GET["cat_1"]) $query2.="cat.id = 1 OR ";
if($_GET["cat_2"]) $query2.="cat.id = 2 OR ";
if($_GET["cat_3"]) $query2.="cat.id = 3 OR ";
$query="(".SubStr($query2,0,-4).") AND (".$query.")";
$link=mysql_query("SELECT item.id,cat.id as c_id,subcat.id as s_id,item.name,cat,subcat.cat,description,content,html,cat.name AS c_name, subcat.name AS s_name FROM item LEFT JOIN subcat ON (subcat.id=item.subcat) LEFT JOIN cat ON (cat.id=subcat.cat) WHERE $query");
echo mysql_error();
$overall_found=0;
while($link && $a=mysql_fetch_object($link))
{
$overall_found=1;
$c1=tags($search,content($a->name,$a->html));
$c2=tags($search,content($a->description,$a->html));
$c3=tags($search,content($a->content,$a->html));
$ca=make_link($a->c_name,$a-c_id,0,0,0);
$sub=make_link($a->s_name,$a->c_id,$a->s_id,0,0);
echo '<a href="javascript:do_switch('.($r*2).')">'.$c1.'</a> ('.$ca." -&gt; ".$sub.")"/*.make_link($c1,$_GET["cat"],$_GET["subcat"],$i)*/;
if($c2)
{
echo '<br>'.$c2;
}
echo '<table cellspacing="6px" style="display:none;" id="'.($r*2).'"><tr><td>'.$c3.'</td></tr></table>';
echo '<table height="14px" id="'.($r*2+1).'"><tr><td></td></tr></table>
';
$r++;
}
}
if(!$overall_found)
{
echo "Auf Ihre Suchanfrage konnten leider keine Ergebnisse geliefert werden. Bitte versuchen Sie, weniger Begriffe zu verwenden.";
}
echo "<br><br><br>".search();
}
elseif($_GET["item"])
{
$a[0]=content($_GET["cat"],$_GET["subcat"],$_GET["item"],0);
$a[1]=content($_GET["cat"],$_GET["subcat"],$_GET["item"],1);
$a[2]=content($_GET["cat"],$_GET["subcat"],$_GET["item"],2);
echo heading($a[0]);
if($a[1])
{
echo heading($a[1],3);
}
echo "<br>".$a[2];
}
elseif($_GET["subcat"])
{
if($login && $_GET["add"]) edit_mode($_GET["subcat"]);
else if($login && $_GET["edit"]) edit_mode($_GET["subcat"],$_GET["edit"]);
else
{
if($login && $_GET["delete"])
mysql_query("DELETE FROM item WHERE id = '".$_GET["delete"]."'");
echo heading("Bitte w�hlen Sie einen Artikel aus")."<br>";
if($login) echo '[<a href="index.php?add=true&subcat='.$_GET["subcat"].'">Hinzuf�gen</a>]<br><br>';
$link=mysql_query("SELECT id,name,description,content,html FROM item WHERE subcat = ".$_GET["subcat"]." ORDER BY name");
for($i=0;$link && $a=mysql_fetch_object($link);$i++)
{
$c1=content($a->name,$a->html);
$c2=content($a->description,$a->html);
$c3=content($a->content,$a->html);
if($login) $edit=' [<a href="index.php?edit='.$a->id.'&subcat='.$_GET["subcat"].'">Bearbeiten</a>] [<a href="index.php?delete='.$a->id.'&subcat='.$_GET["subcat"].'">L�schen</a>]';
echo '<a href="javascript:do_switch('.($i*2).')">'.$c1."</a>".$edit/*.make_link($c1,$_GET["cat"],$_GET["subcat"],$i)*/;
if($c2)
{
echo '<br>'.$c2;
}
echo '<table cellspacing="6px" style="display:none;" id="'.($i*2).'"><tr><td>'.$c3.'</td></tr></table>';
echo '<table height="14px" id="'.($i*2+1).'"><tr><td></td></tr></table>
';
}
}
}
elseif($_GET["cat"])
{
echo heading("Bitte w�hlen Sie eine Unterkategorie aus")."<br>";
$link=mysql_query("SELECT id,name FROM subcat WHERE cat = ".$_GET["cat"]." ORDER BY sort,name");
while($link && $a=mysql_fetch_object($link))
echo '<b>'.make_link($a->name,$_GET["cat"],$a->id,$_GET["item"]).'</b><br><br>
';
}
else
{
echo heading("Bitte w�hlen Sie eine Hauptkategorie aus")."<br>";
$link=mysql_query("SELECT id,name FROM cat ORDER BY sort,name");
while($link && $a=mysql_fetch_object($link))
echo '<b>'.make_link($a->name,$a->id).'</b><br><br>
';
echo "<br><br><br>".search();

}
?>
</td></tr>
<tr><td height="10px" width="100%">
<hr size="1" color="#770000"><div style="color:#666666">
&nbsp;&nbsp;&nbsp;&nbsp;PMS Hile- & Referenzcenter<br>
</td></tr>
<?
if(!$login)
{
echo "<tr><td><form action=\"index.php\" method=\"post\">
Name: <input type=\"text\" name=\"name\"> Passwort: <input type=\"password\" name=\"pass\"> <input type=\"submit\" name=\"login\" value=\"Login\">
</form></td></tr>";
}
?>
</table>
</body>
</html>