<?php
// Module: admin_actions_content.php
// Handlers for content management: categories, subcategories, item recovery/restore

/**
 * Handle cat (category) action
 */
function handle_admin_cat()
{
	global $pms_db_connection, $pms_db_prefix, $pms_db_use_reference;
	global $edit, $new, $post, $delete, $select_reference, $action, $error, $ok, $sort_do, $sort_para, $id_para;

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

/**
 * Handle subcat (subcategory) action
 */
function handle_admin_subcat()
{
	global $pms_db_connection, $pms_db_prefix, $pms_db_use_reference;
	global $edit, $new, $post, $delete, $select_reference, $action, $error, $ok, $sort_do, $sort_para, $id_para;
	global $subcat_filter, $image_path, $supported_img;

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

/**
 * Handle item_restore action (restore deleted items from backup)
 */
function handle_admin_item_restore()
{
	global $pms_db_connection, $action, $error, $ok;

	if(@$_POST["do_restore"])
	{
		$action="item";
		$found=recover_item($_POST["item_select"],0);
		if($pms_db_connection->query(str_replace("\\r\\n", "\r\n", $found[$_POST["date_select"]][1]))) $ok="Inhalt erfolgreich Wiederhergestellt"; else $error="Fehler beim Wiederherstellen des Inhalts";
		ok_error();
	}
	else
	{
		$ok=0;
		$step=0;
		if(@$_POST["item_restore"])$step=1;
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
					$sel = '';
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
}

/**
 * Handle item_recover action (recover versions from backup)
 */
function handle_admin_item_recover()
{
	global $pms_db_connection, $pms_db_prefix, $action, $error, $ok;

	if(@$_GET["do_recover"] && @$_GET["item"])
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

?>
