<?php
/** @psalm-suppress ParadoxicalCondition */
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

// Module: admin_actions_ui.php
// Verbliebener Altbestand: Bild-Auswahl der Inhaltsverwaltung

/**
 * Handle add_image action (image upload/deletion)
 */
function handle_admin_add_image()
{
	global $pms_db_connection, $pms_db_prefix, $error, $ok, $add_image, $action, $edit, $post;
	global $edit_string_replace, $edit_string_use;
	global $cat, $subcat, $typ, $typ2;

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
		$edit=(int)($_POST["item"] ?? 0);
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
				$edit=(int)($_GET["item"] ?? 0);
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
				$db_err = $pms_db_connection->error();
				$error = "Interner Verarbeitungsfehler"
					. " (item=" . intval($_POST["item"] ?? 0)
					. ", image=" . htmlspecialchars(basename($image))
					. ", file=" . (file_exists($file) ? "ok" : "fehlt")
					. ($db_err ? ", db=" . htmlspecialchars($db_err) : "")
					. ")";
				ok_error();
			}
		}
	}
}

?>
