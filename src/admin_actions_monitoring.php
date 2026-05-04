<?php
// Module: admin_actions_monitoring.php
// Handlers for backup, events, activity monitoring actions
// Includes POST form submission processors

// Process POST submissions for monitoring-related actions
function process_monitoring_post_handlers()
{
	global $pms_db_connection, $pms_db_prefix, $_POST, $_SESSION;
	global $action, $error, $ok;

	// Process events filter session storage
	if($_POST['events']=="OK")
	{
		$action="events";
		$_SESSION['last_events']=$_POST['last_events'];
	}

	// Process backup export
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

	// Process activity bot filter session storage
	if($_POST["send_bot_filter"])
	{
		$_SESSION['filter_bot']=$_POST['filter_bot'];
		$action="activity";
	}
}

/**
 * Handle events action
 */
function handle_admin_events()
{
	global $pms_db_connection, $pms_db_prefix, $gb_item, $special_typ;

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

/**
 * Handle backup action
 */
function handle_admin_backup()
{
	global $backup_folder, $action, $error, $ok;

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

/**
 * Handle activity action
 */
function handle_admin_activity()
{
	global $config_values, $number_visitors;

	$add = [];
	$check = '';
	$act = [];

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
	[<a href=\"admin.php?action=activity\">Aktualisieren</a>]
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

process_monitoring_post_handlers();

?>
