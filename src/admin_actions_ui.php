<?php
// Module: admin_actions_ui.php
// Handlers for UI/display actions: home, add_image

/**
 * Handle home action (dashboard)
 */
function handle_admin_home()
{
	global $pms_version, $pms_db_use_reference, $pms_db_reference_id;

	echo '<h2>Willkommen im Admin Center!</h2>
	<br>
	Sie befinden sich nun im gesicherten Bereich der Website.<br>
	Bitte wählen Sie eine Aktion im Menü links aus<br>
	Im unteren Teil der Seite finden Sie die PMS-Referenz.<br>
	Klicken Sie dort auf die gewünschte Position, um direkt zur Hilfe zu gelangen.';
	if(@$GLOBALS['set_reloadable'])
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

/**
 * Handle add_image action (image upload/deletion)
 */
function handle_admin_add_image()
{
	global $pms_db_connection, $pms_db_prefix, $error, $ok, $add_image, $action, $edit, $post;
	global $edit_string_replace, $edit_string_use;

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
			$GLOBALS['add_image2']=$edit;
			$GLOBALS['add_image2_img']=$image;
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
				if(!$delete) $delete=$select && $_GET["item"] && $_GET["abort"];
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
}

?>
