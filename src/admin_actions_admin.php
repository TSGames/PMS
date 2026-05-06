<?php
/** @psalm-suppress ParadoxicalCondition */
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

// Module: admin_actions_admin.php
// Handlers for bans, config, and user administration actions
// Includes POST form submission processors

// Process POST submissions for admin-related actions
function process_admin_post_handlers()
{
	global $pms_db_connection, $pms_db_prefix, $_SESSION, $_POST, $_FILES;
	global $edit, $action, $error, $ok, $post, $confirmation_dialogs;

	// Process bans save
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
		unset($edit);
		unset($new);
	}

	// Process config save
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

	// Process user save
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
}

/**
 * Handle bans (IP ban management) action
 * Requires super-admin permissions (typ >= 3)
 */
function handle_admin_bans()
{
	global $pms_db_connection, $pms_db_prefix, $new, $edit, $delete, $action, $error, $ok;

	if(from_db("user", @$_SESSION['userid'], "typ") < 3)
	{
		$error = "Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!";
		ok_error();
	}
	else
	{
		if($new || $edit)
		{
			$time = '';
			unset($time);
			if($edit)
			{
				$ip = from_db("bans", $edit, "ip");
				$reason = from_db("bans", $edit, "reason");
				$time = ban_time(from_db("bans", $edit, "time"));
				if($time == "Unbegrenzt")
					unset($time);
			}
			$add = "erstellen";
			$add1 = "Neuen ";
			if($edit)
			{
				$add1 = "";
				$add = "bearbeiten";
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
					$ok = "Ban erfolgreich entfernt!";
				else
					$error = "Fehler beim Löschen des Bans!";
				ok_error();
			}
			echo heading("Bans verwalten");
			echo '[<a href="admin.php?action='.$action.'&new=yes">Neuer Ban</a>]<br><br>';
			echo '<table class="group">';
			echo table_header("ID:30px|IP:100px|Begründung:200px|Verbleibende Dauer<br>(in Tagen):140px|Bearbeiten:80px|Löschen:65px");
			$link = $pms_db_connection->query(make_sql("bans", "", "id"));
			for($i=0; $link && $a=$pms_db_connection->fetchObject($link); $i++)
			{
				$menu[$i][0] = $a->id;
				$menu[$i][1] = $a->ip;
				$menu[$i][2] = def($a->reason);
				$menu[$i][3] = ban_time($a->time);
				$menu[$i][4] = "<a href=\"admin.php?action=".$action."&edit=".$a->id."\">Bearbeiten</a>";
				$menu[$i][5] = "<a href=\"admin.php?action=".$action."&delete=".$a->id."\">Löschen</a>";
			}
			echo array_table($menu, 5);
		}
	}
}

/**
 * Handle config (website configuration) action
 * Requires super-admin permissions (typ >= 3)
 */
