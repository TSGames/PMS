<?
if(@PMS_FRONTEND==1 || @PMS_BACKEND==1)
{
if(@file_exists("install/"))
{
if(!@file_exists("config.php"))
{
header("Location: install/");
die();
}
else
{
die(header_def().'Lï¿½schen Sie den Install-Ordner und aktualisieren Sie dann die Seite.</div></h1></td></tr></table>
</body>
</html>');
}
}
}
function dynamic_string($str,$edit,$id){
if(!$edit) return $str;

return '<style type="text/css">
.edit_var_layer{
border:1px solid #faa;
box-shadow: 2px 2px 6px rgba(0, 0, 0,0.5);
    -webkit-box-shadow:  2px 2px6px rgba(0, 0, 0,0.5);
    -moz-box-shadow:  2px 2px 6px rgba(0, 0, 0,0.5);
transition: all 0.5s ease-in-out;
-webkit-transition: all 0.5s ease-in-out;
-moz-transition: all 0.5s ease-in-out;
background-position:100% 0%;
width:100%;
}
.edit_var_layer_hover, .edit_var_layer:hover{
border:1px solid #800;
width:100%;
background-color:rgba(255,0,0,0.4);
*background-color:#f00;
background-image:url(images/edit.png);
background-position:100% 0%;
background-repeat:no-repeat;
min-height:30px;
cursor:help ;
*cursor:hand;
transition: all 0.5s ease-in-out;
-webkit-transition: all 0.5s ease-in-out;
-moz-transition: all 0.5s ease-in-out;
box-shadow: 2px 2px 6px rgba(0, 0, 0,0.5);
    -webkit-box-shadow:  2px 2px6px rgba(0, 0, 0,0.5);
    -moz-box-shadow:  2px 2px 6px rgba(0, 0, 0,0.5);
}
</style>
<div class="edit_var_layer" onmouseover="this.className=\'edit_var_layer_hover\';" onmouseout="this.className=\'edit_var_layer\'" title="'.language("EDIT_VAR").'" onclick="document.location=\'admin.php?action=var&edit='.$id.'\';">'.$str.'</div>';
}
function get_24_stats($filter=0){
global $pms_db_connection;
$link=$pms_db_connection->query(make_sql("visitors_counter","","time DESC"));
$i=0;
while($link && $a=$pms_db_connection->fetchObject($link))
{
if($filter && (stripos($a->browser,"bot") || stripos($a->browser,"spider") || stripos($a->browser,"crawler") || stripos($a->browser,"monitor"))) continue;
$stats[$i]=$a;
$i++;
}
return $stats;
}
function store_all()
{
global $_GET;
global $_POST;
global $_SESSION;
if($_GET["action"]=="logout") unset($_GET["action"]);
if(is_array($_GET)) foreach($_GET as $k=>$v) $_SESSION["STORE_GET"][$k]=$v;
if(is_array($_POST))foreach($_POST as $k=>$v) $_SESSION["STORE_POST"][$k]=$v;

}
function reload_all($do=0)
{
global $_GET;
global $_POST;
global $_SESSION;
// In fact, it doesn't make sense to return true if there are only GET-based actions, so there is only post
if($do==0) return isset($_SESSION["STORE_POST"]) ? (count($_SESSION["STORE_POST"])) : 0;
if($do==-1)
{
unset($_SESSION["STORE_GET"]);
unset($_SESSION["STORE_POST"]);
return;
}
unset($_GET);unset($_POST);
$_GET=array();
$_POST=array();
if(is_array($_SESSION["STORE_GET"]))foreach($_SESSION["STORE_GET"] as $k=>$v) $_GET[$k]=$v;
if(is_array($_SESSION["STORE_POST"]))foreach($_SESSION["STORE_POST"] as $k=>$v) $_POST[$k]=$v;
unset($_SESSION["STORE_GET"]);
unset($_SESSION["STORE_POST"]);
}
require_once "static.php";
require_once "functions_global.php";
unset($db_server);unset($db_username);unset($db_password);unset($db_databasename);

$pms_version="1.67";

$content_typ[0]="Standard";
$content_typ[1]="News";
$content_typ[2]="Download";
$content_typ[3]="Spezialseite";

$plugin_intern[0][0]="Registrieren";
$plugin_intern[0][1]="register";
$plugin_intern[1][0]="Benutzer-Panel";
$plugin_intern[1][1]="user_panel";
$plugin_intern[2][0]="Ausloggen";
$plugin_intern[2][1]="logout";
$plugin_intern[3][0]="Passwort vergessen";
$plugin_intern[3][1]="password_recover";
$plugin_intern[4][0]="Top Users";
$plugin_intern[4][1]="topuser";
$plugin_intern[5][0]="Gï¿½stebuch";
$plugin_intern[5][1]="guestbook";
$plugin_intern[6][0]="Startseite";
$plugin_intern[6][1]="#index.php";
$plugin_intern[7][0]="Administration";
$plugin_intern[7][1]="#admin.php";
$plugin_intern[8][0]="Sitemap";
$plugin_intern[8][1]="sitemap";

$special_tags[0][0]="[code]";
$special_tags[0][1]="[/code]";
$special_tags[1][0]="[php]";
$special_tags[1][1]="[/php]";

$special_typ[1]="Startseite"; // Beginn with 1 for !$var
$special_typ[2]="Download-Seite";
$special_typ[3]="Gesperrter/Ungï¿½ltiger Content";
$special_typ[4]="Gï¿½stebuch";
$special_typ[5]="IP gebannt";

$valid_actions=array("download","search","register","password_recover","sitemap","logout","guestbook","user","topuser","user_panel","item","register_finish");


$language_folder="dialoges";

$template_lists_folder="template_lists";


function convert_action($typ,$con)
{
global $action_name;
global $action_list;
global $valid_actions;
if($typ==0)
return "Kategorie: ".make_link(from_db("cat",$con,"name"),"",$con,0,0,"",0,"_blank");
if($typ==1)
return "Unterkategorie: ".make_link(from_db("subcat",$con,"name"),"",0,$con,0,"",0,"_blank");
if($typ==2)
return "Inhalt: ".make_link(from_db("item",$con,"name"),"",0,0,$con,"",0,"_blank");
if($typ==3)
{
$name=$valid_actions[$con];
if($name!="user" && $name!="download") $name=make_link($name,"action=".$name,0,0,0,"",0,"_blank");
return "Plugin: ".$name;
}
if($typ==4)
return 'PMS Administration (<a href="admin.php?action='.$action_list[$con].'">'.$action_name[$con].'</a>)';
}
function check_subcatjump($check)
{
global $login;
global $subcat_jump;
global $item;
$subcat_jump=0;
$add=" AND available = '1' AND visible = '1'";
if($login && from_db("user",$_SESSION['userid'],"typ")>=2) unset($add);
if($check && from_db("subcat",$check,"jump") && count_db_exp("item","WHERE subcat = '".$check."'".$add)==1)
{
if(!$item) $item=from_db("item",$check,"id",1,"subcat",$add);
$subcat_jump=1;
}
}
function header_def()
{
return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><link rel="stylesheet" type="text/css" href="admin.css"><title>PMS - Seitenladefehler</title></head><body><table width="100%" height="100%"><tr><td><div align="center"><h1>';
}
if(!file_exists("/var/template/template.html") && (PMS_FRONTEND==1 || PMS_BACKEND==1))
{
die(header_def().'Es wurde kein Template gefunden! (/var/template/template.html fehlt!)<br>Laden Sie ein Template hoch.</div></h1></td></tr></table>
</body>
</html>');
}
$template_content=@file_get_contents("/var/template/template.html");


//ini_setini_set("session.auto_start",1);

if(!$pms_db_connection->valid())
{
$a=trim(file_get_contents("/var/template/template.html"));
$error="<h1>Keine Verbindung zur Datenbank mï¿½glich!</h1>
<h2>Administrative Informationen: ".$pms_db_connection->error()."</h2>";
$a=str_replace(array("#title","#content","#footer"),array("Professional Management System - Datenbank-Fehler",$error,$footer),$a);
$b=get_template();
$a=str_replace($b,"Datenbank-Fehler",$a);
$a=do_check(make_dynamic($a));
die($a);
}
//mysqli_connect("","root","");
//session_start was here before, but since static files it was set to another position

// get config in one var
$link=$pms_db_connection->query(make_sql("config"));
if($link) $config_values=$pms_db_connection->fetchObject($link);
$register_activated=$config_values->register_activated;
$password_recovery_activated=$config_values->password_recovery_activated;
$guestbook_activated=$config_values->guestbook_activated;

$image_path="images/";
$backup_folder="/var/db/backup";
if($_SESSION["config_id"]) $backup_folder.="_".$_SESSION["config_id"];
$backup_folder.="/";
$supported_img=array('jpg','jpeg','gif','png');

// load gb_id
$link=$pms_db_connection->query(make_sql("item","special = 4","id"));
if($link)
{
$a=$pms_db_connection->fetchObject($link);
$gb_item=$a->id;
}
if(($_SESSION['userid'] || $_SESSION['pmsglobal']) && ($_SESSION['loginip']!=$_SERVER["REMOTE_ADDR"] || $_SESSION['website_key']!=$website_key))
{
delete_sessions();
$login=0;
}

// get language file
$l_file=array($config_values->language.".txt","custom.txt");
foreach($l_file as $l)
{
$lang=trim(@file_get_contents($language_folder."/".$l));
$lang=explode(chr(10)."##",$lang);
$i=0;
foreach($lang as $a)
{
$i++;
if($i==1) continue;
$a=explode(chr(10),$a);
$b=trim($a[0]);
unset($a[0]);
$language[$b]=trim(implode(chr(10),$a),"\t\n\r\0");
if(!strstr($b,"MAIL_BODY")) $language[$b]=str_replace(chr(10),"<br>",$language[$b]);
}
}
$user_typ[-1]="Alle";
$user_typ[0]=language("USER_TYP_DEFAULT");
$user_typ[1]=language("USER_TYP_MODERATOR");
$user_typ[2]=language("USER_TYP_ADMIN");
$user_typ[3]=language("USER_TYP_SUPERADMIN");

// end of language

$footer="<div align=\"center\">".str_replace(array("%1","%2"),array($config_values->name,date("Y")),language("FOOTER_COPYRIGHT"))."</div>".$footer;


// delete old bans
$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."bans WHERE time < ".time()." AND time != 0");

// set cookie domain
$cookie_domain=$config_values->page;
if(stristr(substr($cookie_domain,0,7),"://"))
{
$cookie_domain=substr(stristr($cookie_domain,"://"),3);
}
if(stristr(substr($cookie_domain,0,4),"www."))
{
$cookie_domain=substr(stristr($cookie_domain,"www."),4);
}
//echo $cookie_domain;
function get_template()
{
return array("#content","#title","#menu","#user_panel","#poll","#footer","#counter","#birthday","#topuser","#mostdiscussed","#search","#position_row","#latest_comments","#comments_list","#newsletter");
}
function warning_box($str)
{
return '<table class="warning"><tr><td>'.$str.'</td></tr></table>';
}
function copy_reference($what,$id)
{
global $pms_db_reference;
global $pms_db_prefix;
global $pms_db_connection;
$a=$pms_db_connection->query("INSERT INTO ".$pms_db_prefix.$what." SELECT * FROM ".$pms_db_reference.$what." WHERE id = '$id'");
if($a) return 1;
global $error;
$error="Fehler beim Duplizieren<br>Technische Information: ".mysqli_error();
ok_error();
return 0;
}
// Gets the template_lists files
function get_lists($cur,$name="list")
{
global $template_lists_folder;
$dir=@opendir($template_lists_folder);
if($dir)
{
$str="<select name=\"".$name."\"><option value=\"\"";
if(!$cur) $str.=" selected";
$str.=">&lt;Standard&gt;</option>";
for($i=0,$j=0;($a=@readdir($dir))!==FALSE;$i++)
{
if($a=="." || $a==".." || strlen($a)>32) continue;
$c=explode(".",$a);
$c=strtolower($c[count($c)-1]);
if($c!="php" && $c!="php5") continue;
$check="";
if($a==$cur) $check=" selected";
$str.="<option value=\"".$a."\"".$check.">".SubStr($a,0,-strlen($c)-1)."</option>";
$j++;
}
$str.="</select>";
if($j) return $str;
else return "";
@closedir($dir);
}
return "";
}
function select_reference($head,$info,$what,$name="name",$sort="sort",$action="",$what2="")
{
global $pms_db_reference;
global $pms_db_prefix;
global $pms_db_connection;
if(!$action) $action=$what;
$link=$pms_db_connection->query("SELECT id,".$name." FROM ".$pms_db_reference.$what." ORDER BY ".$sort.",".$name);
$link2=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix.$what." ORDER BY ".$sort.",".$name);
$ok=0;
if($link && mysqli_num_rows($link))
{
for($i=0;$link2 && $a=$pms_db_connection->fetchObject($link2);$i++) $ids[$i]=$a->id;
$str.=form("","get")."<input type=\"hidden\" name=\"action\" value=\"".$action."\">
<table><tr><td>".$info."</td><td><select name=\"reference\">";
while($a=$pms_db_connection->fetchObject($link))
{
if(@in_array($a->id,$ids)) continue;
$ok++;
$str.="<option value=\"".$a->id."\">".$a->$name."</option>";
}
$str.="</td></tr><tr><td colspan=\"2\"><div style=\"text-align:center;\"><input type=\"submit\" name=\"create\" value=\"Erzeugen\"></div></td></tr></table></form>";
}
if($what2) $what=$what2;
if(!$ok)
$str="Es ist kein Referenzobjekt angelegt. Legen Sie das Element zuerst im Haupt-PMS an!
<br><br>
[<a href=\"admin.php?action=".$what."\">Zurï¿½ck</a>]";
$str=heading($head)."<br>".$str;
return $str;
}
function load_hidden()
{
global $cat;
global $subcat;
global $item;
global $content_page;
global $action;
global $id;
global $_POST;
$cat=$_POST['cat'];
$subcat=$_POST['subcat'];
$item=$_POST['item'];
$content_page=$_POST['content_page'];
$action=$_POST['action'];
$id=$_POST['id'];
}
function hidden_positions()
{
global $cat;
global $subcat;
global $item;
global $content_page;
global $action;
global $id;
return "<input type=\"hidden\" name=\"cat\" value=\"".$cat."\">
<input type=\"hidden\" name=\"subcat\" value=\"".$subcat."\">
<input type=\"hidden\" name=\"item\" value=\"".$item."\">
<input type=\"hidden\" name=\"content_page\" value=\"".$content_page."\">
<input type=\"hidden\" name=\"action\" value=\"".$action."\">
<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
}
$anti_spam_count=0;
function get_filesize($what)
{
$dir=@opendir($what);
if($dir)
{
while (($file = readdir($dir)) !== false)
{
if($file=="." || $file=="..")
{
continue;
}
$size+=get_filesize($what."/".$file);
}
closedir($dir);
return $size;
}
else
{
return @filesize($what);
}
}
function delete_all($dir)
{
$a=@opendir($dir);
while (($file = @readdir($a)) !== false)
{
if($file=="." || $file=="..")
{
continue;
}
if(!@unlink($dir."/".$file)) delete_all($dir."/".$file);
}
@closedir($a);
return @rmdir($dir);
}


function get_backups()
{
global $backup_folder;
$dir=@opendir(SubStr($backup_folder,0,-1));
$i=0;
if($dir)
{
while (($file = @readdir($dir)) !== false)
{
if($file=="." || $file==".." || strstr($file,"."))
{
continue;
}
$back[$i]=$file;
$i++;
}
@closedir($dir);
}
if($back) {
	@rsort($back);
}
return $back;
}
function get_tinymceinit($match,$height)
{
return 'tinymce.init({
    selector: "#'.$match.'",
    width: 640,
    height: "'.$height.'",
    resize: "both",
    language: "de",
    plugins: "advlist autolink lists link image charmap preview anchor \
              searchreplace visualblocks code fullscreen insertdatetime media \
              table help wordcount",
    
    toolbar: "undo redo | bold italic underline strikethrough | \
              alignleft aligncenter alignright alignjustify | \
              styleselect formatselect fontselect fontsizeselect | \
              bullist numlist outdent indent blockquote | \
              link image media | forecolor backcolor | \
              removeformat code fullscreen",

    content_css: "template_files/style.css",
    body_class: "content_table",

    // Externe Listen für Links/Medien/Templates (falls genutzt)
    template_external_list_url: "lists/template_list.js",
    external_link_list_url: "lists/link_list.js",
    external_image_list_url: "lists/image_list.js",
    media_external_list_url: "lists/media_list.js",

    // Platzhalter-Werte für Templates
    template_replace_values: {
        username: "Some User",
        staffid: "991234"
    }
});';
}
function get_tinymce($match="content",$init=1,$height=300)
{
$str='
<!-- TinyMCE -->
<script type="text/javascript" src="tinymce/tinymce.js"></script>
<script type="text/javascript">
';
if($init) $str.=get_tinymceinit($match,$height);
$str.='
</script>
<!-- /TinyMCE -->';
return $str;
}
function edit_out($string,$name,$class,$edit_mode,$mode=0)
{
if(!$edit_mode) return $string;
if($mode==2) $string=cleanup_content($string);
//if($mode) $string=my_stripslashes($string);
$add='rows="4" cols="50"';
if($mode==2) $add='rows="20" width="100%"';
if(!$mode) $str.='<input type="text" name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" value="'.str_replace('"',"&quot;",$string).'">';
else $str.='<textarea name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" '.$add.'>'.str_replace('&','&amp;',$string).'</textarea>';
return $str;
}
function cleanup_content($content)
{
$content=explode('<img id="##pms_replace_image_temp"',$content);
if(count($content)>1)
{
$c_new=$content[0];
for($i=1;$i<count($content);$i++)
{
$add=explode(">",$content[$i]);
unset($add[0]);
$add=implode(">",$add);
$c_new.=$add;
}
}
else
$c_new=$content[0];
return $c_new;
}
function recover_item($id="",$mode=0,$ids="")
{
global $pms_db_prefix;
global $backup_folder;
$back=get_backups();
if(!$back) return 0;
$i=0;
foreach($back as $b)
{
$a=utf8_decode(trim(@file_get_contents($backup_folder.$b."/Database.sql")));
$a=explode(chr(10),$a);
foreach($a as $line)
{
if(substr($line,0,12)!="INSERT INTO ") continue;
$c=explode(" (",$line);
if(substr($c[0],-4)!="item") continue;
if(!$mode && !strstr($line,"VALUES ('$id',")) continue;
unset($c[0]);
$line=implode(" (",$c);
$line="INSERT INTO ".$pms_db_prefix."item (".$line;
$date=explode("_",$b);
if(count($date)<5) $date="Nicht Auslesbar"; else $date=$date[2].".".$date[1].".".$date[0]." - ".$date[3].":".$date[4];
$temp=explode(" ('",$line);
$temp=explode("','",$temp[1]);
if($mode && @in_array($temp[0],$ids)) continue;
$found[$i][0]=$date;
$found[$i][1]=$line;
$found[$i][2]=$temp[0]; //id
$found[$i][3]=$temp[3];
$i++;
}
}
if(!$i) return 0;
return $found;
}
function copy_recrusive($from,$to,&$results)
{
@mkdir($to);
if(substr($from,-1)!="/") $from.="/";
if(substr($to,-1)!="/") $to.="/";
$dir=@opendir($from);
if($dir)
{
while (($file = @readdir($dir)) !== false)
{
if($file=="." || $file=="..")
{
continue;
}
$copy_from=$from.$file;
$copy_to=$to.$file;
$dir2=@opendir($copy_from);
if($dir2)
{
copy_recrusive($copy_from,$copy_to,$results);
@closedir($dir2);
}
else
{
$results[0]+=copy($copy_from,$copy_to);
$results[1]++;
}
}
@closedir($dir);
}
}
function do_export()
{
ini_set("max_execution_time","3600");
global $pms_db_prefix;
global $backup_folder;
global $pms_db_connection;
$sys_tables=array('bans','cat','comments','config','dynamic','item','menu','poll','subcat','user');
for($i=0;$i<count($sys_tables);$i++)
{
$sys_tables[$i]=$pms_db_prefix.$sys_tables[$i];
}
global $pms_version;
ini_set("max_execution_time",6000); // Export does need a lot of time
umask(0);
@mkdir(SubStr($backup_folder,0,-1));
$time=date("Y")."_".date("m")."_".date("d")."_".date("H")."_".date("i")."_".rand(10000,99999);
$exp="-- MYSQL Dump erzeugt von PMS, Version ".$pms_version."
-- Hinweis: Dies ist ein sehr einfacher MYSQL-Export. Wenn Sie einen besseren Export benï¿½tigen, nutzen Sie bitte den phpMyAdmin
-- Bitte seien Sie sich bewusst, dass wir keinerlei Garantie fï¿½r den erfolgreichen Export/Import zwischen verschiedenen PMS-Versionen geben kï¿½nnen
-- Wï¿½hlen Sie diesen Export wï¿½hrend des Installationsvorgangs aus, um ihn wiederherzustellen
-- www.TSGames.de?item=236 fï¿½r weitere Informationen ï¿½ber PMS

-- Nur fï¿½r phpMyAdmin-Import:
-- Entfernen Sie die Kommentare vor den Folgenden Zeilen, um alle alten Daten vor dem Einlesen zu lï¿½schen.
";

$result = $pms_db_connection->fetchAllObject($pms_db_connection->list_tables());
foreach($result as $r)
{
	$tablename = $r->name;
	if(!in_array($tablename,$sys_tables))
	{
	continue; // we don't need to export the visitors
	}
	$exp.="-- TRUNCATE TABLE ".$tablename.";
	";
}
$exp.=chr(10);
foreach($result as $r)
{
$table=$r->name;
if(!in_array($table,$sys_tables))
{
continue; // we don't need to export the visitors
}
$exp=$exp."-- Daten fï¿½r Tabelle ".$table.chr(10);
$link=$pms_db_connection->query("SELECT * FROM ".$table);
if($link)
{
unset($field_name);
$end=$link->numColumns();
for($j=0;$j<$end;$j++)
{
$field_name[$j] = $link->columnName($j);
}
$dump = $pms_db_connection->fetchAllObject($link);
foreach($dump as $d)
{
$exp.="INSERT INTO ".$table." (";
for($k=0;$k<count($field_name);$k++)
{
$exp.=$field_name[$k];
if($k!=count($field_name)-1)
{
$exp.=",";
}
}
$exp.=") VALUES (";
for($k=0;$k<count($field_name);$k++)
{
$exp=$exp."'".str_replace(array(chr(10),chr(13),"'"),array('\n','\r',"''"),$d->{$field_name[$k]})."'";
if($k!=count($field_name)-1)
{
$exp.=",";
}
}
$exp.=");
";
}
}
$exp.=chr(10);
}
@mkdir($backup_folder.$time);
$file=fopen($backup_folder.$time."/Database.sql","w+");
fwrite($file,utf8_encode($exp));
fclose($file);
@mkdir($backup_folder.$time."/images");
unset($a);
$a[0]="images/subcat";
$a[1]="images/item";
$a[2]="images/user";
$a[3]="images/uploads";
$a[4]="template_files";
$result=array(0,0);
for($i=0;$i<count($a);$i++)
{
copy_recrusive($a[$i],$backup_folder.$time."/".$a[$i],$result);
}
$result[0]+=copy("/var/template/template.html",$backup_folder.$time."/template.html");
$result[1]++;
return $result;
}
function comp_spam($a,$session,$count)
{
if(strlen($session)<1) return 0;
session_write_close();
session_id($session);
session_start();
$spam=$_SESSION["spam".($count*1)];
return $a==$spam && strlen($spam)==6;
}
function random_type($length){
	for($i=0;$i<$length;$i++){
	$x=rand(0,9);
	if($x>=10) $x=chr($x+87);
	$str.=$x;
}
return $str;
}
function make_antispam($short=0)
{
global $anti_spam_count;
$anti_spam_count++;
$rand=random_type(6);
$_SESSION["spam".$anti_spam_count]=$rand;
if($short) return "<img src=\"image.php?id=".session_id()."&count=$anti_spam_count\" border=\"0\" alt=\"Code\"><input type=\"hidden\" name=\"session\" value=\"".session_id()."\"><input type=\"hidden\" name=\"spamcount\" value=\"$anti_spam_count\"> <input type=\"text\" name=\"spam\" maxlength=\"6\" size=\"6\">";
return "
<script type=\"text/javascript\">
function toggle(id)
{
if(document.getElementById(id).style.display==\"none\")
{
document.getElementById(id).style.display=\"\";
}
else
{
document.getElementById(id).style.display=\"none\";
}
}
</script>
<tr><td>".str_replace(array("%1","%2"),array("<img src=\"image.php?id=".session_id()."&count=$anti_spam_count\" border=\"0\" alt=\"Code\">","<a href=\"javascript:toggle(".$anti_spam_count.")\">".language("SPAM_HELP")."</a>"),language("SPAM"))."
<input type=\"hidden\" name=\"session\" value=\"".session_id()."\"><input type=\"hidden\" name=\"spamcount\" value=\"$anti_spam_count\"></td><td><input type=\"text\" name=\"spam\" maxlength=\"6\" size=\"6\"></td></tr>
<tr id=\"".$anti_spam_count."\" style=\"display: none\"><td>
".language("SPAM_LEFT")."</td><td>
".language("SPAM_RIGHT")."
</td></tr>";
}
function remove_html($str)
{
return str_replace(array('&lt;','&gt;','&nbsp;','<br>'),array('<','>',' ','
'),$str);
}
function get_rss($auto=1,$c=0,$s=0)
{
if($auto)
{
global $cat;global $subcat;
$c=$cat;
$s=$subcat;
}
global $config_values;
if($config_values->speciallinks)
{
$str=$config_values->page.'/rss/';
if($s) return $str.link_name(from_db("subcat",$s,"name")).'-'.$s.'s.rss';
if($c) return $str.link_name(from_db("cat",$c,"name")).'-'.$c.'c.rss';
return $str.link_name($config_values->name).'.rss';
}
if($s) return 'rss.php?subcat='.$s;
if($c) return 'rss.php?cat='.$c;
return 'rss.php';
}
function make_dynamic($str,$smiley=1)
{
global $special_tags;
global $cat;
global $subcat;
global $item;
global $content_page;
global $action;
global $id;
global $number_visitors;
global $pms_db_connection;
for($j_zaehler=0;$j_zaehler<count($special_tags);$j_zaehler++)
{
$str2=explode($special_tags[$j_zaehler][0],$str);
if($smiley) $str=smileys($str2[0]);
for($i_zaehler=1;$i_zaehler<count($str2);$i_zaehler++)
{
$str3=explode($special_tags[$j_zaehler][1],$str2[$i_zaehler]);
if($j_zaehler==0)
{
$str.="<table width=\"100%\"><tr><td width=\"100%\" class=\"code\">".$str3[0]."</td></tr></table>";
}
if($j_zaehler==1)
{
	try {
		eval('ob_start();' .remove_html($str3[0]).'$str.=ob_get_clean();');
	}catch(ParseError|Exception $e) {
		$str.=print_r($e, true);
	}
}
if($smiley) $str.=smileys($str3[1]);
}
}
return $str;
}
function make_dynamicold($str)
{
global $special_tags;
for($j_zaehler=0;$j_zaehler<count($special_tags);$j_zaehler++)
{
$str2=explode($special_tags[$j_zaehler],$str);
$str=smileys($str2[0]);
for($i_zaehler=1;$i_zaehler<count($str2);$i_zaehler++)
{
if($i_zaehler%2==1)
{
if($j_zaehler==0)
{
$str.="<table width=\"100%\"><tr><td width=\"100%\" div class=\"code\">".$str2[$i_zaehler]."</td></tr></table>";
}
if($j_zaehler==1)
{
eval('ob_start();'.str_replace(array("<br>","<br />","<br/>"),"",$str2[$i_zaehler]).'$str.=ob_get_clean();');
}
}
else
{
$str.=smileys($str2[$i_zaehler]);
}
}
}
return $str;
}
function my_stripslashes($str)
{
$str=stripslashes($str);
$str=str_replace(array("&lt;","&gt;"),array("&amp;lt;","&amp;gt;"),$str);
return $str;
}
function delete_sessions()
{
global $cookie_domain;
unset($_SESSION['userid']);
unset($_SESSION['usertyp']);
unset($_SESSION['pmsglobal']);
unset($_SESSION['loginip']);
unset($_SESSION['last_login']);
unset($_SESSION['update_notice']);
@setcookie("login_pw","",time()-3600,"/",$cookie_domain);
$_COOKIE["login_pw"]="";
}
function time_diff($time)
{
$diff=time()-$time;
$sec=$diff%60;
$min=intval($diff/60)%60;
$hour=intval($diff/3600);
if($sec!=1) $s="n";
if($min!=1) $m="n";
if($hour!=1) $h="n";
if($hour) $str=$hour." Stunde".$h.", ";
if($min || $hour) $str.=$min." Minute".$m." und ";
$str.=$sec." Sekunde".$s;
return $str;
}
function browser($a)
{
return $a;
if(strstr($a,"Firefox"))
{
$b=explode("Firefox/",$a);
return "Firefox ".$b[1];
}
}
function make_sql($table,$exp="",$order="id",$field="*",$group="",$add_where=1,$join="")
{
global $pms_db_prefix;
if($join) $join=" ".$join;
if($exp)
{
if($add_where) $exp=" WHERE ".$exp;
else $exp=" ".$exp;
}
if($order) $order=" ORDER BY ".$order;
if($group) $group=" GROUP BY ".$group;
return "Select ".$field." FROM ".$pms_db_prefix.$table.$join.$exp.$group.$order.";";
}
function compare($a,$b)
{
if($a[0]==$b[0])
{
return 0;
}
return $a[0] < $b[0]? 1: -1;
}
function config_space($str="")
{
if($str=="") return "</table><br></td></tr>";
return "<tr><td colspan=\"2\"><span style=\"color:#A0A0A0;font-weight:bold;\">".$str."</span></td></tr><tr><td colspan=\"2\"><table width=\"100%\" style=\"border:1px solid #A0A0A0;\">";
}
function small_comment($str)
{
global $config_values;
$max_length=$config_values->latest_comments_chars;
$diff=intval($max_length/10);
$words=explode(" ",$str);
$length=0;
if(strlen($str)<=$max_length) return($str);
for($i=0;$i<count($words);$i++)
{
$w_length=strlen($words[$i]);
if($length+$w_length>$max_length)
{
if($length<$max_length-$diff)
$str_new.=SubStr($words[$i],0,($max_length-$length))." ";
break;
}
$length+=$w_length+1;
$str_new.=$words[$i]." ";
}
return SubStr($str_new,0,-1)."...";
}
function clear_comment($str)
{
$search=array('<','>','&lt;br&gt;');
$replace=array('&lt;','&gt;','<br>');
return str_replace($search,$replace,$str);
}
function make_age($age)
{
//$a=intval(date(Y,time()-$age+86400));
//if($a<0) $a=(70+$a)+67;
$a=date("Y")-date("Y",$age);
if(date("m",$age)>date("m") || date("m",$age)==date("m") && date("d",$age)>date("d")) $a--;
return $a;
}
function make_date($time,$long,$am=0,$bypass=0)
{

$check1=intval($time/86400);
$check2=intval(time()/86400);
if(!$bypass && ($check1==$check2 || $check1==$check2-1 || $check1==$check2-2))
{
if($check1==$check2)
$format=language("DAY_TODAY");
elseif($check1==$check2-1)
$format=language("DAY_ONE_AGO");
elseif($check1==$check2-2)
$format=language("DAY_TWO_AGO");

if(strstr($format,"%1"))
$str=str_replace("%1",make_date($time,$long,$am,1),$format);
else
$str=$format;
$str.=",";
if($long)
{
$str.=" ".language("TIME_AT");
}
$str.=" ".date(language("TIME_FORMAT"),$time);
if($long)
{
$str.=" ".language("TIME_ADD");
}
return $str;
}
if($long)
{
$str=language("DATE_AT")." ".get_weekday($time).", ".language("DATE_ADD")." ";
}
$a=language("DATE_AT")." ";
if(!$am)
{
unset($a);
}
$str.=$a.date(language("DATE_FORMAT"),$time);
if(!$bypass) $str.=", ".date(language("TIME_FORMAT"),$time);
if($long && !$bypass) $str.=" ".language("TIME_ADD");
return $str;
}
function get_errorpage()
{
global $pms_db_prefix;
global $pms_db_connection;
$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE special = '3' LIMIT 1;");
if($link)
{
$a=$pms_db_connection->fetchObject($link);
$item=$a->id;
}
if(!$item) // No error? Get Frontpage
{
$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE special = '1' LIMIT 1;");
if($link)
{
$a=$pms_db_connection->fetchObject($link);
$item=$a->id;
}
}
return $item;
}
function make_mail($mail,$text="",$force_safe=0)
{
global $config_values;
if($config_values->safemail || $force_safe)
{
$a=strlen($mail);
$time=(date(Y)%6-date(m)*2)*2;
if($time<0) $time*=-1;
for($i=0;$i<strlen($mail);$i++)
{
$str.=(ord($mail[$i])+$time);
if($i!=strlen($mail)-1)
{
$str.="_";
}
}
$str2=language("MAIL_SEND");
if($text) $str2=$text;
return "<a href=\"mail.php?mail=$str\">".$str2."</a>";
}
else
{
return "<a href=\"mailto:".$mail."\">".$mail."</a>";
}
}
function language($str)
{
global $language;
if($language[$str]) return $language[$str];
return $str;
}
function name_condition($n)
{
$invalid=array('[',']','(',')',"'",'"','{','}');
foreach($invalid as $i)
{
if(strpos($n,$i)!==FALSE) return 0;
}
return 1;
}
function make_user($id,$name,$password,$passwordr,$mail,$website,$typ,$image,$image2,$image_delete,$bday,$top,$active,$sendmail,$signatur = "",$showmail = 1)
{
global $pms_db_prefix;
global $config_values;
global $pms_db_connection;
$register=time();
$registerip=$_SERVER["REMOTE_ADDR"];
$ok=1;
if(!$id || $password)
{
if($password!=$passwordr)
{
return language("USER_ERROR_PW_COMPARE");
}
}
if(strlen($name)<3)
{
return language("USER_ERROR_SHORT_USERNAME");
}
if(!name_condition($name))
{
return language("USER_ERROR_CHAR_USERNAME");
}
if($ok)
{
$link=$pms_db_connection->query(make_sql("user","","id"));
if($link)
{
while($a=$pms_db_connection->fetchObject($link))
{
if(strtolower($name)==strtolower($a->name) && (!$id || $id!=$a->id))
{
return language("USER_ERROR_USER_EXISTS");
}
}
}
}
if($bday)
{
$bday=explode(".",$bday);
if($bday[0] && $bday[1] && $bday[2])
{
$bday=@mktime(0,0,0,$bday[1],$bday[0],$bday[2]);
}
if($bday>time()-60*60*24*365 || $bday<time()-60*60*24*365*200)
{
return language("USER_ERROR_INVALID_BIRTH");
}
}
if(!$id || $password)
{
if(strlen($password)<3 && $ok)
{
return language("USER_ERROR_SHORT_PW");
}
}
if(!check_mail($mail) && $ok)
{
return language("USER_ERROR_INVALID_MAIL");
}
if($ok==1)
{
$img="";
$name=$pms_db_connection->escape($name);
$mail=$pms_db_connection->escape($mail);
$website=$pms_db_connection->escape($website);
$signatur=$pms_db_connection->escape($signatur);
$dbay=$pms_db_connection->escape($bday);

if($id && $image_delete)
{
del_contentimg("user",$id,from_db("user",$id,"image"));
$img="image = 0,";
}
if($id && $password)
{
$password=", password = '".md5($password)."'";
}
$do="INSERT INTO ".$pms_db_prefix."user (name,password,mail,showmail,website,signatur,typ,register,registerip,bday,top,active) VALUES ('$name','".md5($password)."','$mail','$showmail','$website','$signatur','$typ','$register','$registerip','$bday','$top','$active');";
if($id)
{
if($typ<1 && from_db("user",$id,"typ")>=1) // reset mails
{
global $confirmation_dialogs;
foreach($confirmation_dialogs as $f)
$add.=", ".$f[1]." = '0'";

}
$do="UPDATE ".$pms_db_prefix."user SET name = '$name'".$password.", mail = '$mail', showmail = '$showmail',website = '$website', signatur = '$signatur', typ = '$typ',".$img."bday = '$bday', top = '$top', active = '$active'".$add." WHERE id = '$id' LIMIT 1;";
}
if($pms_db_connection->query($do))
{
if($image)
{
$end=substr($image,-3);
global $supported_img;
if(in_array(strtolower($end),$supported_img))
{
if(!$id)
{
$link=$pms_db_connection->query(make_sql("user","","id"));
while($link && $a=$pms_db_connection->fetchObject($link))
{
$id=$a->id;
}
}
global $image_path;
global $pms_db_prefix;
$target=$image_path."user/".$id.".".$end;
@copy($image2,$target);
create_img($target,64,64);
$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET image = '$end' WHERE id = '$id' LIMIT 1;");
}
}
if($sendmail)
{
if(!$id)
{
$link=$pms_db_connection->query(make_sql("user","","id"));
while($link && $d=$pms_db_connection->fetchObject($link))
{
$id=$d->id;
}
}
$key=from_db("user",$id,"register");
$c=$config_values->page;
$subject=str_replace("%1",$config_values->name,language("REGISTER_MAIL_SUBJECT"));
$body=str_replace(array("%1","%2","%3"),array($name,$c,$c."/index.php?action=register_finish&id=".$id."&key=".$key),language("REGISTER_MAIL_BODY"));

my_mail($mail,$subject,$body);
$link=$pms_db_connection->query(make_sql("user","typ >= '1' AND mail_register = '1'"));
while($link && $a=$pms_db_connection->fetchObject($link))
my_mail($a->mail,language("REGISTER_ADMIN_MAIL_SUBJECT"),str_replace(array("%1","%2","%3"),array($a->name,$config_values->name,$name),language("REGISTER_ADMIN_MAIL_BODY")));

}
$a[0]=language("USER_SAVE_SUCCESS");
$a[1]=1;
return $a;
}
else
{
$a[0]=language("USER_SAVE_ERROR");
$a[1]=0;
return $a;
}
}
}

function mail_spam($s)
{
return preg_replace("/(\n+|\r+|%0A|%0D)/i", '', $s);
}
function my_mail($to,$subject,$body)
{
global $config_values;
$to=mail_spam($to); // spam removing
return mail($to,$subject,$body."

".$config_values->page."
".str_replace("%1",date("Y"),language("MAIL_BODY_FOOTER")),"FROM: ".$config_values->name." <".$config_values->mail.">");
}
function ban_time($time)
{
if($time>0)
{
return ceil(($time-time())/60/60/24);
}
else
{
return language("BAN_UNLIMITED");
}
}
$str.=make_imgalt("rate".$b.".gif",0,$alt,"template_files/","rate");
function make_imgalt($str,$border,$alt,$folder="",$class="",$title="")
{
global $image_path;
if(!$folder)
{
$folder=$image_path;
}
if($title) $title=" title=\"".$title."\"";
$border=" border=\"".$border."\"";
$size=@getimagesize($folder.$str);
if($class) $class=" class=\"".$class."\"";
if($size)
{
$size=" width=\"".$size[0]."px\" height=\"".$size[1]."px\"";
}
return "<img src=\"".$folder.$str."\"".$border.$size.$title." alt=\"".$alt."\"".$class.">";
}
function make_img($str,$border)
{
return make_imgalt($str,$border,"");
}
function do_login($name,$password,$min_rights,$do_md5=1)
{
global $pms_db_prefix;
global $pms_db_connection;
$link=$pms_db_connection->query(make_sql("user","name LIKE '".$pms_db_connection->escape($name)."'","id"));
if($do_md5)$password=md5($password);
if($link && $a=$pms_db_connection->fetchObject($link))
{
if($password!=$a->password)
{
return 2;
}
if($a->active==0)
{
return 3;
}
if($min_rights>0 && $min_rights>$a->typ)
{
return 4;
}
$login2=time();
$id=$a->id;
$_SESSION['last_login']=from_db("user",$id,"login");

$_SESSION['pmsglobal']=1;
$_SESSION['userid']=$a->id;
$_SESSION['usertyp']=$a->typ;
global $website_key;
global $login;
$_SESSION['loginip']=$_SERVER["REMOTE_ADDR"];
$_SESSION['website_key']=$website_key;
$login=1;

$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET login = '$login2' WHERE id = '$id' LIMIT 1;");
$str[0]=$a->name;
$str[1]=$a->password;
$str[2]=$a->typ;
$str[3]=$a->id;
return $str;
}
return 1;
}
function size($byte)
{
$a[0]="Byte";
$a[1]="KB";
$a[2]="MB";
$a[3]="GB";
for($i=0;$byte>1024;$i++) $byte/=1024;
return round($byte,2)." ".$a[$i];
}
function make_contentimg($what,$id,$typ,$size=0,$width=-1,$height=-1) // 0 = thumb, 1 = default
{
if(!$id)
{
return "";
}
global $image_path;
$file=$what."/".$id;
if($size==1)
{
$full=$image_path.$file."_full.".$typ;
$file=$file."_large";
$alt=" alt=\"".str_replace('"',"&quot;",from_db($what,$id,"name"))."\"";
if(file_exists($full)) $link_full=1;
}
$file=$file.".".$typ;
$str="<img class=\"small_image\"".$alt." src=\"".$image_path;
if(file_exists($image_path.$file))
{
$size=@getimagesize($image_path.$file);
if($size)
$size=" width=\"".$size[0]."px\" height=\"".$size[1]."px\"";

$str.=$file;
}
else
{
return ""; // No picture also looks well at all?!
$str.="noimage.gif";
}
$str.="\"".$size." border=\"0\">";
if($link_full) $str="<a href=\"".$full."\" class=\"full_image\">".$str."</a>";
return $str;
}
function make_check($str)
{
if(!$str)
{
return "";
}
return " checked";
}
function del_contentimg($what,$id,$typ,$all=1)
{
global $image_path;
$file=$image_path.$what."/".$id.".".$typ;
$file2=$image_path.$what."/".$id."_large.".$typ;
$file3=$image_path.$what."/".$id."_full.".$typ;
if(file_exists($file) && $all)
unlink($file);

if(file_exists($file2))
unlink($file2);

if(file_exists($file3))
unlink($file3);
}
function back_button()
{
return "[<a href=\"javascript:history.back()\">Zurï¿½ck</a>]";
}
function check_mail($mail)
{
if(strlen($mail)<8  || !stristr($mail,"@") || !stristr($mail,"."))
{
return 0;
}
return 1;
}
function form($on_submit="",$method="post",$add="")
{
if($add) $add="?".$add;
if($on_submit) $on_submit=' onSubmit="'.$on_submit.'"';
return '<form action="'.$_SERVER["PHP_SELF"].$add.'"'.$on_submit.' name="pms_form" method="'.$method.'" enctype="multipart/form-data" accept-charset="ISO-8859-1">';
}
function count_db_exp($table,$exp)
{
global $pms_db_connection;
global $pms_db_prefix;
if($link=$pms_db_connection->query("SELECT COUNT(id) FROM ".$pms_db_prefix.$table." ".$exp.";"))
{
$link=$pms_db_connection->fetch($link);
return $link[0];
}
return(0);
}
function sum_db($table,$where,$exp="")
{
global $pms_db_connection;
global $pms_db_prefix;
if($link=$pms_db_connection->query("SELECT SUM(".$where.") FROM ".$pms_db_prefix.$table." ".$exp))
{
$link=$pms_db_connection->fetch($link);
if(!$link[0])
{
$link[0]=0;
}
return $link[0];
}
return 0;
}
function count_db($table)
{
return count_db_exp($table,"");
}

function ok_error()
{
global $ok;
global $error;
if(!$ok && !$error) return "";
$class="info_ok";
$str=$ok;
if($error)
{
$class="info_error";
$str=$error;
}
echo '<table class="'.$class.'"><tr><td>'.$str.'</td></tr></table><br>';
}

function array_table($a,$b)
{
for($i=0;$i<($a ? count($a) : 0);$i++)
{
$color="";
if($i%2==0)
{
$color=" bgcolor=\"#FFFFFF\"";
}
$str.="<tr".$color.">";
for($j=0;$j<=$b;$j++)
{
$str.="<td>".$a[$i][$j]."</td>";
}
$str.="</tr>
";
}
$str.="</table>";
return $str;
}
function def($str)
{
return str_replace("
","<br>",$str);
}
function table_header_style($str,$style)
{
$str=explode("|",$str);
if($style)
{
$style=" class=\"".$style."\"";
$style1="<div".$style.">";
$style2="</div>";
}
$a="<tr".$style.">";
for($i=0;$i<count($str);$i++)
{
$b=explode(":",$str[$i]);
$a=$a."<td width=\"".$b[1]."\">".$style1.$b[0].$style2."</td>";
}
$a=$a."</tr>";
return $a;
}
function table_header($str)
{
return table_header_style($str,"");
}
function link_name($str,$ignore="")
{
$not=array();
if($ignore)
{
for($i=0;$i<strlen($ignore);$i++) $not[$i]=SubStr($ignore,$i,1);
}
for($i=0;$i<256;$i++)
{
if(in_array(chr($i),$not)) continue;
$array[0][$i]=chr($i);
if($i<48) $array[1][$i]="_";
else if($i<58) $array[1][$i]=chr($i);
else if($i<65) $array[1][$i]="_";
else if($i<91) $array[1][$i]=chr($i);
else if($i<97) $array[1][$i]="_";
else if($i<123) $array[1][$i]=chr($i);
else $array[1][$i]="_";
}
$a=str_replace(array("ï¿½","ï¿½","ï¿½","ï¿½","ï¿½","ï¿½"),array("Ae","Oe","Ue","ae","oe","ue"),$str);
$a=str_replace($array[0],$array[1],$a);
for($i=10;$i>1;$i--)
{
$str="";
for($j=0;$j<$i;$j++)$str.="_";
$l[$i]=$str;
}
$a=str_replace($l,"_",$a);
return(trim($a,"_"));
}
function make_link_mark($name,$add,$cat,$subcat,$item,$mark="",$class="",$id=0,$target="",$close=1)
{
global $config_values;
$special=$config_values->speciallinks;
$site=$config_values->page."/";
if($class)
$class=" class=\"".$class."\"";

if($target)
$target=" target=\"".$target."\"";

$start="<a".$class.$target." href=\"";
$a="index.php";
$b=0;
if($add)
{
$b=1;
$a=$a."?".$add;
if($special)
{
$temp=explode("&",$add);
if($temp[0]=="action=user") 
{
$temp2=explode("=",$temp[1]);
$a=$site."content/".link_name(from_db("user",$temp2[1],"name"))."-".$temp2[1]."u".".html";
}
elseif(substr($temp[0],0,6)=="action" && substr($temp[0],-6)!="logout")
{
$add2=$temp;
unset($add2[0]);
$add2=implode("&",$add2);
if($add2) $add2="?".$add2;
$a=$site."action/".substr($temp[0],7).".html".$add2;
}
else $end=$add;
}

}
if($item)
{
if($b==0)
{
$a=$a."?";
}
else
{
$a=$a."&";
}
$b=1;
$a=$a."item=".$item;
if($special) $a=$site."content/".link_name(from_db("item",$item,"name"))."-".$item.".html";

}
else if($subcat)
{
if($b==0)
{
$a=$a."?";
}
else
{
$a=$a."&";
}
$b=1;
$a=$a."subcat=".$subcat;
if($special) $a=$site."content/".link_name(from_db("subcat",$subcat,"name"))."-".$subcat."s".".html";
}
else if($cat)
{
if($b==0)
{
$a=$a."?";
}
else
{
$a=$a."&";
}
$b=1;
$a=$a."cat=".$cat;
if($special) $a=$site."content/".link_name(from_db("cat",$cat,"name"))."-".$cat."c".".html";
}
if($special) $b=0;
if($id)
{
if($b==0)
{
$a=$a."?";
}
else
{
$a=$a."&";
}
$b=1;
$a=$a."id=".$id;
}
if($mark)
$mark="#".$mark;
if($end)
{
if(!$b) $end="?".$end;
else $end="&".$end;
}
$a=$a.$end.$mark."\">".$name;
if($close) $a.="</a>";
return $start.$a;
}
function make_link($name,$add,$cat=0,$subcat=0,$item=0,$class="",$id=0,$target="")
{
return make_link_mark($name,$add,$cat,$subcat,$item,"",$class,$id,$target);
}
function get_size($img,$width,$height,$what)
{
if(!is_array($img))
{
if(!$size=@getimagesize($img))
{
return 0;
}
}
else $size=$img;
// scale to full-size
if($width>$size[0])
{
$width=$size[0];
}
if($height>$size[1])
{
$height=$size[1];
}
// end
if($size[0]>=$size[1])
{
$s=$size[1]/$size[0];
$height=$s*$width;
}
else
{
$s=$size[0]/$size[1];
$width=$s*$height;
}

if($what==1)
{
return $width;
}
if($what==2)
{
return $height;
}
}

function create_img($img,$width1,$height1,$constrains=1,$filter=0)
{
global $config_values;
$quali=$config_values->picquali;
$size=@getimagesize($img);
if($width1==-1) $width1=$size[0];
if($height1==-1) $height1=$size[1];
if($constrains)
{
$width=get_size($img,$width1,$height1,1);
$height=get_size($img,$width1,$height1,2);
}
else
{
$width=$width1;
$height=$height1;
}
$typ=explode(".",$img);
$typ=strtolower($typ[count($typ)-1]);
global $supported_img;
$supported=$supported_img;
if($size[0]==$width && $size[1]==$height && !$filter)
{
return 0;
}
@ini_set("memory_limit","500M"); // To use more memory
$img_new=imagecreatetruecolor($width,$height);
if($typ=="jpg" || $typ=="jpeg")
{
$img_org=imagecreatefromjpeg($img);
$typ="jpg";
}
if($typ=="gif")
{
$img_org=imagecreatefromgif($img);
}
if($typ=="png")
{
$img_org=imagecreatefrompng($img);
}

imagecopyresampled($img_new,$img_org,0,0,0,0,$width,$height,$size[0],$size[1]);
if($filter)
{
if($filter==1) imagefilter($img_new,IMG_FILTER_GRAYSCALE);
else if($filter==2) imagefilter($img_new,IMG_FILTER_NEGATE);
else if($filter==3) imagefilter($img_new,IMG_FILTER_MEAN_REMOVAL);
else if($filter==4) imagefilter($img_new,IMG_FILTER_EMBOSS);
}
if($typ=="jpg"  || $typ=="jpeg")
{
imagejpeg($img_new,$img,$quali);
}
if($typ=="gif")
{
imagegif($img_new,$img);
}
if($typ=="png")
{
$result=imagepng($img_new,$img, -1);
}
imagedestroy($img_new);
imagedestroy($img_org);
return 1;
}
function get_comment_str($a)
{

if($a==0)
$b=language("COMMENTS_SHOW_NONE");
else if($a==1)
$b=language("COMMENTS_SHOW_ONE");
else if($a>1)
$b=str_replace("%1",$a,language("COMMENTS_SHOW_MULTI"));
return $b;
}
function get_comments($id)
{
global $config_values;
if($config_values->comments && from_db("item",$id,"comments"))
{
$a=count_db_exp("comments","WHERE item = '$id'");
$b=get_comment_str($a);

return make_link_mark($b,"comments=all","","",$id,"comments");
}
return "";
}
function make_contentsmall($what,$id,$img=1)
{
global $pms_db_prefix;
global $config_values;
global $pms_db_connection;
$sel_what="*";
if($what=="item" && $config_values->commentssmall && $config_values->comments)
{
$select=" LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.item=".$pms_db_prefix."item.id)";
$sel_what=$pms_db_prefix."item.*,COUNT(".$pms_db_prefix."comments.id) AS comments_num ";
$sel_group=$pms_db_prefix."item.id";
}
$link=$pms_db_connection->query(make_sql($what.$select,$pms_db_prefix.$what.".id = '$id'",$pms_db_prefix.$what.".id LIMIT 1",$sel_what,$sel_group));
if($link)
{
$a=$pms_db_connection->fetchObject($link);
if(!$a->id)
{
return "";
}
$link1="cat";
if($what=="subcat")
{
$link2="id";
$link3=0;
$rate=make_rating($id,$what);
if($rate)
{
$rate="<tr><td>".$rate;
}
}
if($what=="item")
{
$link2="subcat";
$link3="id";
$rate=make_rating($id,"item",$a->numratings,$a->rating);
if($rate)
{
$rate="<tr><td>".$rate;
}
if($config_values->commentssmall && $config_values->comments)
{
$comments=make_link_mark(get_comment_str($a->comments_num),"comments=all","","",$id,"comments","num_comments");
if($comments && $rate)
{
$rate.=" (".$comments.")";
}
elseif($comments)
{
$rate="<tr><td>(".$comments.")";
}
}
}
if($rate)
{
$rate.="</td></tr>";
}
$str="<table><tr>";
if($img)
{
$li=make_contentimg($what,$a->id,$a->image,0);
if($li) $li=make_link($li,"",$a->$link1,$a->$link2,$a->$link3);
$str.="<td rowspan=\"3\" height=\"64px\" width=\"78px\" style=\"text-align:center;\">".$li."
</td>";
}
$str.="<td class=\"item_link\">".make_link($a->name,"",$a->$link1,$a->$link2,$a->$link3)
."</td></tr>".$rate."<tr valign=\"top\"><td class=\"description\">
".make_dynamic(def($a->description))."</td></tr></table>
";
}
return $str;
}
function user_out($rang,$id,$all,$linkit=0,$sig=0,$head=0,$points="")
{
global $config_values;
global $pms_db_connection;
global $pms_db_prefix;
if($config_values->topusers && $points=="")
{
$points_add=1;
$link2=$pms_db_connection->query("SELECT COUNT(id)*60 FROM ".$pms_db_prefix."comments WHERE user = '$id';");
if($link2 && $b=$pms_db_connection->fetch($link2))
{
$points=$b[0];
}
}
$link=$pms_db_connection->query(make_sql("user","id = '$id'","id"));
if($link)
{
$a=$pms_db_connection->fetchObject($link);
if(!$a->id)
{
return "";
}
if($rang)
{
$rang=str_replace("%1",$rang,language("TOP_USER_RANK"))." ";
}
if(strtolower(substr($a->website,0,7))=="http://")
{
$a->website=substr($a->website,7);
}
$web="<a href=\"http://".$a->website."\" target=\"_blank\">"./*$a->website*/language("USER_VIEW_VISIT_WEBSITE")."</a>";
if($all)
{
$web="<a href=\"http://".$a->website."\" target=\"_blank\">".$a->website."</a>";
}
if($points_add) $points+=$a->points;
$str="<table";
if($all)
{
$str.=" width=\"100%\"";
}
$str.="><tr><td rowspan=\"3\"";
if($linkit)
{
$a->name=make_link($a->name,"action=user&id=".$id,"","","");
}
if(!$all)
{
$str.=" style=\"text-align:center;\"><b>".$rang.$a->name."</b><br>".str_replace("%1",$points,language("TOP_USER_POINTS"))."<br>";
}
else
{
$str.=" width=\"78px\">";
}
$str.="
".make_contentimg("user",$a->id,$a->image,0)."<br>";
if(!$all && $a->website)
{
$str.=$web;
}
$str.="</td>";
if($all)
{
$h1="<b>";
$h2="</b>";
if($head)
{
$h1="<div class=\"user_heading\">";
$h2="</div>";
}
$str.="<td>".$h1.$rang.$a->name.$h2."</td></tr><tr valign=\"top\"><td class=\"description\">
<table width=\"100%\">";
if($config_values->topusers)
$str.="<tr><td width=\"120px\">".language("USER_VIEW_POINTS")."</td><td>".$points."</td></tr>";

$str.="<tr><td>".language("USER_VIEW_REGISTERED")."</td><td>".make_date($a->register,0)."</td></tr>
<tr><td>".language("USER_VIEW_LAST_LOGIN")."</td><td>".make_date($a->login,0)."</td></tr>";
if($a->bday)
{
$str.="<tr><td>".language("USER_VIEW_BIRTH")."</td><td>".date(language("DATE_FORMAT"),$a->bday)." (".make_age($a->bday).")</td></tr>";
}
global $user_typ;
$str.="
<tr><td>".language("USER_VIEW_TYP")."</td><td>".$user_typ[$a->typ]."</td></tr>";
if($a->showmail)
{
$str.="
<tr><td>".language("USER_VIEW_MAIL")."</td><td>".make_mail($a->mail)."</a></td></tr>";
}
if($a->website)
{
$str.="<tr><td>".language("USER_VIEW_WEBSITE")."</td><td>".$web."</td></tr>";
}
if($sig && $a->signatur)
{
$str.="<tr><td>".language("USER_VIEW_SIGNATUR")."</td><td>".def($a->signatur)."</td></tr>";
}
$str.="</tr></table>
</td>";
}
$str.="</tr></table>
";
}
return $str;
}
function make_rating($item,$what="item",$num=0,$rating=0,$force=0)
{
global $pms_db_prefix;
global $config_values;
global $pms_db_connection;
if(!$force)
{
if(!$config_values->rate || (!from_db("item",$item,"rate") && $what=="item") || ($what=="subcat" && count_db_exp("item","subcat = '$item' AND rate = '1'")<0))
{
return "";
}
}
if($what=="item")
{
if($num) $a=$num;
else $a=from_db("item",$item,"numratings");
if($a)
{
if($rating) $rated=$rating;
else $rated=from_db("item",$item,"rating");
$rated/=$a;
}
else
{
$rated=0;
}
}
if($what=="subcat")
{
$link=$pms_db_connection->query("SELECT SUM(numratings) FROM ".$pms_db_prefix."item WHERE subcat = '$item';");
if($link)
{
$a=$pms_db_connection->fetch($link);
$a=$a[0];
}
if($a)
{
$link=$pms_db_connection->query("SELECT SUM(rating) FROM ".$pms_db_prefix."item WHERE subcat = '$item';");
if($link)
{
$b=$pms_db_connection->fetch($link);
$b=$b[0];
}
$rated=$b/$a;
}
}
$str="";
$alt="Bewertung: ".round($rated,1);
for($i=1;$i<6;$i++)
{
$b="0";
if($rated>=$i-0.75)
{
$b="05";
}
if($rated>=$i-0.25)
{
$b="1";
}
$str.=make_imgalt("rate".$b.".gif",0,$alt,"template_files/","rate",$alt);
}
return $str;
}
function smileys($str)
{
if(!from_db("config",1,"smileys")) return $str;
$s[0][0]="<input ";
$s[0][1]=">";
$s[1][0]="<textarea";
$s[1][1]="</textarea>";
$smiley_search=array(
':)',':-)'
,':D',':-D',':d',':-D'
,';)',';-)',
':(',':-(',
':\'(',
':|',':-|',
':P',':-P',':p',':-p');
$smiley_replace=array(
make_img("smileys/1.gif",0),make_img("smileys/1.gif",0),
make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),
make_img("smileys/3.gif",0),make_img("smileys/3.gif",0),
make_img("smileys/4.gif",0),make_img("smileys/4.gif",0),
make_img("smileys/5.gif",0),
make_img("smileys/6.gif",0),make_img("smileys/6.gif",0),
make_img("smileys/7.gif",0),make_img("smileys/7.gif",0),make_img("smileys/7.gif",0),make_img("smileys/7.gif",0));
if(!strstr($str,$s[0][0]) && !strstr($str,$s[1][0]))
{
return str_replace($smiley_search,$smiley_replace,$str);
}

for($j=0;$j<count($s);$j++)
{
$str2=explode($s[$j][0],$str);
//$str2[0]=str_replace($smiley_search,$smiley_replace,$str2[0]);
for($i=0;$i<count($str2);$i++)
{
$str3=explode($s[$j][1],$str2[$i]);
for($k=1;$k<count($str3);$k++)
{
if(strstr($str3[$k],$s[0][0]) || strstr($str3[$k],$s[1][0]))
{
$k++;
continue;
}
$str3[$k]=str_replace($smiley_search,$smiley_replace,$str3[$k]);
}
$str2[$i]=implode($s[$j][1],$str3);
}
$str=implode($s[$j][0],$str2);
}
return $str;
}

function user_points($id,$points)
{
global $pms_db_prefix;
global $pms_db_connection;
if($id)
{
return $pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET points=points+$points WHERE id = '$id' LIMIT 1");
}
}
function make_limits_search($who,$exp)
{
global $cat;
global $subcat;
global $item;
global $content_page;
global $page_limit;
global $start_page;
global $action;
global $search_query;
global $pms_db_prefix;
global $pms_db_connection;

$link=$pms_db_connection->query("SELECT COUNT(id) FROM ".$pms_db_prefix.$who." WHERE ".$exp.";");
if($link && $a=$pms_db_connection->fetch($link))
{
if($a[0]<=$page_limit)
{
return "";
}
$count=$a[0]/$page_limit;
if($count-intval($count)!=0)
{
$count=intval($count)+1;
}
$add=" ...";
if($content_page>$count)
{
$content_page=$count;
$start_page=($content_page-1)*$page_limit;
}
$s=$content_page-5;
$e=$content_page+5;
if($e>=$count)
{
$e=$count;
$add="";
}
if($s<1)
{
$s=1;
}
for($i=$s;$i<=$e;$i++)
{
if($i!=$s)
{
$str.=" | ";
}
else
{
$str.="Seite: ";
if($i>1)
{
$str.="... ";
}
}
if($i==$content_page)
{
$str.=$i;
}
else
{
$str2="";
if($action=="search")
{
$str2="action=".$action."&query=".$search_query."&";
}
$str2=$str2."page=";
$str.=make_link($i,$str2.$i,$cat,$subcat,$item);
}
}
$str.=$add;
return $str;
}
}
function content_mostdiscussed($id)
{
$str="<table><tr><td><center>".make_link(from_db("item",$id,"name"),"","","",$id)."</td>
</tr><tr><td><center>".make_link(make_contentimg("item",$id,from_db("item",$id,"image"),0),"","","",$id)."</td></tr>
<tr><td><center>";
$c=get_comments($id);
if($c)
{
$str.="(".$c.")";
}
return $str."</center></td></tr></table>";
}
function make_limits($who,$who2,$id)
{
$av="available = 1 AND ";
if($who=="item")
{
$av=$av."visible = 1 AND ";
}
if(from_db("user",$_SESSION['userid'],"typ")>2)
{
unset($av);
}
return make_limits_search($who,$av.$who2." = '$id'");
}
function replace_download($str,$id,$file,$link)
{

$search=array("#id","#file","#button");
$replace=array($id,$file,"<div class=\"download_button\"><a href=\"".$link."\">".language("DOWNLOAD_BUTTON")."</a></div>");
//<form action=\"".$link."\" method=\"post\"><input type=\"submit\" value=\"Download!\">
return str_replace($search,$replace,$str);
}
function replace_dynamic($content,$dyn=0,$return=0)
{
global $item_allowed_edit;
global $pms_db_connection;
if(!$dyn)
{
unset($dyn);
$link=$pms_db_connection->query(make_sql("dynamic","","id"));
$ok=0;
for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
{
$dyn[$i]=$a;
$ok=1;
}
}
if($dyn && count($dyn))
{
foreach($dyn as $a)
{
$search=$a->searcher;
$replace=$a->replacer;
if($a->makebr)
{
$search=def($search);
$replace=dynamic_string(def($replace),$item_allowed_edit,$a->id);
}
$content=str_replace($search,$replace,$content);
}
}
if($return) return array($content,$dyn);
return $content;
}
function do_check($content)
{
$do[0]="item";
$do[1]="subcat";
$do[2]="user";
$do[3]="item_link";
$do[4]="subcat_link";
$do[5]="cat_link";
$do[6]="user_link";
for($i=0;$i<count($do);$i++)
{
$str=explode("#".$do[$i].":",$content);
if(count($str)>1)
{
for($j=1;$j<count($str);$j++)
{
$space="<";
$a=explode("<",$str[$j]);
$b=explode(" ",$str[$j]);
if(strlen($a[0])>strlen($b[0]))
{
$space=" ";
$a=$b;
}
$b=explode(".",$str[$j]);
if(strlen($a[0])>strlen($b[0]))
{
$space=".";
$a=$b;
}
$b=explode(",",$str[$j]);
if(strlen($a[0])>strlen($b[0]))
{
$space=",";
$a=$b;
}
if($i!=2 && $i<3)
{
$a[0]=make_contentsmall($do[$i],$a[0]);
}
elseif($i<3)
{
$a[0]=user_out("",$a[0],1);
}
elseif($i==3)
{
$a[0]=make_link(from_db("item",$a[0],"name"),"","","",$a[0]);
}
elseif($i==4)
{
$a[0]=make_link(from_db("subcat",$a[0],"name"),"","",$a[0],"");
}
elseif($i==5)
{
$a[0]=make_link(from_db("cat",$a[0],"name"),"",$a[0],"","");
}
else
{
$a[0]=make_link(from_db("user",$a[0],"name"),"action=user&id=".$a[0],"","","");
}
$str[$j]=implode($space,$a);
}
$content=implode("",$str);
}
}
return $content;
}
function get_weekday($time)
{

$week_count=date("w",$time);
if($week_count==0)
{
return language("WEEKDAY_SUNDAY");
}
if($week_count==1)
{
return language("WEEKDAY_MONDAY");
}
if($week_count==2)
{
return language("WEEKDAY_TUESDAY");
}
if($week_count==3)
{
return language("WEEKDAY_WEDNESDAY");
}
if($week_count==4)
{
return language("WEEKDAY_THURSDAY");
}
if($week_count==5)
{
return language("WEEKDAY_FRIDAY");
}
if($week_count==6)
{
return language("WEEKDAY_SATURDAY");
}
}
?>
