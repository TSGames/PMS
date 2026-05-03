<?php
// Module includes for refactored functions
require_once 'functions_user.php';
require_once 'functions_database.php';
require_once 'functions_ui.php';
require_once 'functions_content.php';
require_once 'functions_image.php';
require_once 'functions_mail.php';
require_once 'functions_editor.php';
require_once 'functions_utility.php';

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
	// Gets the template_lists files
	$anti_spam_count=0;
	
	
	function make_antispam($short=0)
	{
		global $anti_spam_count;
		$anti_spam_count++;
		$rand=random_type(6);
		$_SESSION["spam".$anti_spam_count]=$rand;
		if($short) return "<img src=\"image.php?id=".session_id()."&count=$anti_spam_count\" border=\"0\" alt=\"Code\"><input type=\"hidden\" name=\"session\" value=\"".session_id()."\"><input type=\"hidden\" name=\"spamcount\" value=\"$anti_spam_count\"> <input type=\"text\" name=\"spam\" maxlength=\"6\" size=\"6\">";
		return "
<script type=\"text/javascript\">
</script>
<tr><td>".str_replace(array("%1","%2"),array("<img src=\"image.php?id=".session_id()."&count=$anti_spam_count\" border=\"0\" alt=\"Code\">","<a href=\"javascript:toggle(".$anti_spam_count.")\">".language("SPAM_HELP")."</a>"),language("SPAM"))."
<input type=\"hidden\" name=\"session\" value=\"".session_id()."\"><input type=\"hidden\" name=\"spamcount\" value=\"$anti_spam_count\"></td><td><input type=\"text\" name=\"spam\" maxlength=\"6\" size=\"6\"></td></tr>
<tr id=\"".$anti_spam_count."\" style=\"display: none\"><td>
".language("SPAM_LEFT")."</td><td>
".language("SPAM_RIGHT")."
</td></tr>";
	}
	
	$str.=make_imgalt("rate".$b.".gif",0,$alt,"template_files/","rate");
	
	
	
				
				?>
				