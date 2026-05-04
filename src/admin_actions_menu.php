<?php
// Module: admin_actions_menu.php
// Handler for menu management action
// Includes POST form submission processors

// Process POST submissions for menu-related actions
function process_menu_post_handlers()
{
	global $pms_db_connection, $pms_db_prefix, $_POST;
	global $action, $post, $error, $ok, $edit, $new;

	// Process menu refresh
	if(array_key_exists("menu_refresh",$_POST))
	{
		$action="menu";
		$post=1;
	}

	// Process menu form submission
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
}

/**
 * Handle menu action
 */
function handle_admin_menu()
{
	global $pms_db_connection, $pms_db_prefix, $edit, $new, $post, $delete, $sort_do, $sort_para, $id_para;
	global $action, $error, $ok, $config_values, $user_typ, $plugin_intern;

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

process_menu_post_handlers();

?>
