<?php
define("PMS_FRONTEND",0);
define("PMS_BACKEND",1);
require('functions.php');

$modul=$_GET["modul"];

$admin_center=1;

$action_name[0]="Home";
$action_list[0]="home";
$action_info[0]="Anzeigen der Startseite von PMS";
$action_name[1]="Website-Konfigurator";
$action_list[1]="config";
$action_info[1]="Festlegen globaler Einstellungen für diese Website";
$action_name[2]="Menü";
$action_list[2]="menu";
$action_info[2]="Konfigurieren und Anpassen der Menü-Einträge";
$action_name[3]="Benutzerverwaltung";
$action_list[3]="user";
$action_info[3]="Verwaltung und Rechtevergabe der Benutzerkonten";
$action_name[4]="Kategorien";
$action_list[4]="cat";
$action_info[4]="Anlegen, Bearbeiten und Löschen von Haupt-Kategorien";
$action_name[5]="Unterkategorien";
$action_list[5]="subcat";
$action_info[5]="Anlegen, Bearbeiten und Löschen von Unter-Kategorien";
$action_name[6]="Inhalte";
$action_list[6]="item";
$action_info[6]="Erstellen, Bearbeiten und Löschen von Textseiten sowie Wiederherstellung aus Backups";
$action_name[7]="Variablen";
$action_list[7]="var";
$action_info[7]="Konfigurieren von veränderbaren Platzhaltern";
$action_name[8]="Umfragen";
$action_list[8]="poll";
$action_info[8]="Festlegen von Fragen & Antworten für das Umfragen-Plugin";
$action_name[9]="Bans/Sperrungen";
$action_list[9]="bans";
$action_info[9]="Bestimmte IP-Adressen dauerhaft oder vorübergehend sperren";
$action_name[10]="Ereignisse";
$action_list[10]="events";
$action_info[10]="Übersichtliche Liste der Ereignisse auf der Website";
$action_name[11]="Backup-Manager";
$action_list[11]="backup";
$action_info[11]="Erstellen und Löschen von Datenbank & System-Backups";
$action_name[12]="Website-Status";
$action_list[12]="activity";
$action_info[12]="Anzeige aktueller Website-Aktivitäten";
$action_name[13]="Website anzeigen";
$action_list[13]="page";
$action_info[13]="Die Website anzeigen";
ob_start();
if(@include('update.php'))
{
	$modul_content=ob_get_contents();
	$modul_name[0][0]="Update";
	$modul_name[0][1]="update";
}
if(@include('modules/upload.php'))
{
	$modul_content=ob_get_contents();
	$modul_name[5][0]="Bilder-Upload";
	$modul_name[5][1]="picture_upload";
}
if(@include('modules/newsletter.php'))
{
	$modul_content=ob_get_contents();
	$modul_name[10][0]="Newsletter";
	$modul_name[10][1]="newsletter";
}
ob_end_clean();
ob_start();
include('counter.php');
echo '<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="stylesheet" type="text/css" href="admin.css">
<link rel="SHORTCUT ICON" href="admin.ico">

<title>PMS Administration (BackEnd) - '.from_db("config",1,"name").'</title>';
if(!$_SESSION['tinymce'])
{
	$_SESSION['tinymce']=from_db("config",1,"editor")+1;
}
if(array_key_exists("item_step1",$_POST))
{
	$_SESSION['tinymce']=$_POST['tinymce']+1; // Check as soon as possible!
}
echo '
<script type="text/javascript">
var IE = document.all?true:false

// If NS -- that is, !IE -- then set up for mouse capture
if (!IE) document.captureEvents(Event.MOUSEMOVE)

// Set-up to use getMouseXY function onMouseMove
document.onmousemove = getMouseXY;

// Temporary variables to hold mouse x-y pos.s
var tempX = 0
var tempY = 0

function getMouseXY(e) {
	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft
		tempY = event.clientY + document.body.scrollTop
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX
		tempY = e.pageY
	} 
}