function handle_admin_config()
{
	global $pms_db_connection, $pms_db_prefix, $language_folder, $confirmation_dialogs, $error, $ok;

	if(from_db("user", @$_SESSION['userid'], "typ") < 3)
	{
		$error = "Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!";
		ok_error();
	}
	else
	{
		$link = $pms_db_connection->query(make_sql("config", "", "id"));
		if($link)
		{
			$a = $pms_db_connection->fetchObject($link);
			$title = make_check($a->title);
			$rate = make_check($a->rate);
			$comments = make_check($a->comments);
			$predownload = make_check($a->predownload);
			$vertical = make_check($a->vertical);
			$menu_mode = [];
			$menu_mode[$a->menu_mode] = " checked";
			if($a->menu_mode) $menu_disabled = ' style="display:none;"';
			if(!$vertical)
				$horizontal = make_check(1);

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

/**
 * Handle user (user management) action
 */
function handle_admin_user()
{
	global $pms_db_connection, $pms_db_prefix, $new, $edit, $post, $delete, $action, $error, $ok, $user_typ;

	$no_edit = 0;

	if($edit && from_db("user", $edit, "typ") > from_db("user", @$_SESSION['userid'], "typ"))
	{
		$error = "Sie können keine Benutzer bearbeiten, die höhere Berechtigungen als Sie selbst haben!";
		ok_error();
		$no_edit = 1;
	}

	if(($edit || $new || $post) && !$no_edit)
	{
		if($new && !$post)
		{
			$active = 1;
			$showmail = make_check(1);
			$top = make_check(1);
		}
		if($edit && !$post)
		{
			$name = from_db("user", $edit, "name");
			$mail = from_db("user", $edit, "mail");
			$typ = from_db("user", $edit, "typ");
			$bday = from_db("user", $edit, "bday");
			$website = from_db("user", $edit, "website");
			$signatur = stripslashes(from_db("user", $edit, "signatur"));
			if($bday)
				$bday = date("d.m.Y", $bday);
			else
				unset($bday);
			$image = from_db("user", $edit, "image");
			$active = from_db("user", $edit, "active");
			$top = make_check(from_db("user", $edit, "top"));
			$showmail = make_check(from_db("user", $edit, "showmail"));
		}
		if($active)
			$active = " checked";
		else
			$active = "";
		if($edit)
			$add = 'bearbeiten';
		else
			$add = 'erstellen';

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
				$sel=" selected";
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
			if($delete && from_db("user", $delete, "typ") > from_db("user", @$_SESSION['userid'], "typ"))
			{
				$error = "Sie können keine Benutzer löschen, die höhere Berechtigungen als Sie selbst haben!";
				ok_error();
				$no_edit = 1;
			}
			else
			{
				if(@$_SESSION['userid'] == $delete)
				{
					$error = "Sie können sich nicht selbst löschen!";
					ok_error();
				}
				else
				{
					del_contentimg("user", $delete, from_db("user", $delete, "image"));
					$name = from_db("user", $delete, "name");
					if($pms_db_connection->query("DELETE FROM ".$pms_db_prefix."user WHERE id = ".$delete." LIMIT 1;"))
					{
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."comments SET user = '0', name = '$name' WHERE user = '$delete'");
						$ok = "Benutzer erfolgreich entfernt";
					}
					else
						$error = "Benutzer konnte nicht entfernt werden";
					ok_error();
				}
			}
		}
		echo heading("Benutzerverwaltung");
		echo '[<a href="admin.php?action='.$action.'&new=yes">Neuer Benutzer</a>]<br><br>';
		echo '<table class="group">';
		echo table_header("ID:30px|Name:100px|E-Mail:150px|Typ:120px|Letzter Login:90px|Registriert:80px|Registrations-IP:100px|Punkte:40px|Aktiviert:60px|Bearbeiten:80px|Löschen:65px");
		$link = $pms_db_connection->query(make_sql("user", "LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.user=".$pms_db_prefix."user.id)", $pms_db_prefix."user.id", $pms_db_prefix."user.*,count(".$pms_db_prefix."comments.id)*60+".$pms_db_prefix."user.points as points", $pms_db_prefix."user.id", 0));
		$num_user = 0;
		for($i=0; $link && $a=$pms_db_connection->fetchObject($link); $i++)
		{
			$act = "Nein";
			if($a->active == 1)
				$act = "Ja";
			if($a->register != 0)
				$register = date("d.m.Y", $a->register);
			else
				$register = "-";
			if($a->login != 0)
				$login2 = date("d.m.Y", $a->login);
			else
				$login2 = "-";

			$menu[$i][0] = $a->id;
			$menu[$i][1] = $a->name;
			$menu[$i][2] = "<a href=\"mailto:".$a->mail."\">".$a->mail."</a>";
			$menu[$i][3] = $user_typ[$a->typ];
			$menu[$i][4] = $login2;
			$menu[$i][5] = $register;
			$menu[$i][6] = $a->registerip;
			$menu[$i][7] = $a->points;
			$menu[$i][8] = $act;
			$menu[$i][9] = "<a href=\"admin.php?action=user&edit=".$a->id."\">Bearbeiten</a>";
			$menu[$i][10] = "<a href=\"admin.php?action=user&delete=".$a->id."\">Löschen</a>";
			$num_user = $i;
		}
		$num_user++;
		echo array_table($menu, 10);
		echo '
		<br>'.$num_user.' Benutzer registriert.';
	}
}

?>
