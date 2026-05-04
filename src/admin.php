<?php
define("PMS_FRONTEND",0);
define("PMS_BACKEND",1);
require('functions.php');
require('admin_helpers.php');
require('admin_actions_dynamic.php');
require('admin_actions_admin.php');
require('admin_actions_monitoring.php');
require('admin_actions_ui.php');
require('admin_actions_menu.php');
require('admin_actions_content.php');

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
$action=@$_POST["action"] ?: @$_GET["action"];
$modul=@$_GET["modul"];

if($action === 'xlsx_import_ajax' && @$_SESSION['userid'])
{
	header('Content-Type: application/json');
	if(!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK)
		{ echo json_encode(['error' => 'Upload fehlgeschlagen']); exit; }
	@mkdir('images/uploads/temp/', 0755, true);
	$temp = 'images/uploads/temp/' . uniqid('xlsx_') . '.xlsx';
	if(move_uploaded_file($_FILES['xlsx_file']['tmp_name'], $temp))
	{
		$result = parse_xlsx_to_text($temp);
		@unlink($temp);
		cleanup_xlsx_temp_files(1);
		if(is_array($result) && isset($result['error']))
			echo json_encode(['error' => $result['error']]);
		else
			echo json_encode(['content' => $result ?: '']);
	}
	else echo json_encode(['error' => 'Datei konnte nicht gespeichert werden']);
	exit;
}

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
	echo form()."<table align=\"center\" style=\"max-width: 500px; justify-self: center;\"><tr>
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
	echo '<button class="sidebar-toggle" id="sidebar-toggle">&#9776;</button>';
	echo '<div class="admin-layout">';
	echo '<aside class="admin-sidebar" id="admin-sidebar">';
	echo '<div class="sidebar-header">'.htmlspecialchars($config_values->name).'</div>';
	echo '<div class="sidebar-user">Hallo, '.from_db("user",$_SESSION['userid'],"name").' (<a href="admin.php?action=logout">Logout</a>)</div>';
	echo '<nav class="sidebar-nav">';
	echo '<div class="nav-section-label">Navigation</div>';
	echo '<ul class="nav-list">';
	for($i=0;$i<count($action_name);$i++)
	{
		$icon="";
		$file=$image_path."admin/".$action_list[$i].".png";
		if(file_exists($file)) $icon='<img src="'.$file.'" width="16" height="16" alt="">';
		$href='admin.php?action='.$action_list[$i];
		$target="";
		if($action_list[$i]=="page"){
			$href="index.php";
			$target=' target="_blank"';
		}
		$active=($action==$action_list[$i]) ? ' active' : '';
		echo '<li class="nav-item'.$active.'"><a href="'.$href.'"'.$target.' title="'.htmlspecialchars($action_info[$i]).'"><span class="nav-icon">'.$icon.'</span><span class="nav-label">'.htmlspecialchars($action_name[$i]).'</span></a></li>';
	}
	echo '</ul>';
	if(count($modul_name))
	{
		echo '<div class="nav-divider"></div>';
		echo '<div class="nav-section-label">Module</div>';
		echo '<ul class="nav-list">';
		foreach($modul_name as $a)
		{
			$icon="";
			$file=$image_path."admin/".$a[1].".png";
			if(file_exists($file)) $icon='<img src="'.$file.'" width="16" height="16" alt="">';
			echo '<li class="nav-item"><a href="admin.php?modul='.htmlspecialchars($a[1]).'"><span class="nav-icon">'.$icon.'</span><span class="nav-label">'.htmlspecialchars($a[0]).'</span></a></li>';
		}
		echo '</ul>';
	}
	echo '</nav>';
	echo '</aside>';
	echo '<main class="admin-main"><div class="admin-content-inner">';
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
			$content=$pms_db_connection->escape($_POST['content'], false);
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
			if($_POST["item_step2"]!="Übernehmen")
			{
				unset($edit);
			}
		}
		if($_POST["add_image"]!="")
		{
			$action="item";
			$id=$_POST["item"];
			if(!$id){
				$id=$pms_db_connection->lastInsertId();
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
		handle_admin_add_image();
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
			handle_admin_home();
		}
			if($action=="menu")
			{
				handle_admin_menu();
			}
			if($action=="user")
			{
				handle_admin_user();
			}
			if($action=="cat")
			{
				handle_admin_cat();
			}
			if($action=="subcat")
			{
				handle_admin_subcat();
			}
			if($action=="item_restore")
			{
				handle_admin_item_restore();
			}
			if($action=="item_recover" && from_db("item",$_GET["item"],"id"))
			{
				handle_admin_item_recover();
			}
			if($action=="item")
			{
				handle_admin_item();
			}
			
			if($action=="var")
			{
				handle_admin_var();
			}
			if($action=="bans")
			{
				handle_admin_bans();
			}
			if($action=="config")
			{
				handle_admin_config();
			}
			if($action=="poll")
			{
				handle_admin_poll();
			}
			if($action=="events")
			{
				handle_admin_events();
			}
			if($action=="backup")
			{
				handle_admin_backup();
			}
			if($action=="activity")
			{
				handle_admin_activity();
			}
			echo $modul_content;
			echo "</center></td></tr></table>";
		}
		else
		{
			echo "</center></td></tr></table>";
		}
		echo "</div></main></div>";
	}
	?>
	<script type="text/javascript">
	document.getElementById('sidebar-toggle').addEventListener('click', function() {
		document.getElementById('admin-sidebar').classList.toggle('open');
	});
	</script>
	</body>
	</html>