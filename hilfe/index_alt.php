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
function content($cat,$sub,$item2,$what)
{
global $item;
if(!$item[$cat][$sub][$item2])
{
return "";
}
$a=explode("
",$item[$cat][$sub][$item2]);
$rep=0;
if($a[0]=="[nohtml]")
{
$rep=1;
unset($a[0]);
}
if($what==2)
{
unset($a[$rep],$a[$rep+1]);
$a[2+$rep]=implode("
",$a);
}
if($rep)
{
$a[$what+$rep]=str_replace(array('<','>'),array('&lt;','&gt;'),$a[$what+$rep]);
}
$new=$a[$what+$rep];
$new=str_replace("BackEnd",make_link("BackEnd",2),$new);
$new=str_replace("Programmierung/SDK",make_link("Programmierung/SDK",3),$new);
return str_replace("
","<br>",$new);
}
$cat[1]="FrontEnd / Gestaltung";
$subcat[1][1]="HTML-Template";
$subcat[1][2]="CSS-Styleclasses";
$cat[2]="BackEnd";
$subcat[2][1]="Website-Konfigurator";
$subcat[2][2]="Menü";
$subcat[2][3]="Benutzerverwaltung";
$subcat[2][4]="Kategorien";
$subcat[2][5]="Unterkategorien";
$subcat[2][6]="Inhalte";
$subcat[2][7]="Variablen";
$subcat[2][8]="Umfragen";
$subcat[2][9]="Bans/Sperrungen";
$subcat[2][10]="Ereignisse";
$subcat[2][11]="Backup-Manager";
$subcat[2][12]="Website-Status";
$cat[3]="Programmierung/SDK";
$subcat[3][1]="Einstieg";
$subcat[3][2]="Funktionsreferenz";

$s=@scandir("content");
for($i=0;$i<count($s);$i++)
{
if($s[$i]=="." || $s[$i]=="..")
{
continue;
}
$z1=$s[$i][0]*10+$s[$i][1];
$z2=$s[$i][2]*10+$s[$i][3];
$z3=$s[$i][4];
if($s[$i][5]!=".")
{
$z3=$z3*10+$s[$i][5];
}
$item[$z1][$z2][$z3]=trim(file_get_contents("content/".$s[$i]));
}
$temp=content($_GET["cat"],$_GET["subcat"],$_GET["item"],0);
$m1=make_link($cat[$_GET["cat"]],$_GET["cat"],0,0);
if($_GET["search"])
{
$m1="<a href=\"index.php?search=".$_GET["search"]."&query=".$_GET["query"]."\">Suche</a>";
}
$m2=make_link($subcat[$_GET["cat"]][$_GET["subcat"]],$_GET["cat"],$_GET["subcat"],0);
$m3=make_link($temp,$_GET["cat"],$_GET["subcat"],$_GET["item"]);
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
else
{
$search=explode(" ",$_GET["query"]);
for($i=0;$i<count($search);$i++) {if(!$search[$i]) unset($search[$i]);}
$r=0;
for($i=1;$i<=count($cat);$i++)
{
for($j=1;$j<=count($subcat[$i]);$j++)
{
for($k=1;$k<=count($item[$i][$j]);$k++)
{
$found=1;
for($a=0;$a<count($search);$a++)
{

if(!@stristr($item[$i][$j][$k],$search[$a]))
{
$found=0;
break;
}
}
if($found)
{
$overall_found=1;
$c1=tags($search,content($i,$j,$k,0));
$c2=tags($search,content($i,$j,$k,1));
$c3=tags($search,content($i,$j,$k,2));
$ca=make_link($cat[$i],$i,0,0,0);
$sub=make_link($subcat[$i][$j],$i,$j,0,0);
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
}
}
}
if(!$overall_found)
{
echo "Auf Ihre Suchanfrage konnten leider keine Ergebnisse geliefert werden. Bitte versuchen Sie, weniger Begriffe zu verwenden.";
}
echo "<br><br><br>
<form action=\"index.php\" method=\"get\">
<input type=\"text\" name=\"query\" value=\"".$_GET["query"]."\"> <input type=\"submit\" name=\"search\" value=\"Suchen\">
</form>";
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
echo heading("Bitte wählen Sie einen Artikel aus")."<br>";
for($i=1;$a=$item[$_GET["cat"]][$_GET["subcat"]][$i];$i++)
{
$c1=content($_GET["cat"],$_GET["subcat"],$i,0);
$c2=content($_GET["cat"],$_GET["subcat"],$i,1);
$c3=content($_GET["cat"],$_GET["subcat"],$i,2);
echo '<a href="javascript:do_switch('.($i*2).')">'.$c1.'</a>'/*.make_link($c1,$_GET["cat"],$_GET["subcat"],$i)*/;
if($c2)
{
echo '<br>'.$c2;
}
echo '<table cellspacing="6px" style="display:none;" id="'.($i*2).'"><tr><td>'.$c3.'</td></tr></table>';
echo '<table height="14px" id="'.($i*2+1).'"><tr><td></td></tr></table>
';
}
}
elseif($_GET["cat"])
{
echo heading("Bitte wählen Sie eine Unterkategorie aus")."<br>";
for($i=1;$a=$subcat[$_GET["cat"]][$i];$i++)
{
echo '<b>'.make_link($a,$_GET["cat"],$i,$_GET["item"]).'</b><br><br>
';
}
}
else
{
echo heading("Bitte wählen Sie eine Hauptkategorie aus")."<br>";
for($i=1;$a=$cat[$i];$i++)
{
echo '<b>'.make_link($a,$i,$_GET["subcat"],$_GET["item"]).'</b><br><br>
';
}
echo "<br><br><br>
<form action=\"index.php\" method=\"get\">
<input type=\"text\" name=\"query\" value=\"".$_GET["query"]."\"> <input type=\"submit\" name=\"search\" value=\"Suchen\">
</form>";

}
?>
</td></tr>
<tr><td height="10px" width="100%">
<hr size="1" color="#770000"><div style="color:#666666">
&nbsp;&nbsp;&nbsp;&nbsp;PMS Hile- & Referenzcenter<br>
&nbsp;&nbsp;&nbsp;&nbsp;PMS ist ein Websystem von TSGames (Torsten Simon)<br>
&nbsp;&nbsp;&nbsp;&nbsp;Kopieren dieser Inhalte (auch Auszugsweise) ist nicht gestattet.</div>
</td></tr></table>
</body>
</html>