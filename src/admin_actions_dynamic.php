<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

// Module: admin_actions_dynamic.php
// Handlers for var (search/replace rules) and poll (surveys) actions
// Includes POST form submission processors

// Process POST submissions for dynamic-related actions
function process_dynamic_post_handlers()
{
	global $pms_db_connection, $pms_db_prefix, $_POST, $_SESSION;
	global $action, $edit, $error, $ok;

	// Process poll filter session storage
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

	// Process poll save
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

	// Process var save
	if($_POST['var']=="Speichern")
	{
		$action="var";
		$edit=$_POST['id'];
		$search=$_POST['search'];
		$replace=$pms_db_connection->escape($_POST['replace']);
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
}

/**
 * Handle var (dynamic search/replace rules) action
 */
function handle_admin_var()
{
	global $pms_db_use_reference, $pms_db_connection, $pms_db_prefix, $new, $edit, $delete,
	       $select_reference, $action, $error, $ok;

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
		$search = '';
		$replace = '';
		if(!$_SESSION['poll_search'])
		{
			$search="Suche:150px|";
		}
		if(!$_SESSION['poll_replace'])
		{
			$replace="Ersetzen mit:150px|";
		}
		echo table_header("ID:30px|".$search.$replace."Bearbeiten:80px|Löschen:65px");
		$link=$pms_db_connection->query(make_sql("dynamic","","LOWER(searcher)"));
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

/**
 * Handle poll (surveys/questions) action
 */
function handle_admin_poll()
{
	global $pms_db_connection, $pms_db_prefix, $new, $edit, $delete, $sort_do, $sort_para, $id_para,
	       $action, $error, $ok;

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

process_dynamic_post_handlers();

?>
