<?php
// Module: admin_actions_content.php
// Handlers for content management: categories, subcategories, items, recovery/restore
// Includes POST form submission processors

// Process POST submissions for content-related actions
function process_content_post_handlers()
{
	global $pms_db_connection, $pms_db_prefix, $_SESSION, $_POST, $_GET, $_FILES;
	global $edit, $action, $error, $ok, $post, $item_filter, $item_filter2, $delete;
	global $subcat_filter, $add_image, $add_image2, $add_image2_img;
	global $supported_img, $image_path;

	// AJAX XLSX import endpoint
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

	// Process category delete
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

	// Process category save
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

	// Process subcategory delete
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

	// Process subcategory save
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

	// Process item filter
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

	// Process subcategory filter
	if(array_key_exists("subcat_filter",$_POST))
	{
		$action="subcat";
		$subcat_filter=$_POST["uppcat"];
		$_SESSION["subcat_filter"]=$_POST["uppcat"];
	}

	// Process item step 1 (initial item form submission)
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

	// Process item refresh action
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

	// Process item step 2 (main item form submission with content)
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
						if($typ!=1)
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
			$ok="<div align=\"center\">Inhalt erfolgreich gespeichert!<br><a href=\"index.php?item=".$edit."\">Inhalt anzeigen</a></div>";
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

	// Process add_image (drag-drop image upload for items)
	if($_POST["add_image"]!="")
	{
		$action="item";
		$id=$_POST["item"];
		if(!$id){
			$id=$pms_db_connection->lastInsertId();
		}
		$path="images/uploads/";
		if($_POST["drag_name"]){
			$_FILES["image"]["name"]=$_POST["drag_name"];
			$img_data=rawurldecode($_POST["drag_data"]);
		}
		if($_FILES["image"]["name"])
		{
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
}

// Call POST processors on module load
process_content_post_handlers();

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

/**
 * Handle item (content) action
 */
function handle_admin_item()
{
	global $pms_db_connection, $pms_db_prefix, $pms_db_use_reference;
	global $edit, $new, $post, $delete, $select_reference, $action, $error, $ok;
	global $sort_do, $sort_para, $id_para;
	global $item_filter, $item_filter2, $content_typ, $special_typ;
	global $image_path, $supported_img;
	global $edit_string_replace, $edit_string_use;
	global $add_image, $add_image2, $add_image2_img;

	$typ2 = '';
	$files = [];

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
				if($typ2==4)
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
			<input type=\"hidden\" name=\"action\" value=\"item\">
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
			if(!$edit_string_replace)
			{
				$content=cleanup_content($content);
			}
			else $content=str_replace('<img id="##pms_replace_image_temp"','<img src="'.$edit_string_use.'"',$content);
			echo "
			<tr><td colspan=\"2\"><center>Inhalt:</center></td></tr>
			<tr><td colspan=\"2\"><center><textarea rows=\"22\" cols=\"95\" id=\"content\" name=\"content\">".str_replace('&','&amp;',$content)."</textarea>
			</center></td></tr>
			<tr><td colspan=\"2\"><center>
			<fieldset style=\"margin-top:10px; padding:10px; border:1px solid #ccc; width:90%;\">
				<legend>XLSX Datei importieren</legend>
				<input type=\"file\" id=\"xlsx_file_picker\" accept=\".xlsx\" style=\"display:none\">
				<button type=\"button\" onclick=\"document.getElementById('xlsx_file_picker').click()\">XLSX Inhalt importieren</button>
				<span id=\"xlsx_status\" style=\"margin-left:10px;font-size:.9em;\"></span>
				<br/><small>Zeichentabelle wird als Rohtext mit Leerzeichen als Trennzeichen eingefügt</small>
			</fieldset>
			</center></td></tr>
			<tr style=\"display:none\"><td><script>
			document.getElementById('xlsx_file_picker').addEventListener('change', function() {
				if (!this.files.length) return;
				var status = document.getElementById('xlsx_status');
				status.textContent = 'Wird importiert...';
				var fd = new FormData();
				fd.append('xlsx_file', this.files[0]);
				fetch('admin.php?action=xlsx_import_ajax', {method:'POST', body:fd})
					.then(function(r){return r.json();})
					.then(function(data){
						if(data.error){status.textContent='Fehler: '+data.error;return;}
						if(typeof tinyMCE!=='undefined' && tinyMCE.activeEditor){
							var existing=tinyMCE.activeEditor.getContent({format:'text'}).trim();
							tinyMCE.activeEditor.setContent(data.content+(existing?'\\n\\n--- Bestehender Inhalt ---\\n\\n'+existing:''));
						} else {
							var ta=document.querySelector('textarea[name=content]');
							if(ta) ta.value=data.content+(ta.value?'\\n\\n--- Bestehender Inhalt ---\\n\\n'+ta.value:'');
						}
						status.textContent='Importiert ✓';
					})
					.catch(function(){status.textContent='Netzwerkfehler';});
				this.value='';
			});
			</script></td></tr>";
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
				$a="";
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

		echo heading("Bild anpassen").form("disable_buttons().").'
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
		<table class="group items">';
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

?>