function show_info(a)
{
	if(!tempX && !tempY) return;
	var b=document.getElementById(\'info_1\');
	b.style.left=(tempX+14) + "px";
	b.style.top=(tempY+15) + "px";
	b.firstChild.nodeValue=a;
	b.style.display=\'\';
	b=document.getElementById(\'info_2\');
	b.style.left=(tempX+18) + "px";
	b.style.top=(tempY+19) + "px";
	b.firstChild.nodeValue=a;
	b.style.display=\'\';
}
function hide_info()
{
	document.getElementById(\'info_2\').style.display=\'none\';
	document.getElementById(\'info_1\').style.display=\'none\';
}
</script>
';
if($_SESSION['tinymce']==2 || $modul=="newsletter")
{
	echo get_tinymce();
}
?>
<script type="text/javascript" src="drag.js"></script>
</head>
<body>
<table width="100%" height="100%" cellspacing="0" cellpadding="0">
<tr height="6px">
<td>
<?
$login=0;
if(@$_SESSION['pmsglobal']==1)
{
	$login=1;
}
if(@$_GET["action"]=="logout")
{
	delete_sessions();
	echo "<center>";
	$ok="Logout erfolgreich!";
	ok_error();
	echo "</center>";
	$login=0;
}
if(array_key_exists("login",$_POST) || @$_COOKIE["login_id"] && @$_COOKIE["login_pw"])
{
	$post=array_key_exists("login",$_POST);
	if($post)
	$a=do_login($_POST['login_name'],$_POST['login_password'],2);
	else $a=do_login(from_db("user",$_COOKIE["login_id"]*1,"name"),$_COOKIE["login_pw"],2,0);
	if(!is_array($a))
	{
		setcookie("login_id","",time()-3600,"/",$cookie_domain);
		setcookie("login_pw","",time()-3600,"/",$cookie_domain);
		if($post)
		{
			echo "<div align=\"center\">";
			if($a==1)
			$error="Benutzer existiert nicht!";
			else if($a==2)
			$error="Passwort ist ungültig!";
			else if($a==3)
			$error="Der Benutzer ist gesperrt!";
			else if($a==4)
			$error="Ihre Berechtigungen sind zu niedrig!";
			
			ok_error();
			echo "</div>";
		}
	}
	else
	{
		setcookie("login_id",$a[3],time()+60*60*24*1000,"/",$cookie_domain);
		if($_POST['save_login'])
		{
			setcookie("login_pw",md5($_POST['login_password']),time()+60*60*24*1000,"/",$cookie_domain);
		}
		$set_reloadable=reload_all(0);
		$_SESSION['reload_check']=1;
	}
	unset($post);
}
if(@$_GET["action"]=="load_last") // run pending actions from last logout
{
	unset($_SESSION['reload_check']);
	reload_all(1);
}
if(@$_SESSION['reload_check']>=2)
{
	unset($_SESSION['reload_check']);
	reload_all(-1);
}
if(@$_SESSION['reload_check'])$_SESSION['reload_check']++;

// Get Global Values!
$delete=@$_GET["delete"];
$edit=@$_GET["edit"];
$new=@$_GET["new"];
$edit=@$_GET["edit"];
$subcat_filter=@$_SESSION["subcat_filter"];
$item_filter=@$_SESSION["item_filter"];
$item_filter2=@$_SESSION["item_filter2"];
$sort_do=@$_GET["sort"];
$sort_para=@$_GET["pos"];
$id_para=@$_GET["id"];
$action=@$_GET["action"];
$modul=@$_GET["modul"];

if(from_db("user",@$_SESSION['userid'],"typ")<2)
{
	delete_sessions();
	$login=0;
}
if($login==0)
{
	if(count($_POST) && !$_POST["login"])
	{
		$error="Aus Sicherheitsgründen wurde die Sitzung beendet.<br>Bitte geben Sie Ihre Zugangsdaten erneut ein";
		ok_error();
	}
	unset($name);
	if(!@$_POST["login"]) store_all(); // save last action!
	if(@$_COOKIE["login_id"]) $name=from_db("user",@$_COOKIE["login_id"]*1,"name");
	echo form()."<table align=\"center\" style=\"max-width: 500px\"><tr>
	<td style=\"display:flex;justify-content:center\">
	</td></tr><tr><td><center><b>PMS Back End Login</b><table><tr><td>Benutzername:</td><td>
	<input type=\"text\" name=\"login_name\" value=\"".str_replace('"','&quot;',@$name || '')."\"></td></tr>
	<tr><td>Passwort:</td><td><input type=\"password\" name=\"login_password\"></td></tr>
	<tr><td colspan=\"2\"><div align=\"center\"><input type=\"checkbox\" name=\"save_login\" value=\"1\"> Zugangsdaten auf diesem Computer speichern</div>
	<tr><td colspan=\"2\"><div align=\"center\"><input type=\"submit\" name=\"login\" value=\"Einloggen\"></div>
	</td></tr></table>
	</form></td></tr>
	</table>";
}
echo ob_get_clean();
if($login==1)
{
	unset($a);
	echo '<label id="info_1" style="z-index:2;background-color:#efefef;padding: 5px;box-shadow:3px 3px 5px #00000088;position:absolute;display:none;">a</label>
	<label id="info_2" style="opacity: .5;z-index:1;color:#606060;background-color:#606060;position:absolute;display:none;">a</label>';
	echo "
	<table width=\"100%\" height=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td class=\"admin_menu\" width=\"250px\">
	<div id=\"menu_id\" class=\"menu_div\">
	<table class=\"menu\" cellspacing=\"0\" width=\"250px\">";
	echo "</td></tr>
	<tr><td class=\"menu_welcome\">Hallo, ".from_db("user",$_SESSION['userid'],"name")." (<a href=\"admin.php?action=logout\">Logout</a>)";
	echo '<tr><td>Menü</td></tr>
	<tr><td>
	<table cellspacing="0" cellpadding="0">';
	
	for($i=0;$i<count($action_name);$i++)
	{
		$add="";
		$file=$image_path."admin/".$action_list[$i].".png";
		if(file_exists($file)) $add='<img src="'.$file.'" width="16px" height="16px">';
		$href='admin.php?action='.$action_list[$i];
		unset($target);
		if($action_list[$i]=="page"){
			$href="index.php";
			$target=' target="_blank"';
		}
		echo '<tr><td class="menu_icon">'.$add.'</td><td><a onmouseover="show_info(\''.$action_info[$i].'\')" onmouseout="hide_info()" href="'.$href.'"'.$target.'>'.$action_name[$i].'</a></td></tr>
		';
	}
	echo "</table></td></tr>";
	if(count($modul_name))
	{
		echo '
		<tr><td>Module</td></tr>
		<tr><td>
		<table cellspacing="0" cellpadding="0">
		';
		$i=0;
		foreach($modul_name as $a)
		{
			$add="";
			$file=$image_path."admin/".$a[1].".png";
			if(file_exists($file)) $add='<img src="'.$file.'" width="16px" height="16px">';
			echo '<tr><td class="menu_icon">'.$add.'</td><td><a href="admin.php?modul='.$a[1].'">'.$a[0].'</a></td></tr>
			';
		}
		echo "</table></td></tr>";
	}
	echo "</table>
	</td><td class=\"admin_content\"><div align=\"center\">
	";
	$update_info="update.info";
	if($_SESSION["config_id"]) $update_info="update_".$_SESSION["config_id"].".info";
	if(!file_exists($update_info))
	{
		$f=fopen($update_info,"w+");
		fwrite($f,$pms_version);
		fclose($f);
	}
	if(file_exists("update.sql") && $action=="update" && from_db("user",$_SESSION['userid'],"typ")>2)
	{
		$last_version=trim(@file_get_contents($update_info));
		$a_count=update_engine(1,$last_version,$pms_db_prefix);
		if($a_count[0]==$a_count[1])
		{
			$f=fopen($update_info,"w+");
			fwrite($f,$pms_version);
			fclose($f);
			$ok="Update erfolgreich installiert!";
		}
		else 
		$error="Fehler bei der Installation des Updates.<br>Bitte melden Sie das Problem an den Support!<br>Weitere Informationen in der Datei update_sql.log";
		ok_error(); 
		unlink("update.sql");
		$action="home";
	}
	$last_version=trim(@file_get_contents($update_info));
	if($pms_db_use_reference)
	{
		$id=$pms_db_reference_id*1;
		if($id) $id="_".$id;
		else $id="";
		if($last_version<trim(@file_get_contents("update".$id.".info")) && !file_exists("update.sql")) // added update.sql
		{
			$found=1;
			echo heading("Updates notwendig").'
			Dieses Web-System ist ein Referenz-System eines anderen Systems.
			<br>
			Es wurde jedoch festgestellt, dass das primäre System eine andere Datenbank-Version benutzt.
			<br><br>
			Klicken Sie bitte auf den Button unten, um ein Update auszuführen.<br><br>
			<br><br><br>
			[';
			if(from_db("user",$_SESSION['userid'],"typ")>2) 
			echo '<a href="update.php?action=do">System aktualisieren</a>]';
			else echo '<span class="disabled">System aktualisieren</span>]<br><br>
			<span class="disabled">Sie müssen Super-Administrator sein, um den Vorgang fortzusetzen.
			<br><br>
			Ein Zugriff auf die Administrationsoberfläche ist erst möglich,<br>
			wenn ein Super-Administrator diesen Vorgang abgeschlossen hat.</span>';
		}
	}
	if(file_exists("update.sql") && !$found)
	{
		$found=update_engine(0,$last_version);
		if($found)
		{
			echo heading("Update Installieren").'
			Das System wurde aktualisiert.<br>Bevor jedoch die volle Funktionalität der neuen Version verfügbar ist, muss die Installation abgeschlossen werden.<br>
			<br>
			Klicken Sie bitte auf den Button unten, um die Installation durchzuführen.<br><br>
			<b>Wichtig: </b>Erstellen Sie nach erfolgreichem Update ein Backup der Website!
			<br><br><br>
			[';
			if(from_db("user",$_SESSION['userid'],"typ")>2) 
			echo '<a href="admin.php?action=update">Update Installieren</a>]';
			else echo '<span class="disabled">Update Installieren</span>]<br><br>
			<span class="disabled">Sie müssen Super-Administrator sein, um den Vorgang fortzusetzen.
			<br><br>
			Ein Zugriff auf die Administrationsoberfläche ist erst möglich,<br>
			wenn ein Super-Administrator diesen Vorgang abgeschlossen hat.</span>';
		}
		else
		@unlink("update.sql");
	}
	if(!$found)
	{
		if($_POST['poll_filter']=="OK")
		{
			$action="var";
			// Workaround because a PHP Bug warning
			$_SESSION['poll_search']=0;
			$_SESSION['poll_replace']=0;
			if($_POST['poll_search'])
			{
				$_SESSION['poll_search']=1;
			}
			if($_POST['poll_replace'])
			{
				$_SESSION['poll_replace']=1;
			}
		}
		if($_POST["item_restore"]) $action="item_restore";
		
		if(array_key_exists("menu_refresh",$_POST))
		{
			$action="menu";
			$post=1;
		}
		if($_POST['events']=="OK")
		{
			$action="events";
			$_SESSION['last_events']=$_POST['last_events'];
		}
		if($_POST['poll']=="Speichern")
		{
			$action="poll";
			$edit=$_POST['id'];
			$question=$_POST['question'];
			$sort=$_POST['sort'];
			$available=$_POST['available'];
			
			$do="INSERT INTO ".$pms_db_prefix."poll (question,sort,available";
			for($i=1;$i<=10;$i++)
			{
				$do=$do.",answer".$i;
			}
			$do=$do.") VALUES ('$question','$sort','$available'";
			for($i=1;$i<=10;$i++)
			{
				$do=$do.",'".$_POST['answer'.$i]."'";
			}
			$do=$do.")";
			if($edit)
			{
				$do="UPDATE ".$pms_db_prefix."poll SET question = '$question', sort = '$sort', available = '$available'";
				for($i=1;$i<=10;$i++)
				{
					$do=$do.", answer".$i." = '".$_POST['answer'.$i]."'";
				}
				$do=$do." WHERE id = '$edit'";
			}
			$do=$do.";";
			if($pms_db_connection->query($do))
			$ok="Umfrage erfolgreich gespeichert!";
			else
			$error="Fehler beim Speichern der Umfrage!";
			ok_error();
			$edit="";
		}
		
		if($_POST['var']=="Speichern")
		{
			$action="var";
			$edit=$_POST['id'];
			$search=$_POST['search'];
			$replace=$pms_db_connection->escape($_POST['replace']); // Addslashes doesn't work correct
			$makebr=$_POST['makebr'];
			$do="INSERT INTO ".$pms_db_prefix."dynamic (searcher,replacer,makebr) VALUES ('$search','$replace','$makebr');";
			if($edit)
			{
				$do="UPDATE ".$pms_db_prefix."dynamic SET searcher = '$search', replacer = '$replace', makebr = '$makebr' WHERE id = '$edit' LIMIT 1;";
			}
			if($pms_db_connection->query($do))
			$ok="Regel erfolgreich gespeichert!";
			else
			$error="Fehler beim Speichern der Regel!";
			ok_error();
			$edit="";
		}
		if(array_key_exists("backup",$_POST) && from_db("user",$_SESSION['userid'],"typ")>2)
		{
			$action="backup";
			$result=do_export();
			if($result[0]==$result[1])
			$ok="Der Export aller Dateien war erfolgreich.";
			elseif($result==0)
			$error="Der Export konnte nicht durchgeführt werden. Überprüfen Sie die Ordnerberechtigungen, oder wenden Sie sich an den Support!";
			else
			$error="Der Export konnte nicht vollständig durchgeführt werden. Möglicherweise wird auf einige Dateien momentan zugegriffen. Versuchen Sie es später erneut!";
			ok_error();
		}
		if($_POST['bans']=="Speichern")
		{
			$action="bans";
			$edit=$_POST['id'];
			$ip=$_POST['ip'];
			$reason=$_POST['reason'];
			$time=time()+str_replace(",",".",$_POST['time'])*60*60*24;
			if(str_replace(",",".",$_POST['time'])<=0)
			{
				$time=0;
			}
			$do="INSERT INTO ".$pms_db_prefix."bans (ip,reason,time) VALUES ('$ip','$reason','$time');";
			if($edit)
			{
				$do="UPDATE ".$pms_db_prefix."bans SET ip = '$ip', reason = '$reason', time = '$time' WHERE id = '$edit' LIMIT 1;";
			}
			if($pms_db_connection->query($do))
			$ok="Ban erfolgreich gespeichert!";
			else
			$error="Fehler beim Speichern des Bans!";
			ok_error();
			$edit="";
		}
		if(array_key_exists("item_step1",$_POST))
		{
			$action="item";
			$edit=$_POST['id'];
			$cat=$_POST['cat'];
			$subcat=$_POST['subcat'];
			$typ=$_POST['typ'];
			$typ2=$_POST['typ2'];
			$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."subcat WHERE id = '$subcat' AND cat = '$cat' LIMIT 1");
			$ok=0;
			if($link && $typ!=3)
			{
				$a=$pms_db_connection->fetchObject($link);
				if($a->id)
				{
					$ok=1;
				}
			}
			if($typ==3 && $typ2)
			{
				$ok=1;
			}
			$post=1;
			if($ok==1)
			{
				$post=2;
			}
		}
		if(array_key_exists("item_step2",$_POST))
		{
			if($_POST["item_step2"]=="Übernehmen")
			{
				$post=2;
			}
			$action="item";
			$edit=$_POST['id'];
			$name=$pms_db_connection->escape($_POST['name']);
			$description=$pms_db_connection->escape($_POST['description']);
			$sort=$_POST['sort'];
			$cat=$_POST['cat'];
			$subcat=$_POST['subcat'];
			$_SESSION['item_filter']=$cat;
			$item_filter=$cat;
			$_SESSION['item_filter2']=$subcat;
			$item_filter2=$subcat;
			if($typ==3)
			{
				$cat=0;
				$subcat=0;
			}
			$content=$pms_db_connection->escape($_POST['content']);
			$typ=$pms_db_connection->escape($_POST['typ']);
			$typ2=$pms_db_connection->escape($_POST['typ2']);
			$link=$pms_db_connection->escape($_POST['link']);
			$user=$pms_db_connection->escape($_POST['user']);
			$available=$pms_db_connection->escape($_POST['available']);
			$visible=$pms_db_connection->escape($_POST['visible']);
			$image=$pms_db_connection->escape($_FILES["image"]["name"]);
			$showuser=$pms_db_connection->escape($_POST['showuser']);
			$rate=$pms_db_connection->escape($_POST['rate']);
			$comments=$pms_db_connection->escape($_POST['comments']);
			$time=time();
			if(!$_POST["create_at_use"])
			{
				unset($d);unset($t);
				$d=explode(".",$_POST["create_at_date"]);
				$t=explode(":",$_POST["create_at_time"]);
				$time_posted=@mktime($t[0],$t[1],0,$d[1],$d[0],$d[2]);
				if($edit)$time_create_change=", time = '".$time_posted."'";
				else $time=$time_posted;
			}
			
			if($image)
			$end=substr($image,-3);
			else if($_POST["image_add"])
			{
				if(!$_POST["image_delete"])
				$image2=$_POST["image_add"];
				
				$end=$_POST["image_add"];
			}
			if($edit && $_POST['image_delete'])
			{
				del_contentimg("item",$edit,$end);
				$img="image = 0,";
			}
			if($typ==3)
			{
				$pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET special = 0 WHERE special = '$typ2';");
			}
			$do="INSERT INTO ".$pms_db_prefix."item (cat,subcat,name,typ,special,showuser,rate,comments,description,content,image,sort,user,time,link,available,visible) VALUES ('$cat','$subcat','$name','$typ','$typ2','$showuser','$rate','$comments','$description','$content','$image','$sort','$user','$time','$link','$available','$visible');";
			if($edit)
			$do="UPDATE ".$pms_db_prefix."item SET cat = '$cat', subcat = '$subcat', name = '$name', typ = '$typ', special = '$typ2',showuser = '$showuser', rate = '$rate', comments = '$comments', description = '$description', content = '$content',".$img." sort = '$sort', user = '$user', link = '$link', available = '$available', visible = '$visible', time_changed = '$time'".$time_create_change." WHERE id = '$edit' LIMIT 1;";
			$save_ok=0;
			if($pms_db_connection->query($do))
			{
				if(!$edit)
				{
					$edit=$pms_db_connection->lastInsertId();
					
				}
				if($image || $image2)
				{
					if(!$image || in_array(strtolower($end),$supported_img))
					{
						del_contentimg("item",$edit,from_db("item",$edit,"image"));
						if($image)
						{
							$target=$image_path."item/".$edit.".".$end;
							$target2=$image_path."item/".$edit."_large.".$end;
							$target3=$image_path."item/".$edit."_full.".$end;
							@copy($_FILES["image"]["tmp_name"],$target);
							create_img($target,64,64);
							if($typ!=1) // no news, than also big picture
							{
								@copy($_FILES["image"]["tmp_name"],$target2);
								create_img($target2,640,480);
								if($_POST["full_image"]) @copy($_FILES["image"]["tmp_name"],$target3);
							}
						}
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET image = '$end' WHERE id = '$edit' LIMIT 1;");
					}
				}
				$save_ok=1;
				$ok="<div align=\"center\">Inhalt erfolgreich gespeichert!
				<br><a href=\"index.php?item=".$edit."\">Inhalt anzeigen</a></div>";
			}
			else {
			$error="Fehler beim Speichern des Inhalts!";
			}
			if($save_ok && $_POST["next"]=="image") $add_image=$edit;
			else if($_POST["next"]!="dragdrop") ok_error();
			if(utf8_encode($_POST["item_step2"])!="Übernehmen")
			{
				unset($edit);
			}
		}
		if($_POST["add_image"]!="")
		{
			$action="item";
			$id=$_POST["item"];
			if(!$id){
				$link=$pms_db_connection->query("SELECT LAST_INSERT_ID() FROM ".$pms_db_prefix."item");
				echo mysqli_error();
				if($link && $a=mysqli_fetch_array($link))
				$id=$a[0];
			}
			unset($path);unset($error);
			if($_POST["drag_name"]){
				$_FILES["image"]["name"]=$_POST["drag_name"];
				$img_data=rawurldecode($_POST["drag_data"]);
			}
			if($_FILES["image"]["name"])
			{
				$path="images/uploads/";
				$fname=link_name($_FILES["image"]["name"],"."); // clear all strange characters
				$fname2=explode(".",$fname);
				$end=strtolower($fname2[count($fname2)-1]);
				unset($fname2[count($fname2)-1]);
				$fname_first=implode(".",$fname2);
				if(!in_array($end,$supported_img))
				{
					$add_image=$id;
					$error="Nicht unterstütztes Dateiformat";
				}
				else
				{
					for($i=0;;$i++)
					{
						if($i) $check=$fname_first.$i.".".$end;
						else $check=$fname;
						if(!file_exists($path.$check))
						{
							@mkdir($path);
							if($img_data){
								$f=fopen($path.$check,"wb+");
								if($f){
									fwrite($f,$img_data);
									fclose($f);
									$ok=true;
								}
								else
								$ok=false;
							}
							else{
								$ok=copy($_FILES["image"]["tmp_name"],$path.$check);
							}
							if(!$ok)
							{
								$error="Fehler beim Anlegen der Datei";
								$add_image=$id;
							}
							break;
						}
					}
					if(!$error)
					{
						$add_image2=$id;
						$add_image2_img=$check;
					}
				}
			}
			else
			{
				$error="Keine Datei ausgewählt";
				$add_image=$id;
			}
			if($error) ok_error();
		}
		if($action=="add_image" && $_GET["delete"])
		{
			if(@unlink("images/uploads/".str_replace(array("/","\\"),"",$_GET["delete"])))
			$ok="Bild wurde entfernt";
			else
			$error="Bild konnte nicht entfernt werden";
			ok_error();
			$action="item";
			$add_image=$_GET["item"];
		}
		if($action=="add_image" && $_GET["item"] && ($_GET["image"] || $_GET["abort"]) || array_key_exists("add_image2",$_POST) || array_key_exists("add_image2_abort",$_POST))
		{
			$select=$action=="add_image";
			$action="item";
			$edit=$_POST["item"]*1;
			$image=$_POST["image"];
			$delete=array_key_exists("add_image2_abort",$_POST);
			
			if(!$select && ($_POST["image_width"]<1 || $_POST["image_height"]<1) && !$delete)
			{
				$error="Ungültige Bildgröße";
				ok_error();
				$add_image2=$edit;
				$add_image2_img=$image;
				unset($edit);
			}
			else
			{
				if($select)
				{
					$image=$_GET["image"];
					$edit=$_GET["item"]*1;
				}
				$file="images/uploads/".$image;
				if($delete)
				@unlink($file);
				elseif(!$select)
				{
					create_img($file,$_POST["image_width"],$_POST["image_height"],0);
				}
				if(!$delete) $size=@getimagesize($file);
				$link=$pms_db_connection->query("SELECT cat,subcat,typ,special FROM ".$pms_db_prefix."item WHERE id = '".$edit."'");
				if($link && $a=$pms_db_connection->fetchObject($link))
				{
					$cat=$a->cat;
					$subcat=$a->subcat;
					$typ=$a->typ;
					$typ2=$a->special;
					if(!$delete) $delete=$select && $_GET["item"] && $_GET["abort"]; // don't insert an invalid image - abort procedure!
					if(!$delete)
					{
						$edit_string_replace='pms_replace_image_temp';
						$edit_string_use=$file;
					}
					$post=2;
				}
				else
				{
					unset($edit);
					$error="Interner Verarbeitungsfehler";
					ok_error();
				}
			}
		}
		if($_POST["send_bot_filter"])
		{
			$_SESSION['filter_bot']=$_POST['filter_bot'];
			$action="activity";
		}
		if(array_key_exists("item_refresh",$_POST))
		{
			if($_POST['tinymce_vis'])
			{
				$_SESSION['tinymce']=$_POST['tinymce']+1;
			}
			$action="item";
			$edit=$_POST['id'];
			$post=1;
		}
		if(array_key_exists("cat_delete",$_POST))
		{
			$action="cat";
			$delete=$_POST['id'];
			$i=0;
			$link=$pms_db_connection->query(make_sql("subcat","cat = '$delete'","id"));
			while($link && $a=$pms_db_connection->fetchObject($link))
			{
				del_contentimg("subcat",$a->id,$a->image);
			}
			$link=$pms_db_connection->query(make_sql("item","cat = '$delete'","id"));
			while($link && $a=$pms_db_connection->fetchObject($link))
			{
				del_contentimg("item",$a->id,$a->image);
			}
			$i+=$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."cat WHERE id = '$delete' LIMIT 1;") !== false;
			$i+=$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."subcat WHERE cat = '$delete';") !== false;
			$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE cat = '$delete';");
			while($link && $a=$pms_db_connection->fetchObject($link))
			{
				$id=$a->id;
				$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."comments WHERE item = '$id'");
			}
			$i+=$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."item WHERE cat = '$delete';");
			if($i>0)
			$ok="Kategorie erfolgreich entfernt!";
			ok_error();
			$delete="";
		}
		$confirmation_dialogs[0][0]="user_guestbook";
		$confirmation_dialogs[0][1]="mail_guestbook";
		$confirmation_dialogs[1][0]="user_comments";
		$confirmation_dialogs[1][1]="mail_comments";
		$confirmation_dialogs[2][0]="user_register";
		$confirmation_dialogs[2][1]="mail_register";
		if($_POST['config']=='Speichern' && from_db("user",$_SESSION['userid'],"typ")>=3)
		{
			$action="config";
			$name=$_POST['name'];
			$title=$_POST['title'];
			$page=$_POST['page'];
			$mail=$_POST['mail'];
			$rate=$_POST['rate'];
			$comments=$_POST['comments'];
			$commentssmall=$_POST['commentssmall'];
			$numcomments=$_POST['numcomments'];
			$mincomments=$_POST['mincomments'];
			$numtopuser=$_POST['numtopuser'];
			$picquali=$_POST['picquali'];
			$menubreak=$_POST['menubreak'];
			$page_limit=$_POST['page_limit'];
			$list_rows=$_POST['list_rows'];
			$predownload=$_POST['predownload'];
			$visitors_increment=$_POST['visitors_increment'];
			$register_activated=$_POST['register_activated'];
			$password_recovery_activated=$_POST['password_recovery_activated'];
			$guestbook_activated=$_POST['guestbook_activated'];
			$writtenby=$_POST['writtenby'];
			$vertical=$_POST['vertical'];
			$menu_width=$_POST['menu_width'];
			$menu_height=$_POST['menu_height'];
			$editor=$_POST['editor'];
			$smileys=$_POST['smileys'];
			$safemail=$_POST['safemail'];
			$topusers=$_POST['topusers'];
			$visitors_lifetime=$_POST["visitors_lifetime"];
			$speciallinks=$_POST["speciallinks"];
			$latest_comments_days=$_POST["latest_comments_days"];
			$latest_comments_chars=$_POST["latest_comments_chars"];
			$language=$_POST["language"];
			$allow_compress=$_POST["allow_compress"];
			$menu_mode=$_POST['menu_mode'];
			$search_list=$_POST['search_list'];
			$visitors_password=$pms_db_connection->escape($_POST['visitors_password']);
			if($visitors_lifetime<0)
			{
				$visitors_lifetime=0;
			}
			if($picquali<10)
			{
				$picquali=10;
			}
			if($picquali>100)
			{
				$picquali=100;
			}
			if($page_limit<1)
			{
				$page_limit=1;
			}
			if($numcomments<0)
			{
				$numcomments=0;
			}
			if($mincomments<0)
			{
				$mincomments=0;
			}
			if($numtopuser<0)
			{
				$numtopuser=0;
			}
			if($menu_width<0)
			{
				$menu_width=0;
			}
			if($menu_height<0)
			{
				$menu_height=0;
			}
			foreach($confirmation_dialogs as $f)
			{
				$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET ".$f[1]." = '0' WHERE typ >= '2'");
				if($_POST[$f[0]]) {
					for($i=0;$i<count($_POST[$f[0]]);$i++)
					{
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET ".$f[1]." = '1' WHERE id = '".$_POST[$f[0]][$i]."'");
					}
				}
			}
			if($pms_db_connection->query("UPDATE ".$pms_db_prefix."config SET name = '$name', title = '$title', page = '$page', mail = '$mail', rate = '$rate', comments = '$comments', commentssmall = '$commentssmall', numcomments = '$numcomments', mincomments = '$mincomments', numtopuser = '$numtopuser', predownload = '$predownload', writtenby = '$writtenby', picquali = '$picquali', menubreak = '$menubreak', vertical = '$vertical', menu_width = '$menu_width', menu_height = '$menu_height', page_limit = '$page_limit', list_rows = '$list_rows', visitors_increment = '$visitors_increment', visitors_lifetime = '$visitors_lifetime', register_activated = '$register_activated', password_recovery_activated = '$password_recovery_activated', guestbook_activated = '$guestbook_activated', editor = '$editor', safemail = '$safemail', topusers = '$topusers', speciallinks = '$speciallinks', latest_comments_days= '$latest_comments_days', latest_comments_chars = '$latest_comments_chars', language = '$language', allow_compress = '$allow_compress', menu_mode = '$menu_mode', search_list = '$search_list', visitors_password = '$visitors_password', smileys = '$smileys';"))
			$ok="Einstellungen erfolgreich gespeichert!";
			else
			$error="Fehler beim Speichern!";
			ok_error();
		}
		if(array_key_exists("subcat_filter",$_POST))
		{
			$action="subcat";
			$subcat_filter=$_POST["uppcat"];
			$_SESSION["subcat_filter"]=$_POST["uppcat"];
		}
		if(array_key_exists("item_filter",$_POST))
		{
			$action="item";
			$item_filter=$_POST["uppcat"];
			$_SESSION["item_filter"]=$_POST["uppcat"];
			$item_filter2=$_POST["uppcat2"];
			$ok=0;
			$link=$pms_db_connection->query(make_sql("subcat","id = '$item_filter2' AND cat = '$item_filter'","id"));
			if($link)
			{
				$a=$pms_db_connection->fetchObject($link);
				if($a->id)
				{
					$ok=1;
				}
			}
			if($ok==0)
			{
				$item_filter2=0;
			}
			$_SESSION["item_filter2"]=$item_filter2;
		}
		if(array_key_exists("subcat_delete",$_POST))
		{
			$action="subcat";
			$delete=$_POST['id'];
			$i=0;
			$link=$pms_db_connection->query(make_sql("item","subcat = '$delete'","id"));
			while($link && $a=$pms_db_connection->fetchObject($link))
			{
				del_contentimg("item",$a->id,$a->image);
			}
			del_contentimg("subcat",$delete,from_db("subcat",$delete,"image"));
			$i+=$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."subcat WHERE id = '$delete' LIMIT 1;");
			$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE subcat = '$delete';");
			while($link && $a=$pms_db_connection->fetchObject($link))
			{
				$id=$a->id;
				$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."comments WHERE item = '$id'");
			}
			$i+=$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."item WHERE subcat = '$delete';");
			if($i>0)
			$ok="Unterkategorie erfolgreich entfernt!";
			ok_error();
			$delete="";
		}
		if($_POST["subcat"]=="Speichern")
		{
			$action="subcat";
			$edit=$_POST['id'];
			$name=$_POST['name'];
			$description=$_POST['description'];
			$sort=$_POST['sort'];
			$cat=$_POST['uppcat'];
			$available=$_POST['available'];
			$jump=$_POST['jump'];
			$image=$_FILES["image"]["name"];
			$_SESSION['subcat_filter']=$cat;
			$list=$_POST['list'];
			$subcat_filter=$cat;
			if($image)
			$end=substr($image,-3);
			else if($_POST["image_add"])
			{
				if(!$_POST["image_delete"])
				$image2=$_POST["image_add"];
				
				$end=$_POST["image_add"];
			}
			if($edit && $_POST['image_delete'])
			{
				del_contentimg("subcat",$edit,$end);
				$img="image = 0,";
			}
			$do="INSERT INTO ".$pms_db_prefix."subcat (name,description,sort,cat,available,jump,list) VALUES ('$name','$description','$sort','$cat','$available','$jump','$list');";
			if($edit)
			{
				$do="UPDATE ".$pms_db_prefix."subcat SET name = '$name', description = '$description',".$img." sort = '$sort', cat = '$cat', available = '$available', jump = '$jump', list = '$list' WHERE id = '$edit' LIMIT 1;";
				$link=$pms_db_connection->query(make_sql("item","subcat = '$edit'","id"));
				while($link && $a=$pms_db_connection->fetchObject($link))
				{
					$pms_db_connection->query("UPDATE ".$pms_db_prefix."item set cat = '$cat' WHERE id = '$a->id' LIMIT 1");
				}
			}
			if($pms_db_connection->query($do))
			{
				if($image || $image2)
				{
					if(!$image || in_array(strtolower($end),$supported_img))
					{
						if(!$edit)
						{
							$link=$pms_db_connection->query(make_sql("subcat","","id"));
							while($link && $a=$pms_db_connection->fetchObject($link))
							{
								$edit=$a->id;
							}
						}
						$target=$image_path."subcat/".$edit.".".$end;
						if($image)
						{
							copy($_FILES["image"]["tmp_name"],$target);
							create_img($target,64,64);
						}
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."subcat SET image = '$end' WHERE id = '$edit' LIMIT 1;");
					}
				}
				$ok="Unterkategorie erfolgreich gespeichert!";
			}
			else
			$error="Fehler beim Speichern der Unterkategorie!<br>" . $pms_db_connection->error();
			ok_error();
			$edit="";
		}
		if($_POST["cat"]=="Speichern")
		{
			$action="cat";
			$edit=$_POST['id'];
			$name=$_POST['name'];
			$sort=$_POST['sort'];
			$available=$_POST['available'];
			$list=$_POST['list'];
			$do="INSERT INTO ".$pms_db_prefix."cat (name,sort,available,list) VALUES ('$name','$sort','$available','$list');";
			if($edit)
			{
				$do="UPDATE ".$pms_db_prefix."cat SET name = '$name',sort = '$sort', available = '$available', list = '$list' WHERE id = '$edit' LIMIT 1;";
			}
			if($pms_db_connection->query($do))
			$ok="Kategorie erfolgreich gespeichert!";
			else
			$error="Fehler beim Speichern der Kategorie!";
			ok_error();
			$edit="";
		}
		if($_POST["user"]=="Speichern" && from_db("user",$_POST['id'],"typ")<=from_db("user",$_SESSION['userid'],"typ"))
		{
			$post=1;
			$edit=$_POST['id']*1;
			$action="user";
			$name=$_POST['name'];
			$password=$_POST['password'];
			$passwordr=$_POST['passwordr'];
			$mail=$_POST['mail'];
			$typ=$_POST['typ'];
			if($edit==$_SESSION["userid"] && $typ<from_db("user",$_SESSION['userid'],"typ")) $typ=from_db("user",$_SESSION['userid'],"typ");
			if($typ>from_db("user",$_SESSION['userid'],"typ")) $typ=from_db("user",$_SESSION['userid'],"typ");
			$active=$_POST['active'];
			if($edit==$_SESSION["userid"] && !$active)
			{
				$error="Sie können nicht Ihren aktuellen Account sperren.";
				$post=0;
				$edit=0;
			}
			else
			{
				$a=make_user($edit,$name,$password,$passwordr,$mail,$_POST['website'],$typ,$_FILES["image"]["name"],$_FILES["image"]["tmp_name"],$_POST['image_delete'],$_POST['bday'],$_POST['top'],$active,0,$_POST['signatur'],$_POST['showmail']);
				if(is_array($a))
				{
					if($a[1])$ok=$a[0];
					else $error=$a[0];
					$post=0;
					$edit=0;
				}
				else
				$error=$a;
			}
			ok_error();
		}
		if(array_key_exists("menu",$_POST))
		{
			$action="menu";
			$post=2;
		}
		if($post && $action=="menu")
		{
			$edit=$_POST['id'];
			$cat=$_POST['cat'];
			$subcat=$_POST['subcat'];
			$item=$_POST['item'];
			$name=$_POST['name'];
			$sort=$_POST['sort'];
			$usertyp=$_POST['usertyp'];
			$typ=$_POST['typ'];
			$plugin=$_POST['plugin'];
			$extern=$_POST['extern'];
			$visible=$_POST['visible'];
			$popup=$_POST['popup'];
			if($post==2)
			{
				$ok=1;
				if($subcat)
				{
					$link=$pms_db_connection->query(make_sql("subcat","id = ".$subcat." AND cat = ".$cat,"sort,name"));
					$ok=0;
					if($link)
					{
						$row=$pms_db_connection->fetchObject($link);
						if(!$row->id)
						{
							$ok=0;
						}
						else
						{
							$ok=1;
						}
					}
				}
				if($item && $ok==1)
				{
					$link=$pms_db_connection->query(make_sql("item","id = ".$item." AND subcat = ".$subcat,"sort,name"));
					$ok=0;
					if($link)
					{
						$row=$pms_db_connection->fetchObject($link);
						if(!$row->id)
						{
							$ok=0;
						}
						else
						{
							$ok=1;
						}
					}
				}
				if($ok==1)
				{
					$do="INSERT INTO ".$pms_db_prefix."menu (name,sort,typ,cat,subcat,item,usertyp,plugin,extern,visible,popup) VALUES ('$name','$sort','$typ','$cat','$subcat','$item','$usertyp','$plugin','$extern','$visible','$popup');";
					if($edit)
					{
						$do="UPDATE ".$pms_db_prefix."menu SET name = '$name', sort = '$sort', typ = '$typ', cat = '$cat', subcat = '$subcat', item = '$item', usertyp = '$usertyp', plugin = '$plugin', extern = '$extern', visible = '$visible', popup = '$popup' WHERE id = '$edit' LIMIT 1;";
					}
					if($pms_db_connection->query($do))
					$ok="Menüeintrag erfolgreich gespeichert!";
					else
					$error="Fehler beim Speichern des Menü-Eintrags!";
					ok_error();
					$edit="";
					$new="";
					$post=0;
				}
			}
		}
		if(!$action && !$modul || $action=="home")
		{
			echo '<h2>Willkommen im Admin Center!</h2>
			<br>
			Sie befinden sich nun im gesicherten Bereich der Website.<br>
			Bitte wählen Sie eine Aktion im Menü links aus<br>
			Im unteren Teil der Seite finden Sie die PMS-Referenz.<br>
			Klicken Sie dort auf die gewünschte Position, um direkt zur Hilfe zu gelangen.';
			if($set_reloadable)
			echo '<script type="text/javascript">
			if(confirm("Beim Ausführen des letzten Vorgangs (z.B. Schreiben einer Seite) wurden Sie abgemeldet. Die Änderungen wurden daher noch nicht übernommen. Möchten Sie diesen Vorgang jetzt ausführen, sodass die Änderungen gespeichert werden?\n\nHinweis: Wenn Sie diese Meldung abbrechen, werden die Daten gelöscht und die Änderungen nicht gespeichert."))
			location="admin.php?action=load_last";
			</script>';
			
			if(from_db("user",$_SESSION['userid'],"typ")>2)
			{
				$new=get_latest_version();
				if($new>$pms_version)
				{
					echo "<br><br><span style=\"font-weight:bold;\">Update-Hinweis</span><br>
					Es ist eine aktuellere Version von PMS verfügbar ($new).<br>Sie können das Update starten, wenn Sie auf die Seite <a href=\"admin.php?modul=update\">Update</a> wechseln.";
					if(!$_SESSION["update_notice"])
					{
						$_SESSION["update_notice"]=1;
						echo '<script type="text/javascript">
						if(confirm("Es ist eine aktualisierte Version von PMS verfügbar.\nMöchten Sie jetzt von Version '.$pms_version.' auf '.$new.' updaten?"))
						location="update.php?action=do";
						</script>';
					}
				}
				$back=get_backups();
				if(!is_array($back))
				{
					$backup_advised=1;
					echo '<br><br><span style="font-weight:bold;">Backup-Hinweis</span><br>Sie haben bisher keinerlei Backups Ihrer Website erstellt.';
				}
				else
				{
					$back=explode("_",$back[0]);
					$lastback=mktime($back[3],$back[4],0,$back[1],$back[2],$back[0]);
					if($lastback<time()-60*60*24*30)
					{
						$backup_advised=1;
						echo '<br><br><span style="font-weight:bold;">Backup-Hinweis</span><br>Das zuletzt durchgeführte Backup ist älter als 1 Monat (vom '.date("d.m.Y",$lastback).')';
					}
				}
				if($backup_advised)
				{
					echo '<br>
					<a href="admin.php?action=backup">Klicken Sie hier</a>, um ein neues Backup anzulegen.';
				}
			}
			echo "<br><br>
			<span style=\"font-weight:bold;\">Technische Systeminformationen:</span><br>
			Sie benutzen das Professional Management System, Version ".$pms_version.".<br>
			Um Informationen zu den Neuerungen zu sehen, klicken Sie <a href=\"http://www.tsgames.de/?item=270&version=".$pms_version."\" target=\"_blank\">hier</a> und Sie gelangen zur Versionshistory.";
				if($pms_db_use_reference)
				echo "<br><br>
				<span style=\"font-weight:bold;\">Erweiterte Informationen:</span><br>
				Dieses System läuft als sekundäres Referenzsystem eines anderen, primären Systems.<br>
				(Vermutlich stellt es den Inhalt in einer anderen Sprache bereit)<br>
				Sie müssen Kategorien, Inhalte usw. zunächst im primären System anlegen.<br>
				<a href=\"admin.php?config_id=".($pms_db_reference_id*1)."\">Klicken Sie hier</a>, um Daten im primären System anzulegen.";
			}
			if($action=="menu")
			{
				if($edit || $new || $post)
				{
					if($new && !$post)
					{
						$typ=0;
						$usertyp=0;
						$sort=1000;
						$visible=1;
					}
					if($edit && !$post)
					{
						$name=from_db("menu",$edit,"name");
						$sort=from_db("menu",$edit,"sort");
						$typ=from_db("menu",$edit,"typ");
						$cat=from_db("menu",$edit,"cat");
						$subcat=from_db("menu",$edit,"subcat");
						$item=from_db("menu",$edit,"item");
						if($item)
						{
							$cat=from_db("item",$item,"cat");
							$subcat=from_db("item",$item,"subcat");
						}
						elseif($subcat)
						{
							$cat=from_db("subcat",$subcat,"cat");
						}
						$usertyp=from_db("menu",$edit,"usertyp");
						$plugin=from_db("menu",$edit,"plugin");
						$extern=from_db("menu",$edit,'extern');
						$visible=from_db("menu",$edit,"visible");
						$popup=from_db("menu",$edit,"popup");
					}
					$radio[$typ]=" checked";
					$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
					while($link && $row=$pms_db_connection->fetchObject($link))
					{
						$sel="";
						if($row->id==$cat)
						{
							$sel=" selected";
						}
						$cats=$cats."<option value=\"".$row->id."\"".$sel.">".$row->name."</option>
						";
					}
					$link=$pms_db_connection->query(make_sql("subcat","cat = ".$cat,"sort,name"));
					while($link && $row=$pms_db_connection->fetchObject($link))
					{
						$sel="";
						if($row->id==$subcat)
						{
							$sel=" selected";
						}
						$subcats=$subcats."<option value=\"".$row->id."\"".$sel.">".$row->name."</option>
						";
					}
					$link=$pms_db_connection->query(make_sql("item","cat = ".$cat." AND subcat = ".$subcat,"sort,name"));
					while($link && $row=$pms_db_connection->fetchObject($link))
					{
						$sel="";
						if($row->id==$item)
						{
							$sel=" selected";
						}
						$items=$items."<option value=\"".$row->id."\"".$sel.">".$row->name."</option>
						";
					}
					$popup=make_check($popup);
					$vis="";
					if($visible)
					{
						$vis=" checked";
					}
					echo form();
					if($edit)
					{
						$add="bearbeiten";
					}
					else
					{
						$add="erstellen";
					}
					echo heading("Menüeintrag ".$add)."<table width=\"400px\">
					<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<tr><td width=\"80px\">Name:</td><td><input type=\"text\" name=\"name\" value=\"".$name."\"></td></tr>
					<tr><td>Sortierung:</td><td><input type=\"text\" name=\"sort\" value=\"".$sort."\"></td></tr>
					<tr><td>Sichtbar für:</td><td><select name=\"usertyp\">";
					for($i=-1;$i<count($user_typ)-1;$i++)
					{
						$sel="";
						if($i+1==$usertyp)
						{
							$sel=" selected";
						}
						echo "<option value=\"".($i+1)."\"".$sel.">".$user_typ[$i]."</option>";
					}
					echo"
					</td></tr>
					<tr><td colspan=\"2\">
					<input type=\"radio\" name=\"typ\" value=\"0\"".$radio[0]."> Verlinkung zu Kategorie/Inhalt-Liste</td></tr>
					<tr><td>Kategorie:</td><td><select onclick=\"check_radio(0)\" name=\"cat\">".$cats."</select> <input type=\"submit\" onclick=\"check_radio(0)\" name=\"menu_refresh\" value=\"Aktualisieren\"></td></tr>
					<tr><td>Unterkategorie:</td><td><select onclick=\"check_radio(0)\" name=\"subcat\"><option value=\"0\">[Keine]</option>".$subcats."</select></td></tr>";
					if($subcat)
					{
						echo "<tr><td>Inhalt-Objekt:</td><td><select onclick=\"check_radio(0)\" name=\"item\"><option value=\"0\">[Keins]</option>".$items."</select></td></tr>";
					}
					if($config_values->menu_mode) echo "<tr><td></td><td><input type=\"checkbox\" name=\"popup\" value=\"1\"".$popup."> Aufklappen ermöglichen</td></tr>";
					echo "
					<tr><td colspan=\"2\"><input type=\"radio\" name=\"typ\" value=\"1\"".$radio[1]."> Auf integriertes Plugin verweisen</td></tr>
					<tr><td>Plugin:</td><td><select onclick=\"check_radio(1)\" name=\"plugin\">";
					for($i=0;$i<count($plugin_intern);$i++)
					{
						$sel="";
						if($i==$plugin)
						{
							$sel=" selected";
						}
						echo "<option value=\"".$i."\" ".$sel.">".$plugin_intern[$i][0]."</option>";
					}
					echo "</select></td></tr>
					<tr><td colspan=\"2\"><input type=\"radio\" name=\"typ\" value=\"2\"".$radio[2]."> Link-Code verwenden</td></tr>
					<tr><td valign=\"center\">Link-Code:</td><td><textarea onclick=\"check_radio(2)\" name=\"extern\" rows=\"2\" cols=\"60\">";
					echo my_stripslashes($extern);
					echo "</textarea>
					<div class=\"example\">Beispiel: a href=\"http://www.beispiel.de/\" target=\"_blank\"</div></td></tr>
					<tr><td colspan=\"2\"><input type=\"radio\" name=\"typ\" value=\"3\"".$radio[3]."> Nur Platzhalter (Keine Link-Funktion)</td></tr>
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"visible\" value=\"1\"".$vis."> Eintrag ist sichtbar</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"menu\" value=\"Speichern\"></center></td></tr>
					</table>";
					echo '
					<script type="text/javascript">
					function check_radio(id)
					{
						document.pms_form.typ[id].checked=true;
					}
					</script>
					</form>';
				}
				else
				{
					echo heading("Menüverwaltung");
					echo '[<a href="admin.php?action='.$action.'&new=yes">Neuer Menüeintrag</a>]<br><br>';
					if($sort_do && $id_para)
					{
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."menu SET sort = '$sort_para' WHERE id = '$id_para' LIMIT 1;");
					}
					if($delete)
					{
						if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."menu WHERE id = ".$delete." LIMIT 1;"))
						$ok="Eintrag erfolgreich entfernt";
						else
						$error="Eintrag konnte nicht entfernt werden";
						ok_error();
					}
					echo '<table class="group">';
					echo table_header("Name:200px|Sortierung:90px|Link auf:70px|Sichtbar:60px|Bearbeiten:80px|Löschen:65px");
					$link=$pms_db_connection->query(make_sql("menu","","sort,name"));
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						if($a->typ==0)
						{
							$typ="Content";
						}
						if($a->typ==1)
						{
							$typ="Plugin";
						}
						if($a->typ==2)
						{
							$typ="Link-Code";
						}
						if($a->typ==3)
						{
							$typ="Platzhalter";
						}
						$visible="Nein";
						if($a->visible)
						{
							$visible="Ja";
						}
						if($i>0)
						{
							$sort_up="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($last-1)."&id=".$a->id."\">&uarr;</a>";
						}
						$sort_down="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($a->sort+1)."&id=".$last_id."\">&darr;</a>";
						$menu[$i][0]=$a->name;
						$menu[$i][1]=$a->sort." ".$sort_up;
						if($i>0)
						{
							$menu[$i-1][1]=$menu[$i-1][1].$sort_down;
						}
						$menu[$i][2]=$typ;
						$menu[$i][3]=$visible;
						$menu[$i][4]='<a href="admin.php?action='.$action.'&edit='.$a->id.'">Bearbeiten</a>';
						$menu[$i][5]='<a href="admin.php?action='.$action.'&delete='.$a->id.'">Löschen</a>';
						$last=$a->sort;
						$last_id=$a->id;
					}
					echo array_table($menu,5);
				}
			}
			if($action=="user")
			{
				if($edit && from_db("user",$edit,"typ")>from_db("user",$_SESSION['userid'],"typ"))
				{
					$error="Sie können keine Benutzer bearbeiten, die höhere Berechtigungen als Sie selbst haben!";
					ok_error();
					$no_edit=1;
				}
				if(($edit || $new || $post)&&!$no_edit)
				{
					if($new && !$post)
					{
						$active=1;
						$showmail=make_check(1);
						$top=make_check(1);
					}
					if($edit && !$post)
					{
						$name=from_db("user",$edit,"name");
						$mail=from_db("user",$edit,"mail");
						$typ=from_db("user",$edit,"typ");
						$bday=from_db("user",$edit,"bday");
						$website=from_db("user",$edit,"website");
						$signatur=stripslashes(from_db("user",$edit,"signatur"));
						if($bday)
						{
							$bday=date("d.m.Y",$bday);
						}
						else
						{
							unset($bday);
						}
						$image=from_db("user",$edit,"image");
						$active=from_db("user",$edit,"active");
						$top=make_check(from_db("user",$edit,"top"));
						$showmail=make_check(from_db("user",$edit,"showmail"));
					}
					if($active)
					{
						$active=" checked";
					}
					else
					{
						$active="";
					}
					if($edit)
					{
						$add='bearbeiten';
					}
					else
					{
						$add='erstellen';
					}
					echo heading("Benutzer ".$add)."
					".form()."<table>
					<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<tr><td>Name:</td><td><input type=\"text\" name=\"name\" value=\"".$name."\"></td></tr>
					<tr><td>Neues Passwort:</td><td><input type=\"password\" name=\"password\"></td></tr>
					<tr><td>Passwort wiederholen:</td><td><input type=\"password\" name=\"passwordr\"></td></tr>
					<tr><td>EMail-Adresse:</td><td><input type=\"text\" name=\"mail\" value=\"".$mail."\"></td></tr>
					<tr><td>Website:</td><td><input type=\"text\" name=\"website\" maxlength=\"128\" value=\"".$website."\"></td></tr>
					<tr><td>Signatur:</td><td><textarea cols=\"30\" rows=\"3\" name=\"signatur\" maxlength=\"150\">".$signatur."</textarea></td></tr>
					<tr><td>Benutzertyp:</td><td><select name=\"typ\">";
					for($i=$edit==$_SESSION['userid'] ? from_db("user",$_SESSION["userid"],"typ") : 0;$i<=from_db("user",$_SESSION['userid'],"typ");$i++)
					{
						$sel="";
						if($i==$typ)
						{
							$sel=" selected";
						}
						echo "<option value=\"".$i."\"".$sel.">".$user_typ[$i]."</option>";
					}
					echo "</select></td></tr>
					<tr><td>Geburtsdatum (z.b. 15.03.1985):</td><td><input type=\"text\" name=\"bday\" value=\"".$bday."\">
					<tr><td>Avatar wählen (jpg, gif, png):</td><td><input type=\"file\" name=\"image\">";
					if($image)
					{
						echo "</td></tr><tr><td>".make_contentimg("user",$edit,$image,0)."</td><td><input type=\"checkbox\" name=\"image_delete\" value=\"1\"> Aktuelles Bild löschen";
					}
					echo "
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"showmail\" value=\"1\" ".$showmail."> Die E-Mailadresse des Benutzers anzeigen</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"top\" value=\"1\" ".$top."> Benutzer ist in Top-Liste sichtbar</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"active\" value=\"1\" ".$active."> Benutzer ist aktiviert</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"user\" value=\"Speichern\"></center></td></tr></table>
					</form>";
				}
				else
				{
					if($delete)
					{
						if($delete && from_db("user",$delete,"typ")>from_db("user",$_SESSION['userid'],"typ"))
						{
							$error="Sie können keine Benutzer löschen, die höhere Berechtigungen als Sie selbst haben!";
							ok_error();
							$no_edit=1;
						}
						else
						{
							// später überprüfen, ob nicht eigener Benutzer!
							if($_SESSION['userid']==$delete)
							{
								$error="Sie können sich nicht selbst löschen!";
								ok_error();
							}
							else
							{
								del_contentimg("user",$delete,from_db("user",$delete,"image"));
								$name=from_db("user",$delete,"name");
								if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."user WHERE id = ".$delete." LIMIT 1;"))
								{
									$pms_db_connection->query("UPDATE ".$pms_db_prefix."comments SET user = '0', name = '$name' WHERE user = '$delete'");
									$ok="Benutzer erfolgreich entfernt";
								}
								else
								$error="Benutzer konnte nicht entfernt werden";
								ok_error();
							}
						}
					}
					echo heading("Benutzerverwaltung");
					echo '[<a href="admin.php?action='.$action.'&new=yes">Neuer Benutzer</a>]<br><br>';
					echo '<table class="group">';
					echo table_header("ID:30px|Name:100px|E-Mail:150px|Typ:120px|Letzter Login:90px|Registriert:80px|Registrations-IP:100px|Punkte:40px|Aktiviert:60px|Bearbeiten:80px|Löschen:65px");
					$link=$pms_db_connection->query(make_sql("user","LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.user=".$pms_db_prefix."user.id)",$pms_db_prefix."user.id",$pms_db_prefix."user.*,count(".$pms_db_prefix."comments.id)*60+".$pms_db_prefix."user.points as points",$pms_db_prefix."user.id",0));
					$num_user=0;
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						$act="Nein";
						if($a->active==1)
						{
							$act="Ja";
						}
						if($a->register!=0)
						{
							$register=date("d.m.Y",$a->register);
						}
						else
						{
							$register="-";
						}
						if($a->login!=0)
						{
							$login2=date("d.m.Y",$a->login);
						}
						else
						{
							$login2="-";
						}
						
						$menu[$i][0]=$a->id;
						$menu[$i][1]=$a->name;
						$menu[$i][2]="<a href=\"mailto:".$a->mail."\">".$a->mail."</a>";
						$menu[$i][3]=$user_typ[$a->typ];
						$menu[$i][4]=$login2;
						$menu[$i][5]=$register;
						$menu[$i][6]=$a->registerip;
						$menu[$i][7]=$a->points;
						$menu[$i][8]=$act;
						$menu[$i][9]="<a href=\"admin.php?action=user&edit=".$a->id."\">Bearbeiten</a>";
						$menu[$i][10]="<a href=\"admin.php?action=user&delete=".$a->id."\">Löschen</a>";
						$num_user=$i;
					}
					$num_user++;
					echo array_table($menu,10);
					echo '
					<br>'.$num_user.' Benutzer registriert.';
				}
			}
			if($action=="cat")
			{
				if($pms_db_use_reference)
				{
					if($new)
					{
						echo select_reference("Referenzkategorie wählen","Kategorie:","cat");
						unset($new);
						$select_reference=1;
					}
					else if($_GET["reference"])
					{
						if(copy_reference("cat",$_GET["reference"])) $edit=$_GET["reference"];
					}
				}
				if($new || $edit)
				{
					$sort=1000;
					$available=1;
					$list="";
					if($edit)
					{
						$name=from_db("cat",$edit,"name");
						$sort=from_db("cat",$edit,"sort");
						$list=from_db("cat",$edit,"list");
						$available=from_db("cat",$edit,"available");
					}
					if($available)
					{
						$available=" checked";
					}
					else
					{
						$available="";
					}
					$add="erstellen";
					if($edit)
					{
						$add="bearbeiten";
					}
					echo form().heading("Kategorie ".$add)."
					<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<table><tr><td>Kategoriename:</td><td><input type=\"text\" name=\"name\" value=\"".$name."\"></td></tr>
					<tr><td>Sortierung:</td><td><input type=\"text\" name=\"sort\" value=\"".$sort."\"></td></tr>";
					$a=get_lists($list);
					if($a) echo "<tr><td>Listenansicht:</td><td>".$a."</td></tr>";
					
					echo "
					<tr><td colspan=\"2\"><div align=\"center\"><input type=\"checkbox\" name=\"available\" value=\"1\"".$available."> Kategorie verfügbar</div></td></tr>
					<tr><td colspan=\"2\"><div align=\"center\"><input type=\"submit\" name=\"cat\" value=\"Speichern\"></div></td></tr>
					</table></form>";
				}
				else if(!$select_reference)
				{
					if($delete)
					{
						if(from_db("user",$_SESSION['userid'],"typ")>=3)
						{
							echo form().heading("Löschen von Kategorie bestätigen")."
							<input type=\"hidden\" name=\"id\" value=\"".$delete."\">
							Bitte bestätige das Löschen der Kategorie ".from_db("cat",$delete,"name").":<br>
							<br>
							<div class=\"example\">Hinweis: Es werden ALLE EINTRÄGE UND UNTERKATEGORIEN ENTFERNT!</div>
							<br>
							<br>
							<input type=\"submit\" name=\"cat_delete\" value=\"Kategorie Löschen!\">
							<br>
							<br></form>
							".back_button();
						}
						else
						{
							$error="Sie haben dafür nicht genügend Rechte!";
							ok_error();
							$delete=0;
						}
					}
					if(!$delete)
					{
						if($sort_do && $id_para)
						{
							$pms_db_connection->query("UPDATE ".$pms_db_prefix."cat SET sort='$sort_para' WHERE id = '$id_para' LIMIT 1;");
						}
						echo heading("Kategorien");
						echo '[<a href="admin.php?action='.$action.'&new=yes">Neue Kategorie</a>]<br><br>';
						echo '<table class="group">';
						echo table_header("ID:30px|Name:100px|Sortierung:90px|Verfügbar:60px|Bearbeiten:80px|Löschen:65px");
						$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
						for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
						{
							if($i>0)
							{
								$sort_up="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($last-1)."&id=".$a->id."\">&uarr;</a>";
							}
							$sort_down="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($a->sort+1)."&id=".$last_id."\">&darr;</a>";
							$available="Nein";
							if($a->available)
							{
								$available="Ja";
							}
							$menu[$i][0]=$a->id;
							$menu[$i][1]=$a->name;
							$menu[$i][2]=$a->sort." ".$sort_up;
							if($i>0)
							{
								$menu[$i-1][2]=$menu[$i-1][2].$sort_down;
							}
							$menu[$i][3]=$available;
							$menu[$i][4]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
							$menu[$i][5]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
							$last=$a->sort;
							$last_id=$a->id;
						}
						echo array_table($menu,5);
					}
				}
			}
			if($action=="subcat")
			{
				if($pms_db_use_reference)
				{
					if($new)
					{
						echo select_reference("Referenz-Unterkategorie wählen","Unterkategorie:","subcat");
						unset($new);
						$select_reference=1;
					}
					else if($_GET["reference"])
					{
						copy_reference("subcat",$_GET["reference"]);
						$edit=$_GET["reference"];
					}
				}
				if($new || $edit)
				{
					$sort=1000;
					$available=1;
					$list="";
					$cat=$subcat_filter;
					if($new)
					{
						if($_GET["cat"]) $cat=$_GET["cat"];
					}
					if($edit)
					{
						$name=from_db("subcat",$edit,"name");
						$description=from_db("subcat",$edit,"description");
						$image=from_db("subcat",$edit,"image");
						$hidden="";
						if(!$image)
						{
							foreach($supported_img as $img)
							{
								if(file_exists($image_path."subcat/".$edit.".".$img))
								{
									$hidden='<input type="hidden" name="image_add" value="'.$img.'">';
									$image=$img;
									break;
								}
							}
						}
						$sort=from_db("subcat",$edit,"sort");
						$cat=from_db("subcat",$edit,"cat");
						$list=from_db("subcat",$edit,"list");
						$available=from_db("subcat",$edit,"available");
						$jump=make_check(from_db("subcat",$edit,"jump"));
					}
					$add="erstellen";
					if($edit)
					{
						$add="bearbeiten";
					}
					$available=make_check($available);
					echo form().heading("Unterkategorie ".$add)."
					".$hidden."<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<table><tr><td>Bezeichnung:</td><td><input type=\"text\" name=\"name\" size=\"36\" value=\"".str_replace('"','&quot;',$name)."\"></td></tr>
					<tr><td>Beschreibung:</td><td><textarea rows=\"5\" cols=\"35\" name=\"description\">".str_replace('&','&amp;',$description)."</textarea></td></tr>
					<tr><td>Bild (optional):</td><td><input type=\"file\" name=\"image\" size=\"36\"></td></tr>";
					if($image)
					{
						echo "<tr><td>".make_contentimg("subcat",$edit,$image,0)."</td><td><input type=\"checkbox\" name=\"image_delete\" value=\"1\"> Aktuelles Bild löschen</td></tr>";
					}
					echo "
					<tr><td>Sortierung:</td><td><input type=\"text\" name=\"sort\" size=\"7\" value=\"".$sort."\"></td></tr>
					<tr><td>In Kategorie:</td><td><select name=\"uppcat\">";
					$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
					while($link && $row=$pms_db_connection->fetchObject($link))
					{
						$sel="";
						if($row->id==$cat)
						{
							$sel=" selected";
						}
						echo "<option value=\"".$row->id."\"".$sel.">".$row->name."</option>";
					}
					echo "</select></td></tr>";
					$a=get_lists($list);
					if($a)
					echo "<tr><td>Listenansicht:</td><td>".$a."</td></tr>";
					
					echo "
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"available\" value=\"1\"".$available."> Unterkategorie verfügbar</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"jump\" value=\"1\"".$jump."> Wenn nur 1 Inhalt vorhanden, sofort auf diesen springen</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"subcat\" value=\"Speichern\"></center></td></tr>
					</table></form>";
				}
				else if(!$select_reference)
				{
					if($delete)
					{
						if(from_db("user",$_SESSION['userid'],"typ")>=3)
						{
							echo form().heading("Löschen von Unterkategorie bestätigen")."
							<input type=\"hidden\" name=\"id\" value=\"".$delete."\">
							Bitte bestätige das Löschen der Unterkategorie ".from_db("subcat",$delete,"name").":<br>
							<br>
							<div class=\"example\">Hinweis: Es werden ALLE EINTRÄGE ENTFERNT!</div>
							<br>
							<br>
							<input type=\"submit\" name=\"subcat_delete\" value=\"Unterkategorie Löschen!\">
							<br>
							<br></form>
							".back_button();
						}
						else
						{
							$error="Sie haben dafür nicht genügend Rechte!";
							ok_error();
							$delete=0;
						}
					}
					if(!$delete)
					{
						if($sort_do && $id_para)
						{
							$pms_db_connection->query("UPDATE ".$pms_db_prefix."subcat SET sort='$sort_para' WHERE id = '$id_para' LIMIT 1;");
						}
						echo heading("Unterkategorien");
						echo '[<a href="admin.php?action='.$action.'&new=yes">Neue Unterkategorie</a>]<br><br>';
						echo form()."Zeige nur Unterkategorien der Kategorie <select name=\"uppcat\"><option value=\"0\">[Alle]</option>";
						$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
						if($subcat_filter && !from_db("cat",$subcat_filter,"id"))
						{
							unset($subcat_filter);
						}
						while($link && $a=$pms_db_connection->fetchObject($link))
						{
							$sel="";
							if($a->id==$subcat_filter)
							{
								$sel=" selected";
							}
							echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
						}
						echo '</select> <input type="submit" name="subcat_filter" value="OK"><br><br></form><table class="group">';
						echo table_header("ID:30px|Name:100px|In Kategorie:100px|Sortierung:90px|Verfügbar:60px|Bearbeiten:80px|Löschen:65px");
						$filter="";
						if($subcat_filter)
						{
							$filter="cat = ".$subcat_filter;
						}
						$link=$pms_db_connection->query(make_sql("subcat",$filter,"sort,name"));
						for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
						{
							if($i>0)
							{
								$sort_up="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($last-1)."&id=".$a->id."\">&uarr;</a>";
							}
							$sort_down="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($a->sort+1)."&id=".$last_id."\">&darr;</a>";
							$available="Nein";
							if($a->available)
							{
								$available="Ja";
							}
							$menu[$i][0]=$a->id;
							$menu[$i][1]=$a->name;
							$menu[$i][2]=from_db("cat",$a->cat,"name");
							$menu[$i][3]=$a->sort." ".$sort_up;
							if($i>0)
							{
								$menu[$i-1][3]=$menu[$i-1][3].$sort_down;
							}
							$menu[$i][4]=$available;
							$menu[$i][5]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
							$menu[$i][6]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
							$last=$a->sort;
							$last_id=$a->id;
						}
						echo array_table($menu,6);
					}
				}
			}
			if($action=="item_restore" && $_POST["do_restore"])
			{
				$action="item";
				$found=recover_item($_POST["item_select"],0);
				if($pms_db_connection->query(str_replace("\\r\\n", "\r\n", $found[$_POST["date_select"]][1]))) $ok="Inhalt erfolgreich Wiederhergestellt"; else $error="Fehler beim Wiederherstellen des Inhalts";
				ok_error();
			}
			if($action=="item_restore")
			{
				$ok=0;
				$step=0;
				if($_POST["item_restore"])$step=1;
				echo heading("Gelöschten Inhalt Wiederherstellen");
				$link=$pms_db_connection->query(make_sql("item","","id","id"));
				if($step)
				{
					$found=recover_item($_POST["item_select"],0);
				}
				else
				{
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						$items[$i]=$a->id;
					}
					$found=recover_item(0,1,$items);
				}
				$i=0;
				echo form();
				if($step)
				{
					$list_name="date_select";
					if($found)
					{
						$b_caption="Wiederherstellen";
						$ok=1;
						echo '<input type="hidden" name="item_select" value="'.$_POST["item_select"].'">
						<input type="hidden" name="do_restore" value="1">
						Bitte wählen Sie ein Datum aus, um das Inhaltsobjekt <b>'.$found[0][3].'</b> (ID: '.$found[0][2].') wiederherzustellen';
					}
					else
					{
						echo 'Es ist ein Fehler aufgetreten.';
					}
				}
				else
				{
					$list_name="item_select";
					if($found)
					{
						$b_caption="Weiter";
						echo 'Bitte wählen Sie aus der folgenden Liste das Inhaltsobjekt';
						$ok=1;
					}
					else
					{
						echo 'In den Backups wurden keine Inhalte gefunden, welche gelöscht wurden!';
					}
				}
				if($ok)
				{

					echo ':<br><br>
					<select name="'.$list_name.'">';
					if($step)
					{
						foreach($found as $a)
						{
							echo '<option value="'.$i.'">'.$a[0].'</option>
							';
							$i++;
						}
					}
					else
					{
						$ids_shown=[];
						foreach($found as $a)
						{
							if(@in_array($a[2],$ids_shown)) continue;
							unset($sel);
							echo '<option value="'.$a[2].'"'.$sel.'>(ID: '.$a[2].') '.$a[3].'</option>
							';
							$ids_shown[]=$a[2];
						}
					}
					echo '</select><br><br>';
					echo '<input type="submit" name="item_restore" value="'.$b_caption.'">';
					echo '</form>';
				}
			}
			if($action=="item_recover" && from_db("item",$_GET["item"],"id"))
			{
				if($_GET["do_recover"] && $_GET["item"])
				{
					$ok=0;
					if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."item WHERE id = '".$_GET["item"]."'"))
					{
						$back=recover_item($_GET["item"]);
						if($pms_db_connection->query(str_replace("\\r\\n", "\r\n", $back[$_GET["recover_id"]][1]))) $ok=1;
					}
					$action="item";
					if($ok) $ok="<div align=\"center\">Inhalt erfolgreich zurückgesetzt<br><a href=\"index.php?item=".$_GET["item"]."\">Inhalt anzeigen</a></div>"; else $error="Fehler beim Wiederherstellen des Inhalts!";
					ok_error();
				}
				else
				{
					echo heading("Inhalt Wiederherstellen").'Wählen Sie aus der Liste unten eine Backup-Version für den Inhalt "<a href="admin.php?action=item&edit='.$_GET["item"].'">'.from_db("item",$_GET["item"],"name").'</a>" aus.
					<br>Klicken Sie dazu einfach auf das gewünschte Datum.
					<br><br>
					<b>Achtung!</b> Die aktuelle Version wird verworfen! Falls Sie dies nicht möchten, legen Sie bitte vorher ein <a href="admin.php?action=backup">Backup</a> an!
					<br><br>
					<table class="group">'.table_header("Datum - Uhrzeit:120px|Wiederherstellen:120px");
					$back=recover_item($_GET["item"]);
					$i=0;
					foreach($back as $a)
					{
						$back_new[$i][0]=$a[0];
						$back_new[$i][1]='<a href="admin.php?action=item_recover&item='.$_GET["item"].'&do_recover=yes&recover_id='.$i.'">Wiederherstellen</a></td></tr>';
						$i++;
					}
					echo array_table($back_new,1);
				}
			}
			if($action=="item")
			{
				if($pms_db_use_reference)
				{
					if($new)
					{
						echo select_reference("Referenzinhalt wählen","Inhalt:","item");
						unset($new);
						$select_reference=1;
					}
					else if($_GET["reference"])
					{
						copy_reference("item",$_GET["reference"]);
						$edit=$_GET["reference"];
					}
				}
				if($_GET["do_copy"])
				{
					$error="Fehler beim Kopieren des Inhalts";
					$link=$pms_db_connection->query(make_sql("item","id = '".$_GET["do_copy"]."'","id"));
					if($link && $a=$pms_db_connection->fetchObject($link))
					{
						$str="";
						foreach($a as $key=>$val)
						{
							if($key=="id" || $key=="rating" || $key=="numratings") continue; // no id select
							if($str) $str.=",";
							$str.=$key;
						}
						if($pms_db_connection->query("INSERT INTO ".$pms_db_prefix."item (".$str.") SELECT ".$str." FROM ".$pms_db_prefix."item WHERE id = '".$_GET["do_copy"]."'"))
						{
							$a = $pms_db_connection->lastInsertId();
							if($link && $a)
							{
								$name=addslashes(from_db("item",$a,"name"))." - Kopie";
								$pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET name = '".$name."' WHERE id = '".$a."'");
								$typ=from_db("item",$_GET["do_copy"],"image");
								@copy($image_path."item/".$_GET["do_copy"].".".$typ,$image_path."item/".$a.".".$typ);
								@copy($image_path."item/".$_GET["do_copy"]."_large.".$typ,$image_path."item/".$a."_large.".$typ);
								unset($error);
								$ok="Inhalt erfolgreich kopiert";
							}
						}
					}
					ok_error();
				}
				if($new || $edit || $post)
				{
					if($new || $post==1 || ($edit && !$post))
					{
						if($new)
						{
							$cat=$item_filter;
							$subcat=$item_filter2;
							if($_GET["cat"])
							{
								$cat=$_GET["cat"]*1;
							}
							if($_GET["subcat"])
							{
								$subcat=$_GET["subcat"]*1;
								$cat=from_db("subcat",$_GET["subcat"],"cat");
							}
							if($sort_do)
							{
								$sort=$sort_do;
							}
							if($_GET["name"])
							{
								$name=$_GET["name"];
							}
						}
						if($post==1)
						{
							$cat=(int)$_POST['cat'];
							$subcat=(int)$_POST['subcat'];
							$typ=$_POST['typ'];
							$sort=$_POST['sort'];
							$name=$_POST['name'];
						}
						if($cat) $cat=from_db("cat",$cat,"id");
						if($subcat) $subcat=from_db("subcat",$subcat,"id");
						if($edit && !$post)
						{
							$cat=from_db("item",$edit,"cat");
							$subcat=from_db("item",$edit,"subcat");
							$typ=from_db("item",$edit,"typ");
							$typ2=from_db("item",$edit,"special");
						}
						$do="hinzufügen";
						if($edit)
						{
							$do="bearbeiten";
						}
						echo form()."<h2>Inhalt ".$do." - Vorauswahl</h2>
						<table>
						<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
						<input type=\"hidden\" name=\"sort\" value=\"".$sort."\">
						<input type=\"hidden\" name=\"name\" value=\"".$name."\">
						<tr><td>Typ des Inhalts:</td><td><select name=\"typ\">";
						for($i=0;$i<count($content_typ);$i++)
						{
							$sel="";
							if($i==$typ)
							{
								$sel=" selected";
							}
							echo "<option value=\"".$i."\"".$sel.">".$content_typ[$i]."</option>";
						}
						echo "</select></td></tr>";
						if($typ!=3)
						{
							$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
							$cats = $pms_db_connection->fetchAllObject($link);
							if(count($cats))
							{
								echo "
								<tr><td>In Kategorie:</td><td><select name=\"cat\">";
								foreach($cats as $a)
								{
									$sel="";
									if($a->id==$cat)
									{
										$sel=" selected";
									}
									echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
								}
								echo "</select>";
							}
							else
							echo warning_box("Sie können keinen Inhalt erstellen, da noch keine Kategorien existieren.<br>Wählen Sie als Typ \"Spezialseite\" oder <a href=\"admin.php?action=cat&new=yes\">erstellen Sie eine Kategorie</a>.");
						}
						else
						{
							echo "<tr><td>Art des Spezialinhalts: </td><td><select name=\"typ2\">";
							for($i=1;$i<=count($special_typ);$i++)
							{
								$sel="";
								if($i==$typ2)
								{
									$sel=" selected";
								}
								echo "<option value=\"".$i."\"".$sel.">".$special_typ[$i]."</option>";
							}
						}
						echo " <input type=\"submit\" name=\"item_refresh\" value=\"Aktualisieren\"></td></tr>";
						if($cat && $typ!=3)
						{
							$link=$pms_db_connection->query(make_sql("subcat","cat = '$cat'","sort,name"));
							$subcats = $pms_db_connection->fetchAllObject($link);
							if(count($subcats))
							{
								echo "<tr><td>In Unterkategorie:</td><td><select name=\"subcat\">";
								foreach($subcats as $a)
								{
									$sel="";
									if($a->id==$subcat)
									{
										$sel=" selected";
									}
									echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
								}
								echo "</select></td></tr>";
							}
							else
							{
								echo warning_box("Sie können in der Kategorie ".from_db("cat",$cat,"name")." keinen Inhalt hinzufügen, da es noch keine Unterkategorien gibt.<br>Wählen Sie eine andere Kategorie und Klicken Sie auf \"Aktualisieren\", oder <a href=\"admin.php?action=subcat&cat=".$cat."&new=yes\">legen Sie eine neue Unterkategorie an.</a>");
								unset($cat);
							}
						}
						if($cat || $typ==3)
						{
							echo "
							<tr><td colspan=\"2\"><div align=\"center\">";
							echo "<input type=\"hidden\" name=\"tinymce_vis\" value=\"1\">";
							echo "<input type=\"checkbox\" name=\"tinymce\" value=\"1\"".make_check(isset($_GET["tinymce"])?$_GET["tinymce"] : $_SESSION['tinymce']-1)."> Grafischen HTML-Editor (TinyMCE) verwenden</div></td></tr>
							<tr><td colspan=\"2\"><div align=\"center\"><input type=\"submit\" name=\"item_step1\" value=\"Weiter\"></div></td></tr>";
						}
						echo "</form></table>";
					}
					else
					{
						$available=1;
						$visible=1;
						$rate=1;
						$comments=1;
						$showuser=1;
						if($edit)
						{
							$name=from_db("item",$edit,"name");
							$description=from_db("item",$edit,"description");
							$content=from_db("item",$edit,"content");
							/*$cat=from_db("item",$edit,"cat");
							$subcat=from_db("item",$edit,"subcat");
							$typ=from_db("item",$edit,"typ");*/
							$sort=from_db("item",$edit,"sort");
							$image=from_db("item",$edit,"image");
							$hidden="";
							if(!$image)
							{
								foreach($supported_img as $img)
								{
									if(file_exists($image_path."item/".$edit.".".$img))
									{
										$hidden='<input type="hidden" name="image_add" value="'.$img.'">';
										$image=$img;
										break;
									}
								}
							}
							$available=from_db("item",$edit,"available");
							$visible=from_db("item",$edit,"visible");
							$showuser=from_db("item",$edit,"showuser");
							$link_f=from_db("item",$edit,"link");
							$user=from_db("item",$edit,"user");
							$rate=from_db("item",$edit,"rate");
							$comments=from_db("item",$edit,"comments");
							if($_POST["typ"]==1) del_contentimg("item",$edit,$image,0);
						}
						else
						{
							$sort=$_POST['sort'];
							$name=$_POST['name'];
						}
						if(!$sort && !$edit)
						{
							$sort=1000;
						}
						if($post==2 && !$edit)
						{
							$cat=$_POST['cat'];
							$subcat=$_POST['subcat'];
							$typ=$_POST['typ'];
							$available=1;
							$visible=1;
							$user=$_SESSION['userid'];
							if($typ2==4) // guestbook
							$name="Gästebuch";
						}
						$available=make_check($available);
						$visible=make_check($visible);
						$showuser=make_check($showuser);
						$rate=make_check($rate);
						$comments=make_check($comments);
						$add="erstellen";
						if($edit)
						{
							$add="bearbeiten";
						}
						
						echo '
						<script type="text/javascript">
						function disable_buttons()
						{
							document.getElementById("item_button1").style.display="none";
							document.getElementById("item_button2").style.display="none";
							if(document.getElementById("add_image")) document.getElementById("add_image").style.visibility="hidden";
							document.getElementById("item_save").style.display="";
						}
						function refresh_create()
						{
							document.getElementById("create_at_date").disabled=document.getElementById("create_at_use").checked;
							document.getElementById("create_at_time").disabled=document.getElementById("create_at_use").checked;
						}
						</script>';
						
						echo form("disable_buttons()").heading("Inhalt ".$add)."
						<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
						<input type=\"hidden\" name=\"cat\" value=\"".$cat."\">
						<input type=\"hidden\" name=\"subcat\" value=\"".$subcat."\">
						<input type=\"hidden\" name=\"typ\" value=\"".$typ."\">
						<input type=\"hidden\" name=\"typ2\" value=\"".$typ2."\">".$hidden;
						echo "<table>";
						if($edit)
						{
							echo "<tr><td colspan=\"2\" align=\"center\"><a href=\"index.php?item=".$edit."\" target=\"blank\">Seite in neuem Fenster anzeigen</a></td></tr>";
							if(recover_item($edit)) echo "<tr><td colspan=\"2\" align=\"center\"><a href=\"admin.php?action=item_recover&item=".$edit."\">Version wiederherstellen...</a></td></tr>";
						}
						echo "<tr><td>Titel:</td><td>";
						
						echo "<input type=\"text\" name=\"name\" size=\"36\" value=\"".str_replace('"','&quot;',$name)."\">";
						
						echo "</td></tr>
						<tr><td>Kurzbeschreibung:<br>(Optional)</td><td><textarea rows=\"5\" cols=\"35\" name=\"description\">".str_replace('&','&amp;',$description)."</textarea></td></tr>";
						if($edit)
						{
							$create_date=from_db("item",$edit,"time");
							$create_date_str="Nicht verändern";
						}
						else
						{
							$create_date=time();
							$create_date_str="Automatisch";
						}
						echo "<tr><td>Erstellungsdatum:</td><td>
						<input type=\"text\" name=\"create_at_date\" size=\"7\" maxlength=\"10\" id=\"create_at_date\" value=\"".date("d.m.Y",$create_date)."\" disabled=\"true\"> 
						<input type=\"text\" name=\"create_at_time\" size=\"3\" maxlength=\"5\" id=\"create_at_time\" value=\"".date("H:i",$create_date)."\" disabled=\"true\"> 
						<input type=\"checkbox\" onclick=\"refresh_create()\" id=\"create_at_use\" name=\"create_at_use\" value=\"1\" checked>".$create_date_str."
						</td></tr>";
						
						if($typ2==2 || $typ2==5)
						{
							echo "<tr><td>Folgende Platzhalter sind möglich:</td><td>";
							if($typ2==2)
							{
								echo "#id (ID des Downloads), #file (Name des Downloads), #button (Download-Button)";
							}
							else
							{
								echo "#ip (IP-Adresse des Nutzers, #reason (Begründung des Bans), #time (Zeitlimit des Bans)";
							}
							echo "</td></tr>";
						}
						
						if($typ==2)
						{
							echo "<tr><td>Download-Link:</td><td><input type=\"text\" name=\"link\" size=\"36\" value=\"".str_replace('"','&quot;',$link_f)."\"></td></tr>";
						}
						echo "<tr><td>Bild (optional):</td><td><input type=\"file\" size=\"36\" name=\"image\"></td></tr>";
						if($typ!=1)
						{
							echo "<tr><td colspan=\"2\">Mit #item_picture können Sie das gewählte Bild einfügen, andernfalls wird die Position automatisch bestimmt.
							</td></tr><tr><td></td><td>
							<a href=\"javascript:toogle_pic();\">Weitere Bild-Optionen</a>
							<div id=\"pic_extended\" style=\"display:none;\"><input type=\"checkbox\" name=\"full_image\" value=\"1\"> Originalbild Speichern und verlinken</div>";
							echo '<script type="text/javascript">
							function toogle_pic()
							{
								if(document.getElementById("pic_extended").style.display=="none") document.getElementById("pic_extended").style.display="";
								else document.getElementById("pic_extended").style.display="none";
							}
							</script>';
						}
						if($image)
						{
							echo "<tr><td>".make_contentimg("item",$edit,$image,0)."</td><td><input type=\"checkbox\" name=\"image_delete\" value=\"1\"> Aktuelles Bild löschen</td></tr>";
						}
						echo "
						<tr><td>Sortierung:</td><td><input type=\"text\" name=\"sort\" size=\"7\" value=\"".$sort."\"></td></tr>";
						if($typ2!=4)
						{
							echo "<tr><td>Autor:</td><td><select name=\"user\">";
							$link=$pms_db_connection->query(make_sql("user","","id"));
							while($link && $a=$pms_db_connection->fetchObject($link))
							{
								$sel="";
								if($a->id==$user)
								{
									$sel=" selected";
								}
								echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
							}
							echo "</select>";
						}
						if(!$edit_string_replace) // cleanup any image_temp
						{
							$content=cleanup_content($content);
						}
						else $content=str_replace('<img id="##pms_replace_image_temp"','<img src="'.$edit_string_use.'"',$content);
						echo "
						<tr><td colspan=\"2\"><center>Inhalt:</center></td></tr>
						<tr><td colspan=\"2\"><center><textarea rows=\"22\" cols=\"95\" id=\"content\" name=\"content\">".str_replace('&','&amp;',$content)."</textarea>
						</center></td></tr>";
						if($_SESSION['tinymce']==2) echo "<tr><td colspan=\"2\">
						".'<input type="hidden" name="next" id="next" value="">
						<input type="hidden" name="add_image" id="add_image" value="">
						<input type="hidden" name="item" id="item" value="">
						<input type="hidden" name="drag_name" id="drag_name" value="">
						<input type="hidden" name="drag_data" id="drag_data" value="">
						<script type="text/javascript">
						var item_id='.($edit ? $edit : 0).';
						function add_image(v)
						{
							var temp=tinyMCE.activeEditor.getContent();
							var img=\'<img id="##pms_replace_image_temp" alt="" />\';
							try{
								tinyMCE.activeEditor.execCommand(\'mceInsertContent\',false,tinyMCE.activeEditor.selection.getContent()+img);
								if(tinyMCE.activeEditor.getContent().trim()=="<p>"+img+"</p>") tinyMCE.activeEditor.setContent(img+temp);
							}
							catch(e){}
							document.getElementById("next").value=v;
							document.getElementById("item_button2").click();
						}
						</script>
						'."
						<div class=\"drop_zone\" id=\"drop_zone\">Ziehen Sie eine Bild-Datei von Ihrem Explorer in dieses Feld,<br>um Sie auf der Seite einzufügen.</div>
						
						<div align=\"center\" id=\"add_image\">[<a href=\"javascript:add_image('image')\">Bild einfügen</a>]</div>";
						
						echo "
						<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"available\" value=\"1\"".$available."> Inhalt verfügbar (Zugriff erlaubt, Administratoren haben immer Zugriff)</center></td></tr>";
						if($typ!=3)
						{
							echo "
							<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"visible\" value=\"1\"".$visible."> Inhalt ist sichtbar (Inhalt wird in Liste gezeigt, Administratoren sehen alle Inhalte)</center></td></tr>";
						}
						if($typ!=4 && $typ2!=4)
						{
							echo "
							<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"showuser\" value=\"1\"".$showuser."> \"Geschrieben von...\" anzeigen</center></td></tr>";
							if($typ2!=5)
							{
								echo "
								<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"rate\" value=\"1\"".$rate."> Inhalt darf bewertet werden</center></td></tr>
								<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"comments\" value=\"1\"".$comments."> Inhalt darf kommentiert werden</center></td></tr>";
							}
						}
						echo "
						<tr><td colspan=\"2\"><div align=\"center\"><input type=\"submit\" id=\"item_button1\" name=\"item_step2\" value=\"Übernehmen\"> <input type=\"submit\" id=\"item_button2\" name=\"item_step2\" value=\"Übernehmen & Schließen\">
						<div id=\"item_save\" style=\"display:none;font-weight:bold;height:26px;vertical-align:bottom;\">Bitte Warten, Inhalt wird gespeichert...</div></div></td></tr>
						</table></form>";
					}
				}
				else if($add_image) 
				{
					echo '<table style="border:1px solid #A0A0A0;"><tr><td>'.heading("Bild einfügen").form("disable_buttons()").
					'<script type="text/javascript">
					function disable_buttons()
					{
						document.getElementById("button1").style.display="none";
						document.getElementById("image_upload").style.display="";
					}
					function show_image(a,b,c)
					{
						document.getElementById("image_preview_div").style.display="";
						document.getElementById("image_preview_div").style.left=tempX+18;
						document.getElementById("image_preview_div").style.top=tempY+18;
						document.getElementById("image_preview").src="images/uploads/"+a;
						document.getElementById("image_preview").width=b;
						document.getElementById("image_preview").height=c;
					}
					function hide_image()
					{
						document.getElementById("image_preview_div").style.display="none";
					}
					function delete_info(a)
					{
						if(confirm("Möchten Sie das Bild \'"+a+"\' wirklich löschen?\nHinweis: Diese Aktion kann nicht rückgängig gemacht werden!"))
						location="admin.php?action=add_image&item='.$add_image.'&delete="+a;
					}
					</script>
					<input type="hidden" name="item" value="'.$add_image.'">
					<table><tr><td>Bilddatei wählen:<br>(jpg, png, gif)</td><td><input type="file" name="image"></td></tr>
					<tr><td colspan="2"><div align="center" id="image_upload" style="display:none;font-weight:bold;height:26px;vertical-align:bottom;">Bitte Warten, Bild wird Hochgeladen...</div>
					<div align="center" id="button1"><input type="submit" name="add_image" value="Bild Hinzufügen">
					<br><br>
					[<a href="admin.php?action=add_image&item='.$add_image.'&abort=yes">Abbrechen</a>]
					</div>
					<td></tr></table>
					</td></tr></table><br>oder<br><br>
					
					<div id="image_preview_div" style="display:none;position:absolute;background-color:#ffffff;border:1px solid #aaaaaa;"><img id="image_preview"></div>
					<table style="border:1px solid #A0A0A0;"><tr><td>'.heading("Existierendes Bild verwenden").'
					Klicken Sie auf den Namen eines Bildes, um es zu verwenden.';
					$dir=@opendir("images/uploads/");
					if($dir)
					{
						echo '<table class="group">'.table_header("Bild:305px|Löschen:60px");
						$i=0;
						while(($file=@readdir($dir)) !== FALSE)
						{
							if($file=="." || $file=="..") continue;
							$files[$i++]=$file;
						}
						@closedir($dir);
						@usort($files,function($a,$b) {return strcasecmp($a,$b);});
						for($i=0;$i<count($files);$i++)
						{
							$s=@getimagesize("images/uploads/".$files[$i]);
							$file=$files[$i];
							$w=get_size($s,320,240,1);
							$h=get_size($s,320,240,2);
							unset($a);
							if($i%2==0) $a=" style=\"background-color:#ffffff;\"";
							echo '<tr'.$a.'><td><a href="admin.php?action=add_image&image='.$file.'&item='.$add_image.'" onmouseout="hide_image()" onmouseover="show_image(\''.$file.'\','.$w.','.$h.')">'.$files[$i].'</a></td><td><a href="javascript:delete_info(\''.$files[$i].'\')">Löschen</a></td></tr>';
						}
						echo '</table>';
					}
					echo '</td></tr></table></form>';
				}
				else if($add_image2 && $add_image2_img)
				{
					$size=@getimagesize("images/uploads/".$add_image2_img);
					
					echo heading("Bild anpassen").form("disable_buttons()").'
					<script type="text/javascript">
					var factor='.($size[1] ? $size[0]/$size[1] : 1).';
					function refresh_img()
					{
						document.getElementById("image_scale").width=document.getElementById("image_width").value;
						document.getElementById("image_scale").height=document.getElementById("image_height").value;
					}
					function min_size()
					{
						var warn=0;
						if(document.getElementById("image_width").value<1)
						{
							alert("Es muss eine gültige Bildbreite eingegeben werden!");
							warn=1;
							document.getElementById("image_width").value=1;
						}
						if(document.getElementById("image_height").value<1)
						{
							if(!warn) alert("Es muss eine gültige Bildhöhe eingegeben werden!");
							document.getElementById("image_height").value=1;
						}
					}
					function refresh_height(a)
					{
						if(document.getElementById("image_pro").checked) document.getElementById("image_height").value=parseInt(document.getElementById("image_width").value/factor);
						if(a) min_size();
						refresh_img();
					}
					function refresh_width(a)
					{
						if(document.getElementById("image_pro").checked) document.getElementById("image_width").value=parseInt(document.getElementById("image_height").value*factor);
						if(a) min_size();
						refresh_img();
					}
					function disable_buttons()
					{
						document.getElementById("button1").style.display="none";
						document.getElementById("button2").style.display="none";
						document.getElementById("image_save").style.display="";
					}
					</script>
					<input type="hidden" name="item" value="'.$add_image2.'">
					<input type="hidden" name="image" value="'.$add_image2_img.'">
					<table>
					<tr><td colspan="2"><div align="center">Breite: <input type="text" id="image_width" onkeyup="refresh_height(0)" onblur="refresh_height(1)" name="image_width" size="3" value="'.$size[0].'"> px - Höhe: 
					<input type="text" id="image_height" onkeyup="refresh_width(0)" onblur="refresh_width(1)"  name="image_height" size="3" value="'.$size[1].'"> px</div></td>
					</tr>
					<tr><td colspan="2"><div align="center"><input type="checkbox" onclick="refresh_height()" id="image_pro" value="1" checked> Proportionen beibehalten</div></td></tr>
					<tr><td colspan="2"><div align="center" id="image_save" style="display:none;font-weight:bold;height:52px;vertical-align:bottom;">Bitte Warten...</div>
					<div align="center"><input type="submit" id="button1" name="add_image2" value="Speichern &amp; Einfügen"></div></td></tr>
					<tr><td colspan="2"><div align="center" id="button2"><input type="submit" name="add_image2_abort" value="Abbrechen"></div></td></tr>
					
					<tr><td colspan="2">
					<div id="image_scale_div" align="center"><img id="image_scale" src="images/uploads/'.$add_image2_img.'"></div>
					</td></tr>
					</table>
					</form>';
				}
				else if(!$select_reference)
				{
					if($sort_do && $id_para)
					{
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET sort='$sort_para' WHERE id = '$id_para' LIMIT 1;");
					}
					if($delete)
					{
						del_contentimg("item",$delete,from_db("item",$delete,"image"));
						$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."comments WHERE item = '$delete'");
						if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."item WHERE id = '$delete' LIMIT 1;"))
						$ok="Inhalt erfolgreich entfernt";
						else
						$error="Inhalt konnte nicht entfernt werden";
						ok_error();
					}
					echo heading("Inhalte");
					echo '[<a href="admin.php?action='.$action.'&new=yes">Inhalt hinzufügen</a>]<br>[';
					$baks = get_backups();
					if($baks) {
						$num_backups=count($baks);
					}
					if($num_backups) echo '<a href="admin.php?action=item_restore">Gelöschten Inhalt Wiederherstellen</a>';else echo '<span class="disabled">Gelöschen Inhalt Wiederherstellen</span>';
					echo ']<br><br>';
					echo form()."Zeige nur Inhalte der Kategorie <select name=\"uppcat\"><option value=\"0\">[Alle]</option>";
					$link=$pms_db_connection->query(make_sql("cat","","sort,name"));
					while($link && $a=$pms_db_connection->fetchObject($link))
					{
						$sel="";
						if($a->id==$item_filter)
						{
							$sel=" selected";
						}
						echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
					}
					echo '</select>';
					if($item_filter)
					{
						echo " und Unterkategorie <select name=\"uppcat2\"><option value=\"0\">[Alle]</option>";
						$link=$pms_db_connection->query(make_sql("subcat","cat = '$item_filter'","sort,name"));
						while($link && $a=$pms_db_connection->fetchObject($link))
						{
							$sel="";
							if($a->id==$item_filter2)
							{
								$sel=" selected";
							}
							echo "<option value=\"".$a->id."\"".$sel.">".$a->name."</option>";
						}
						echo '</select>';
					}
					echo ' <input type="submit" name="item_filter" value="OK"><br><br></form>
					<table class="group">';
					echo table_header("ID:30px|Name:100px|Typ:80px|In Kategorie:100px|In Unterkategorie:100px|Sortierung:90px|Erstellt am:80px|Verfügbar:60px|Kopie erstellen:90px|Bearbeiten:80px|Löschen:65px");
					$filter="";
					if($item_filter)
					{
						$filter="cat = ".$item_filter;
					}
					if($item_filter2)
					{
						$filter2=" AND subcat = ".$item_filter2;
					}
					$link=$pms_db_connection->query(make_sql("item",$filter.$filter2,"sort,name"));
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						if($i>0)
						{
							$sort_up="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($last-1)."&id=".$a->id."\">&uarr;</a>";
						}
						$sort_down="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($a->sort+1)."&id=".$last_id."\">&darr;</a>";
						$available="Nein";
						if($a->available)
						{
							$available="Ja";
						}
						$typ=$content_typ[$a->typ];
						$menu[$i][0]=$a->id;
						$menu[$i][1]=$a->name;
						$menu[$i][2]=$typ;
						$menu[$i][3]=from_db("cat",$a->cat,"name");
						$menu[$i][4]=from_db("subcat",$a->subcat,"name");
						$menu[$i][5]=$a->sort." ".$sort_up;
						if($i>0)
						{
							$menu[$i-1][5]=$menu[$i-1][5].$sort_down;
						}
						$menu[$i][6]=date("d.m.Y",$a->time);
						$menu[$i][7]=$available;
						$menu[$i][8]=$a->typ==3 ? '<span class="disabled">Kopie erstellen</span>' : "<a href=\"admin.php?action=".$action."&do_copy=".$a->id."\">Kopie erstellen</a>";
						$menu[$i][9]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
						$menu[$i][10]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
						$last=$a->sort;
						$last_id=$a->id;
					}
					echo array_table($menu,10);
				}
			}
			
			if($action=="var")
			{
				if($pms_db_use_reference)
				{
					if($new)
					{
						echo select_reference("Referenzregel wählen","Regel:","dynamic","searcher","id","var","var");
						unset($new);
						$select_reference=1;
					}
					else if($_GET["reference"])
					{
						if(copy_reference("dynamic",$_GET["reference"])) $edit=$_GET["reference"];
					}
				}
				if($new || $edit)
				{
					$makebr=make_check(1);
					if($edit)
					{
						$search=from_db("dynamic",$edit,"searcher");
						$replace=from_db("dynamic",$edit,"replacer");
						$makebr=make_check(from_db("dynamic",$edit,"makebr"));
					}
					$add="erstellen";
					$add1="Neue ";
					if($edit)
					{
						$add1="";
						$add="bearbeiten";
					}
					echo form().heading($add1."Regel ".$add)."
					<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<table>
					<tr><td>Suchen:</td><td><textarea name=\"search\" rows=\"10\" cols=\"70\">".str_replace('&','&amp;',$search)."</textarea></td></tr>
					<tr><td>Ersetzen mit:</td><td><textarea name=\"replace\" rows=\"10\" cols=\"70\">".str_replace('&','&amp;',$replace)."</textarea></td></tr>
					<tr><td colspan=\"2\"><div align=\"center\"><input type=\"checkbox\" name=\"makebr\" value=\"1\"".$makebr."> Umbrüche mit \"&lt;br&gt;\" ersetzen.</div></td></tr>
					<tr><td colspan=\"2\"><div align=\"center\"><input type=\"submit\" name=\"var\" value=\"Speichern\"></div></td></tr>
					</table></form>".get_monaco();
				}
				elseif(!$select_reference)
				{
					if($delete)
					{
						if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."dynamic WHERE id = '$delete' LIMIT 1;"))
						$ok="Regel erfolgreich entfernt!";
						else
						$error="Fehler beim Löschen der Regel!";
						ok_error();
					}
					$poll_search=make_check($_SESSION['poll_search']);
					$poll_replace=make_check($_SESSION['poll_replace']);
					echo heading("Regeln verwalten");
					echo '[<a href="admin.php?action='.$action.'&new=yes">Neue Regel</a>]<br><br>';
					echo form()."<input type=\"checkbox\" name=\"poll_search\" value=\"1\"".$poll_search."> Zeige keine Such-Kriterien | <input type=\"checkbox\" name=\"poll_replace\" value=\"1\"".$poll_replace."> Zeige keine Ersetz-Kriterien <input type=\"submit\" name=\"poll_filter\" value=\"OK\"></form>";
					echo '<table class="group">';
					unset($search);unset($replace);
					if(!$_SESSION['poll_search'])
					{
						$search="Suche:150px|";
					}
					if(!$_SESSION['poll_replace'])
					{
						$replace="Ersetzen mit:150px|";
					}
					echo table_header("ID:30px|".$search.$replace."Bearbeiten:80px|Löschen:65px");
					$link=$pms_db_connection->query(make_sql("dynamic","","id"));
					$j=0;
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						$j=0;
						$menu[$i][$j]=$a->id;
						$j++;
						if(!$_SESSION['poll_search'])
						{
							$menu[$i][$j]=def($a->searcher);
							$j++;
						}
						if(!$_SESSION['poll_replace'])
						{
							$menu[$i][$j]=def(str_replace(array('<','>'),array('&lt;','&gt;'),$a->replacer));
							$j++;
						}
						$menu[$i][$j]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
						$j++;
						$menu[$i][$j]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
					}
					echo array_table($menu,$j);
				}
			}
			if($action=="bans")
			{
				if(from_db("user",$_SESSION['userid'],"typ")<3)
				{
					$error="Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!";
					ok_error();
				}
				else
				{
					if($new || $edit)
					{
						unset($time);
						if($edit)
						{
							$ip=from_db("bans",$edit,"ip");
							$reason=from_db("bans",$edit,"reason");
							$time=ban_time(from_db("bans",$edit,"time"));
							if($time=="Unbegrenzt")
							{
								unset($time);
							}
						}
						$add="erstellen";
						$add1="Neuen ";
						if($edit)
						{
							$add1="";
							$add="bearbeiten";
						}
						echo form().heading($add1."Ban ".$add)."
						<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
						<table>
						<tr><td>IP:</td><td><input type=\"text\" maxlength=\"15\" name=\"ip\" value=\"".$ip."\"></td></tr>
						<tr><td>Begründung (Optional):</td><td><textarea name=\"reason\" rows=\"5\" cols=\"19\">".str_replace('&','&amp;',$reason)."</textarea></td></tr>
						<tr><td>Zeitlimit in Tagen (0 = Kein Limit):</td><td><input type=\"text\" size=\"3\" name=\"time\" value=\"".$time."\"></td></tr>
						<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"bans\" value=\"Speichern\"></center></td></tr>
						</table></form>";
					}
					else
					{
						if($delete)
						{
							if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."bans WHERE id = '$delete' LIMIT 1;"))
							$ok="Ban erfolgreich entfernt!";
							else
							$error="Fehler beim Löschen des Bans!";
							ok_error();
						}
						echo heading("Bans verwalten");
						echo '[<a href="admin.php?action='.$action.'&new=yes">Neuer Ban</a>]<br><br>';
						echo '<table class="group">';
						echo table_header("ID:30px|IP:100px|Begründung:200px|Verbleibende Dauer<br>(in Tagen):140px|Bearbeiten:80px|Löschen:65px");
						$link=$pms_db_connection->query(make_sql("bans","","id"));
						for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
						{
							$menu[$i][0]=$a->id;
							$menu[$i][1]=$a->ip;
							$menu[$i][2]=def($a->reason);
							$menu[$i][3]=ban_time($a->time);
							$menu[$i][4]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
							$menu[$i][5]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
						}
						echo array_table($menu,5);
					}
				}
			}
			if($action=="config")
			{
				if(from_db("user",$_SESSION['userid'],"typ")<3)
				{
					$error="Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!";
					ok_error();
				}
				else
				{
					$link=$pms_db_connection->query(make_sql("config","","id"));
					if($link)
					{
						$a=$pms_db_connection->fetchObject($link);
						$title=make_check($a->title);
						$rate=make_check($a->rate);
						$comments=make_check($a->comments);
						$predownload=make_check($a->predownload);
						$vertical=make_check($a->vertical);
						unset($menu_mode);
						$menu_mode[$a->menu_mode]=" checked";
						if($a->menu_mode) $menu_disabled=' style="display:none;"';
						if(!$vertical)
						{
							$horizontal=make_check(1);
						}
						echo form().heading("Website-Konfiguration")."<table width=\"600px\" class=\"config_table\">";
						echo '
						<script type="text/javascript">
						function menu_preview()
						{
							document.getElementById(10).style.display="none";
							document.getElementById(11).style.display="none";
							if(document.pms_form.vertical[1].checked)
							{
								document.getElementById(10).style.display="";
							}
							else
							{
								document.getElementById(11).style.display="";
							}
							for(var i=0;document.getElementById(i);i++)
							{
								document.getElementById(i).style.width=document.pms_form.menu_width.value;
								document.getElementById(i).style.height=document.pms_form.menu_height.value;
							}
						}
						setTimeout("menu_preview();",10);
						var last_menu='.$a->menu_mode.';
						function set_mode()
						{
							if(document.pms_form.menu_mode[0].checked && last_menu)
							{
								document.getElementById("menu_id_1").style.display="";
								document.getElementById("menu_id_2").style.display="";
								document.getElementById("menu_id_3").style.display="";
								document.getElementById("menu_id_4").style.display="";
							}
							else if(!last_menu)
							{
								alert("Bitte beachten Sie: Im erweiterten Modus muss eine spezielle CSS-Konfiguration existieren!");
								document.getElementById("menu_id_1").style.display="none";
								document.getElementById("menu_id_2").style.display="none";
								document.getElementById("menu_id_3").style.display="none";
								document.getElementById("menu_id_4").style.display="none";
							}
							last_menu=document.pms_form.menu_mode[1].checked;
						}
						</script>
						';
						echo config_space("Allgemeines")."
						<tr><td>Website-Name:</td><td><input type=\"text\" name=\"name\" size=\"30\" value=\"".$a->name."\"></td></tr>
						<tr><td></td><td><input type=\"checkbox\" name=\"title\" value=\"1\"".$title."> Aktuellen Inhalt bei Seitentitel anzeigen</td></tr>
						<tr><td>Seiten-Adresse:</td><td><input type=\"text\" size=\"30\" name=\"page\" value=\"".$a->page."\">
						<br><div class=\"example\">z.B. http://www.tsgames.de</div></td></tr>
						<tr><td>Mail-Adresse:</td><td><input type=\"text\" size=\"30\" name=\"mail\" value=\"".$a->mail."\"></td></tr>
						<tr><td>Bilderqualität (10 - 100):</td><td><input type=\"text\" maxlength=\"3\" size=\"3\" name=\"picquali\" value=\"".$a->picquali."\"></td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"speciallinks\" value=\"1\"".make_check($a->speciallinks)."> Suchmaschinen-freundliche Links</td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"safemail\" value=\"1\"".make_check($a->safemail)."> E-Mailadressen verschlüsseln</td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"allow_compress\" value=\"1\"".make_check($a->allow_compress)."> Seitenausgabe komprimieren (sofern vom Browser zugelassen, kann Ladezeit verkürzen)</td></tr>
						
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"editor\" value=\"1\"".make_check($a->editor)."> Grafischen HTML-Editor (TinyMCE) für Content-Bearbeitung verwenden<br>(diese Einstellung ist die Standardeinstellung, sie kann jedoch vor jeder Artikelbearbeitung verändert werden!)</td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"smileys\" value=\"1\"".make_check($a->smileys)."> Smiley-Modul aktivieren</td></tr>
						
						".config_space().config_space("Modul: Sprachen")."
						<tr><td>Sprachdatei:</td><td><select name=\"language\">";
						if($dir=@opendir($language_folder))
						{
							while($file=@readdir($dir))
							{
								unset($sel);
								if(strtolower($file)=="custom.txt") continue;
								if(strtolower(substr($file,-3))!="txt") continue;
								$file=substr($file,0,-4);
								if($file==$a->language) $sel=" selected";
								echo "<option".$sel.">".$file."</option>";
							}
							@closedir($dir);
						}
						echo "</select></td></tr>";
						$lists=get_lists($a->search_list,"search_list");
						if($lists) echo config_space().config_space("Modul: Suche")."<tr><td>Listenansicht:</td><td>".$lists."</td></tr>";
						
						echo config_space().config_space("Modul: E-Mail Benachrichtigungen")."<tr><td colspan=\"2\"><table width=\"100%\"><tr><td class=\"confirm_head\">Benutzer</td><td class=\"confirm_head\">Gästebuch</td><td class=\"confirm_head\">Kommentare</td><td class=\"confirm_head\">Registration</td></tr>";
						$link=$pms_db_connection->query(make_sql("user","typ >= '1'","typ DESC,name"));
						while($link && $b=$pms_db_connection->fetchObject($link))
						{
							echo '<tr><td>'.$b->name." (".$b->mail.")</td>";
							foreach($confirmation_dialogs as $f)
							echo '<td style="text-align:center;"><input type="checkbox" name="'.$f[0].'[]" value="'.$b->id.'"'.make_check($b->$f[1]).'></td>';
							
							echo '</tr>';
						}
						echo "</table></td></tr>".config_space().config_space("Modul: Menü")."
						<tr><td>Menü-Modus:</td><td><input type=\"radio\" name=\"menu_mode\" onclick=\"set_mode()\" value=\"0\"".$menu_mode[0].">Standard (einfache Menü-Konfiguration per Admin)<br>
						<input type=\"radio\" name=\"menu_mode\" onclick=\"set_mode()\" value=\"1\"".$menu_mode[1].">Erweitert (Spezielle Konfiguration für aufklappende Menüs, mit Stylesheets)</td></tr>
						<tr id=\"menu_id_1\"".$menu_disabled."><td>Menüumbruch alle:</td><td><input type=\"text\" maxlength=\"3\" size=\"3\" name=\"menubreak\" value=\"".$a->menubreak."\"> Einträge</td></tr>
						<tr id=\"menu_id_2\"".$menu_disabled."><td>Menüausrichtung</td><td><input type=\"radio\" onchange=\"javascript:menu_preview()\" name=\"vertical\" value=\"0\"".$horizontal."> Horizontal <input type=\"radio\" onchange=\"javascript:menu_preview()\" name=\"vertical\" value=\"1\"".$vertical."> Vertikal</td></tr>
						<tr id=\"menu_id_3\"".$menu_disabled."><td>Menü-Größe</td><td><input type=\"text\" onchange=\"javascript:menu_preview()\" name=\"menu_width\" size=\"4\" maxlength=\"4\" value=\"".$a->menu_width."\">px Breite, <input type=\"text\" onchange=\"javascript:menu_preview()\" name=\"menu_height\" size=\"4\" maxlength=\"4\" value=\"".$a->menu_height."\">px Höhe</td></tr>
						<tr id=\"menu_id_4\"".$menu_disabled."><td>Menü-Vorschau</td><td>";
						echo "<table class=\"menu_example\" style=\"display:none;\" id=\"10\">";
						for($i=0;$i<3;$i++)
						{
							echo "<tr>";
							echo "<th id=\"".$i."\">Menüpunkt</th>";
							echo "</tr>";
						}
						echo "</table>";
						echo "<table class=\"menu_example\" style=\"display:none;\" id=\"11\">";
						for($i=0;$i<3;$i++)
						{
							echo "<th id=\"".($i+3)."\">Menüpunkt</th>";
						}
						echo "</table>";
						
						echo "
						</td></tr></div>".config_space().config_space("Modul: Listenansicht")."
						<tr><td width=\"250px\">Einträge/Seite:</td><td><input type=\"text\" maxlength=\"3\" size=\"3\" name=\"page_limit\" value=\"".$a->page_limit."\"> Einträge</td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"commentssmall\" value=\"1\"".make_check($a->commentssmall)."> Zahl der Kommentare bei Inhalts-Liste anzeigen</td></tr>
						<tr><td width=\"250px\">Spalten bei Ausgabe:</td><td><input type=\"text\" maxlength=\"3\" size=\"3\" name=\"list_rows\" value=\"".$a->list_rows."\"></td></tr>
						".config_space().config_space("Modul: Inhaltsansicht")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"writtenby\" value=\"1\"".make_check($a->writtenby)."> Geschrieben von... anzeigen</td></tr>
						
						".config_space().config_space("Modul: Kommentare")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"comments\" value=\"1\"".$comments."> Inhalte dürfen kommentiert werden</td></tr>
						<tr><td width=\"250px\">Kommentar-Anzahl (Anzeige):</td><td><input type=\"text\" maxlength=\"5\" size=\"3\" name=\"numcomments\" value=\"".$a->numcomments."\"></td></tr>
						".config_space().config_space("Modul: Bewertungen")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"rate\" value=\"1\"".$rate."> Bewertungs-System für Inhalte aktivieren</td></tr>
						".config_space().config_space("Modul: Besucherzähler")."
						<tr><td width=\"250px\">Passwort für Statistik-Zugriff:</td><td><input type=\"text\" size=\"20\" maxlength=\"32\" name=\"visitors_password\" value=\"".$a->visitors_password."\"></td></tr>
						<tr><td width=\"250px\">Besucherzähler erhöhen um:</td><td><input type=\"text\" size=\"6\" name=\"visitors_increment\" value=\"".$a->visitors_increment."\"></td></tr>
						<tr><td width=\"250px\">Zeit (Minuten), die ein Besucher als \"Online\" gilt:</td><td><input type=\"text\" size=\"6\" name=\"visitors_lifetime\" value=\"".$a->visitors_lifetime."\"></td></tr>
						".config_space().config_space("Modul: Downloads")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"predownload\" value=\"1\"".$predownload."> Bei Downloads zunächst Download-Vorschaltseite</td></tr>
						".config_space().config_space("Modul: User-System")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"register_activated\" value=\"1\"".make_check($a->register_activated)."> Registration erlauben</td></tr>
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"password_recovery_activated\" value=\"1\"".make_check($a->password_recovery_activated)."> Passwort darf zurückgesetzt werden</td></tr>
						".config_space().config_space("Modul: Top-Users")."
						<tr><td colspan=\"2\"><input type=\"checkbox\" name=\"topusers\" value=\"1\"".make_check($a->topusers)."> Top-Userliste aktiv</td></tr>
						<tr><td width=\"250px\">Anzahl der Top-User:</td><td><input type=\"text\" maxlength=\"5\" size=\"3\" name=\"numtopuser\" value=\"".$a->numtopuser."\"></td></tr>
						
						".config_space().config_space("Modul: Gästebuch")."
						<tr><td></td><td><input type=\"checkbox\" name=\"guestbook_activated\" value=\"1\"".make_check($a->guestbook_activated)."> Verfassen neuer Gästebucheinträge möglich</td></tr>
						".config_space().config_space("Modul: Am Meisten diskutiert")."
						<tr><td width=\"250px\">Minimum-Kommentarzahl für \"Meist diskutiert\":</td><td><input type=\"text\" maxlength=\"5\" size=\"3\" name=\"mincomments\" value=\"".$a->mincomments."\"></td></tr>
						".config_space().config_space("Modul: Aktuelle Kommentare")."
						<tr><td width=\"250px\">Kommentare der letzten</td><td><input type=\"text\" size=\"2\" name=\"latest_comments_days\" value=\"".$a->latest_comments_days."\"> Tage anzeigen</td></tr>
						<tr><td width=\"250px\">Anzahl Zeichen, bis gekürzt wird:</td><td><input type=\"text\" size=\"2\" name=\"latest_comments_chars\" value=\"".$a->latest_comments_chars."\"></td></tr>
						".config_space()."
						
						<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"config\" value=\"Speichern\"></center></td></tr>
						
						</form></table>";
					}
				}
			}
			if($action=="poll")
			{
				if($new || $edit)
				{
					$sort=1000;
					$available=1;
					if($edit)
					{
						$question=from_db("poll",$edit,"question");
						$sort=from_db("poll",$edit,"sort");
						$available=from_db("poll",$edit,"available");
						for($i=1;$i<=10;$i++)
						{
							$answer[$i]=from_db("poll",$edit,"answer".$i);
						}
					}
					$available=make_check($available);
					$add="erstellen";
					if($edit)
					{
						$add="bearbeiten";
					}
					echo form().heading("Umfrage ".$add)."
					<input type=\"hidden\" name=\"id\" value=\"".$edit."\">
					<table><tr><td>Frage:</td><td><input type=\"text\" name=\"question\" size=\"40\" value=\"".$question."\"></td></tr>
					<tr><td>Sortierung:</td><td><input type=\"text\" name=\"sort\" value=\"".$sort."\"></td></tr>";
					for($i=1;$i<=10;$i++)
					{
						echo "<tr><td>".$i.". Antwort:</td><td><input type=\"text\" name=\"answer".$i."\" size=\"40\" value=\"".$answer[$i]."\"></td></tr>";
					}
					echo "<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"available\" value=\"1\"".$available."> Umfrage verfügbar</center></td></tr>
					<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"poll\" value=\"Speichern\"></center></td></tr>
					</table></form>";
				}
				else
				{
					if($delete)
					{
						if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."poll WHERE id = '$delete' LIMIT 1;"))
						$ok="Umfrage erfolgreich gelöscht!";
						else
						$error="Fehler beim Löschen der Umfrage!";
						ok_error();
					}
					if($sort_do && $id_para)
					{
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."poll SET sort='$sort_para' WHERE id = '$id_para' LIMIT 1;");
					}
					echo heading("Umfragen");
					echo '[<a href="admin.php?action='.$action.'&new=yes">Neue Umfrage</a>]<br><br>';
					echo '<table class="group">';
					echo table_header("ID:30px|Frage:150px|Sortierung:90px|Verfügbar:60px|Bearbeiten:80px|Löschen:65px");
					$link=$pms_db_connection->query(make_sql("poll","","sort,question"));
					for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
					{
						if($i>0)
						{
							$sort_up="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($last-1)."&id=".$a->id."\">&uarr;</a>";
						}
						$sort_down="<a href=\"admin.php?action=".$action."&sort=yes&pos=".($a->sort+1)."&id=".$last_id."\">&darr;</a>";
						$available="Nein";
						if($a->available)
						{
							$available="Ja";
						}
						$menu[$i][0]=$a->id;
						$menu[$i][1]=$a->question;
						$menu[$i][2]=$a->sort." ".$sort_up;
						if($i>0)
						{
							$menu[$i-1][2]=$menu[$i-1][2].$sort_down;
						}
						$menu[$i][3]=$available;
						$menu[$i][4]="<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
						$menu[$i][5]="<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
						$last=$a->sort;
						$last_id=$a->id;
					}
					echo array_table($menu,5);
				}
			}
			if($action=="events")
			{
				echo form().heading("Ereignisse").'Es werden die Ereignisse der letzten <select name="last_events">';
				for($i=1;$i<14;$i+=3)
				{
					$sel="";
					if($i==$_SESSION['last_events'])
					{
						$sel=" selected";
					}
					echo "<option".$sel.">".$i."</option>";
				}
				$sel="";
				if($_SESSION['last_events']=='Alle')
				{
					$sel=" selected";
				}
				echo "<option".$sel.">Alle</option>";
				echo '</select> Tag(e) angezeigt. <input type="submit" name="events" value="OK">
				<br>
				<br><table class="group">';
				echo table_header("Ereignisstyp:100px|Datum:80px|Uhrzeit:80px|Beschreibung:600px");
				
				$count=0;
				if(!$_SESSION['last_events'])
				{
					$_SESSION['last_events']=1;
				}
				$last=time()-$_SESSION['last_events']*60*60*24;
				$check[0]="login > '$last' AND ";
				$check[1]="date > '$last' AND ";
				$check[2]="time > '$last' AND ";
				$check[3]="register > '$last' AND ";
				if($_SESSION['last_events']=="Alle")
				{
					unset($check);
				}
				$check[0].="login != '0'";
				$check[1].="date != '0'";
				$check[2].="time != '0'";
				$check[3].="register != '0'";
				$link=$pms_db_connection->query(make_sql("user",$check[0],"login DESC"));
				while($link && $b=$pms_db_connection->fetchObject($link))
				{
					$c[$count][0]="Benutzer"; // Typ
					$c[$count][1]=$b->login; // Date
					$c[$count][2]=$b->name; // User
					$count++;
				}
				$link=$pms_db_connection->query(make_sql("user",$check[3],"register DESC"));
				while($link && $b=$pms_db_connection->fetchObject($link))
				{
					$c[$count][0]="Benutzer"; // Typ
					$c[$count][1]=$b->register; // Date
					$c[$count][2]=$b->name; // User
					$c[$count][3]=1;
					$count++;
				}
				$link=$pms_db_connection->query(make_sql("comments",$check[1],"date DESC"));
				while($link && $b=$pms_db_connection->fetchObject($link))
				{
					$c[$count][0]="Kommentar"; // Typ
					if($b->item==$gb_item)
					{
						$c[$count][0]="Gästebuch";
					}
					$c[$count][1]=$b->date; // Date
					$c[$count][2]=$b->name;
					if($b->user)
					{
						$c[$count][2]=from_db("user",$b->user,"name"); // User
					}
					$c[$count][3]=$b->title;
					$c[$count][4]=$b->item;
					$count++;
				}
				$link=$pms_db_connection->query(make_sql("item",$check[2],"time DESC"));
				while($link && $b=$pms_db_connection->fetchObject($link))
				{
					$c[$count][0]="Inhalt"; // Typ
					$c[$count][1]=$b->time; // Date
					$c[$count][2]=from_db("user",$b->user,"name");
					$c[$count][3]=$b->id;
					$c[$count][4]=$b->subcat;
					$c[$count][5]=$b->cat;
					$c[$count][6]=$b->special;
					$count++;
				}
				function vergleich($a, $b)
				{
					if ($a[1] == $b[1]) {
						return 0;
					}
					return ($a[1] > $b[1]) ? -1 : 1;
				}
				
				usort($c,'vergleich');
				$sname=from_db("config",1,"name");
				for($i=0;$i<count($c);$i++)
				{
					$typ=$c[$i][0];
					echo "<tr";
					if($i%2==0)
					{
						echo " bgcolor=\"#FFFFFF\"";
					}
					echo "><td>".$typ."</td><td>".date("d.m.Y",$c[$i][1])."</td><td>".date("H:i",$c[$i][1])."</td><td>";
					if($typ!="Inhalt" || !$c[$i][6])
					{
						echo $c[$i][2]." hat ";
					}
					if($typ=="Benutzer")
					{
						echo "sich ";
						if(!$c[$i][3])
						{
							echo "eingeloogt";
						}
						else
						{
							echo "auf ".$sname." registriert.";
						}
					}
					if($typ=="Kommentar")
					{
						if(!$c[$i][3])
						{
							$c[$i][3]="Kein Titel";
						}
						echo "das Kommentar mit dem Titel \"".$c[$i][3]."\" zu \"".make_link_mark(from_db("item",$c[$i][4],"name"),"","","",$c[$i][4],"comments")."\" geschrieben.";
					}
					if($typ=="Gästebuch")
					{
						if(!$c[$i][3])
						{
							$c[$i][3]="Kein Titel";
						}
						echo "einen neuen Gästebucheintrag mit dem Titel \"".$c[$i][3]."\" im ".make_link_mark(from_db("item",$c[$i][4],"name"),"","","",$c[$i][4],"comments")." verfasst.";
					}
					if($typ=="Inhalt")
					{
						if(!$c[$i][6])
						{
							echo "ein neues Inhaltsobjekt \"".make_link(from_db("item",$c[$i][3],"name"),"","","",$c[$i][3])."\" erstellt";
						}
						else
						{
							echo "Dem System wurde das Spezial-Modul \"".make_link($special_typ[$c[$i][6]],"","","",$c[$i][3])."\" hinzugefügt.";
						}
						if($c[$i][5] || $c[$i][4])
						{
							echo " (".from_db("cat",$c[$i][5],"name")." -&gt; ".from_db("subcat",$c[$i][4],"name").")";
						}
					}
					echo "</tr>
					";
				}
				echo '</table>';
			}
			if($action=="backup")
			{
				if(from_db("user",$_SESSION['userid'],"typ")<3)
				{
					$error="Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!";
					ok_error();
				}
				else
				{
					echo form().heading("Backup-Manager");
					$back=get_backups();
					if($_GET["save"])
					{
						$error="Das angegebene Backup existiert nicht!";
						if(@in_array($_GET["save"],$back))
						{
							$error="Fehler beim Schützen (Zugriffsrechte überprüfen)";
							$f=fopen($backup_folder.$_GET["save"]."/saved","w+");
							if($f)
							{
								unset($error);
								fclose($f);
								$ok="Backup wurde geschützt!";
							}
						}
						ok_error();
					}
					
					if($_GET["delete"])
					{
						$error="Das angegebene Backup existiert nicht!";
						if(@in_array($_GET["delete"],$back))
						{
							$error="Das Backup kann nicht gelöscht werden (zu Aktuell)";
							$saved=file_exists($backup_folder.$_GET["delete"]."/saved");
							if($saved) $error="Das Backup kann nicht gelöscht werden (geschützt)";
							$a=@array_search($_GET["delete"],$back);
							if($a>=3 && !$saved)
							{
								if(delete_all($backup_folder.$_GET["delete"]))
								{
									unset($error);
									unset($back[$a]);
									$ok="Backup wurde gelöscht!";
								}
								else
								$error="Fehler beim Löschen!";
							}
						}
						ok_error();
					}
					echo "
					Aus Sicherheitsgründen können Sie nicht die 3 aktuellsten Backups löschen, bitte erledigen Sie dies auf Wunsch über FTP
					<br>Sie finden alle Backups im Ordner \"backup\".<br><br>
					Wie Sie alte Backups einladen, erfahren Sie in der PMS Dokumentation.<br>
					Das System speichert alle Kategorien, Inhalte, Variablen, Benutzer, Kommentare, Bewertungen usw.<br>
					Ebenfalls werden alle Bilder von Kategorien, Nutzern oder Inhalten gespeichert.<br>
					Manuell eingefügte Bilder, Dateien oder auch Download-Links werden nicht gesichert. Sichern Sie diese gegebenenfalls manuell.<br><br>
					Es wird empfohlen, wichtige Backups vor dem Löschen zu schützen. Diese können dann nur noch über FTP gelöscht werden.<br><br>
					<table class=\"group\">".table_header("Name:150px|Datum:80px|Uhrzeit:80px|Größe (in MB):80px|Löschen:80px|Schützen:80px");
					if(is_array($back))
					{
						$i=0;
						foreach($back as $b)
						{
							$save=file_exists($backup_folder.$b."/saved");
							$saved=$save ? "<div class=\"disabled\">Geschützt</disabled>" : "<a href=\"admin.php?action=".$action."&save=".$b."\">Schützen</a>";
							$del="<div class=\"disabled\">Nicht Möglich</div>";
							if($i>2 && !$save) $del="<a href=\"admin.php?action=".$action."&delete=".$b."\">Löschen</a>";
							$date=explode("_",$b);
							if(count($date)<5) $date="Nicht auslesbar";
							echo "<tr";
							if($i%2==0) echo " bgcolor=\"#FFFFFF\"";
							echo "><td>".$b."</td>";
							if(is_array($date)) echo "<td>".$date[2].".".$date[1].".".$date[0]."</td><td>".$date[3].":".$date[4]."</td>";
							else echo "<td colspan=\"2\">".$date."</td>";
							echo "<td>".round(get_filesize($backup_folder.$b)/1024/1024,2)."</td><td>".$del."</td><td>".$saved."</td></tr>
							";
							$i++;
						}
					}
					else
					{
						echo "<tr><td colspan=\"4\">Es wurden noch keine Backups angelegt.<br>Klicken Sie auf \"Neues Backup erstellen\", um ein Backup anzulegen.</td></tr>";
					}
					echo "</table>";
					
					echo "<br><br>
					<input type=\"submit\" name=\"backup\" value=\"Neues Backup erstellen\"><br>
					Hinweis: Das Erzeugen eines Backups kann, in Abhängigkeit des Umfangs der Website, bis zu mehreren Minuten dauern!
					</form>";
				}
			}
			if($action=="activity")
			{
				unset($add);
				$stats=get_24_stats($_SESSION["filter_bot"]);
				$num=count($stats);
				if($num!=1)
				{
					$add[0]="en";
					$add[1]="e";
				}
				unset($check);
				if($_SESSION['filter_bot']) $check=" checked";
				echo heading("Website-Status")."Sie haben auf dieser Seite die Möglichkeit, alle aktuellen Benutzer- und Besucher-Aktivitäten einzusehen.
				<br><br>
				<table align=\"center\">
				<tr><td>Besucher Online:</td><td>".count_db_exp("visitors_counter","WHERE time>='".(time()-60*$config_values->visitors_lifetime)."'")."</td></tr>
				<tr><td>Besucher Heute:</td><td>".$config_values->visitors_today."</td></tr>
				<tr><td>Besucher Gestern:</td><td>".$config_values->visitors_yesterday."</td></tr>
				<tr><td>Besucher Gesamt:</td><td>".$number_visitors."</td></tr>
				</table>
				<br>
				Innerhalb der letzten 24 Stunden war".$add[0]." ".$num." Zugriff".$add[1]."<br>
				<br>
				[<a href=\"admin.php?action=".$action."\">Aktualisieren</a>]
				<br><br>
				".form()."<input type=\"checkbox\" name=\"filter_bot\" value=\"1\"".$check."> Suchmaschinen filtern <input type=\"submit\" name=\"send_bot_filter\" value=\"Speichern\"></form>
				<br>
				<table class=\"group\">".table_header("IP:100px|Browser:300px|Benutzer:100px|Letzte Aktivität:120px|Typ der letzten Aktion:150px");
				unset($act);
				$i=0;
				foreach($stats as $a)
				{
					$user="Keiner / Gast";
					if($a->user) $user=make_link(from_db("user",$a->user,"name"),"action=user&id=".$a->user,0,0,0,"",0,"_blank");
					unset($col);
					if($i%2==0) $col=" bgcolor=\"#ffffff\"";
					echo "<tr".$col."><td>".$a->id."</td><td>".browser($a->browser)."</td><td>".$user."</td><td>Vor ".time_diff($a->time)."</td><td>".convert_action($a->typ,$a->content)."</td></tr>";
					$i++;
				}
				echo "</table>";
			}
			echo $modul_content;
			echo "</center></td></tr></table>";
		}
		else
		{
			echo "</center></td></tr></table>";
		}
		echo "</div></td></tr></table>";
	}
	?>
	<script type="text/javascript">
	var menu_height=400;
	function scroll_menu()
	{
		var a1,a2,a3;
		var ie=0;
		if (navigator.appName == 'Microsoft Internet Explorer') {
			ie=1;
			a1=document.body.scrollTop;
			a2=document.body.clientWidth;
			a3=document.body.clientHeight;
		}
		else
		{
			a1=window.pageYOffset;
			a2=window.innerWidth;
			a3=window.innerHeight;
		}
		var top_h=-5;
		if(a3>menu_height)
		top_h=a1-5;
		
		document.getElementById("menu_id").style.top=top_h;
		
		if(document.getElementById("help_id"))
			document.getElementById("help_id").style.width=a2-34;
	}
	scroll_menu();
	window.setInterval("scroll_menu()", 1);
	</script>
	</body>
	</html>