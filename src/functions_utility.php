<?php
// Module: functions_utility.php

/**
 * Create editable dynamic string with hover effect
 *
 * @param str String content
 * @param edit Edit mode flag
 * @param id Content ID
 * @return string Styled dynamic string
 */
function dynamic_string($str,$edit,$id){
	if(!$edit) return $str;
	
	return '<style type="text/css">
.edit_var_layer{
border:1px solid #faa;
box-shadow: 2px 2px 6px rgba(0, 0, 0,0.5);
    -webkit-box-shadow:  2px 2px6px rgba(0, 0, 0,0.5);
    -moz-box-shadow:  2px 2px 6px rgba(0, 0, 0,0.5);
transition: all 0.5s ease-in-out;
-webkit-transition: all 0.5s ease-in-out;
-moz-transition: all 0.5s ease-in-out;
background-position:100% 0%;
width:100%;
}
.edit_var_layer_hover, .edit_var_layer:hover{
border:1px solid #800;
width:100%;
background-color:rgba(255,0,0,0.4);
*background-color:#f00;
background-image:url(images/edit.png);
background-position:100% 0%;
background-repeat:no-repeat;
min-height:30px;
cursor:help ;
*cursor:hand;
transition: all 0.5s ease-in-out;
-webkit-transition: all 0.5s ease-in-out;
-moz-transition: all 0.5s ease-in-out;
box-shadow: 2px 2px 6px rgba(0, 0, 0,0.5);
    -webkit-box-shadow:  2px 2px6px rgba(0, 0, 0,0.5);
    -moz-box-shadow:  2px 2px 6px rgba(0, 0, 0,0.5);
}
</style>
<div class="edit_var_layer" onmouseover="this.className=\'edit_var_layer_hover\';" onmouseout="this.className=\'edit_var_layer\'" title="'.language("EDIT_VAR").'" onclick="document.location=\'admin.php?action=var&edit='.$id.'\';">'.$str.'</div>';
}

/**
 * Get visitor statistics for last 24 hours
 *
 * @param filter Filter bots flag
 * @return array Visitor statistics
 */
function get_24_stats($filter=0){
	global $pms_db_connection;
	$link=$pms_db_connection->query(make_sql("visitors_counter","","time DESC"));
	$i=0;
	while($link && $a=$pms_db_connection->fetchObject($link))
		{
		if($filter && (stripos($a->browser,"bot") || stripos($a->browser,"spider") || stripos($a->browser,"crawler") || stripos($a->browser,"monitor"))) continue;
		$stats[$i]=$a;
		$i++;
	}
	return $stats;
}

/**
 * Store current GET/POST data in session
 * @return void
 */
function store_all()
{
	global $_GET;
	global $_POST;
	global $_SESSION;
	if($_GET["action"]=="logout") unset($_GET["action"]);
	if(is_array($_GET)) foreach($_GET as $k=>$v) $_SESSION["STORE_GET"][$k]=$v;
	if(is_array($_POST))foreach($_POST as $k=>$v) $_SESSION["STORE_POST"][$k]=$v;
	
}

/**
 * Restore stored GET/POST data from session
 *
 * @param do Operation: 0=check, -1=clear, 1=restore
 * @return int|void Count of stored data or void
 */
function reload_all($do=0)
{
	global $_GET;
	global $_POST;
	global $_SESSION;
	// In fact, it doesn't make sense to return true if there are only GET-based actions, so there is only post
	if($do==0) return isset($_SESSION["STORE_POST"]) ? (count($_SESSION["STORE_POST"])) : 0;
	if($do==-1)
		{
		unset($_SESSION["STORE_GET"]);
		unset($_SESSION["STORE_POST"]);
		return;
	}
	unset($_GET);unset($_POST);
	$_GET=array();
	$_POST=array();
	if(is_array($_SESSION["STORE_GET"]))foreach($_SESSION["STORE_GET"] as $k=>$v) $_GET[$k]=$v;
	if(is_array($_SESSION["STORE_POST"]))foreach($_SESSION["STORE_POST"] as $k=>$v) $_POST[$k]=$v;
	unset($_SESSION["STORE_GET"]);
	unset($_SESSION["STORE_POST"]);
}

/**
 * Convert action type to display string
 *
 * @param typ Action type
 * @param con Action context
 * @return string Action description
 */
function convert_action($typ,$con)
{
	global $action_name;
	global $action_list;
	global $valid_actions;
	if($typ==0)
		return "Kategorie: ".make_link(from_db("cat",$con,"name"),"",$con,0,0,"",0,"_blank");
	if($typ==1)
		return "Unterkategorie: ".make_link(from_db("subcat",$con,"name"),"",0,$con,0,"",0,"_blank");
	if($typ==2)
		return "Inhalt: ".make_link(from_db("item",$con,"name"),"",0,0,$con,"",0,"_blank");
	if($typ==3)
		{
		$name=$valid_actions[$con];
		if($name!="user" && $name!="download") $name=make_link($name,"action=".$name,0,0,0,"",0,"_blank");
		return "Plugin: ".$name;
	}
	if($typ==4)
		return 'PMS Administration (<a href="admin.php?action='.$action_list[$con].'">'.$action_name[$con].'</a>)';
}

/**
 * Check if subcategory should jump to item
 *
 * @param check Subcategory ID
 * @return void Sets global subcat_jump flag
 */
function check_subcatjump($check)
{
	global $login;
	global $subcat_jump;
	global $item;
	$subcat_jump=0;
	$add=" AND available = '1' AND visible = '1'";
	if($login && from_db("user",$_SESSION['userid'],"typ")>=2) unset($add);
	if($check && from_db("subcat",$check,"jump") && count_db_exp("item","WHERE subcat = '".$check."'".$add)==1)
		{
		if(!$item) $item=from_db("item",$check,"id",1,"subcat",$add);
		$subcat_jump=1;
	}
}

/**
 * Generate HTML header for error pages
 * @return string HTML header markup
 */
function header_def()
{
	return '<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><link rel="stylesheet" type="text/css" href="admin.css"><title>PMS - Seitenladefehler</title></head><body><table width="100%" height="100%"><tr><td><div align="center"><h1>';
}

	/**
	 * Copy item from reference database
	 *
	 * @param what Item type
	 * @param id Item ID
	 * @return int Success flag
	 */
	function copy_reference($what,$id)
	{
		global $pms_db_reference;
		global $pms_db_prefix;
		global $pms_db_connection;
		$a=$pms_db_connection->query("INSERT INTO ".$pms_db_prefix.$what." SELECT * FROM ".$pms_db_reference.$what." WHERE id = '$id'");
		if($a) return 1;
		global $error;
		$error="Fehler beim Duplizieren<br>Technische Information: ".mysqli_error();
		ok_error();
		return 0;
	}

	/**
	 * Load hidden form field values from POST
	 * @return void Sets global variables
	 */
	function load_hidden()
	{
		global $cat;
		global $subcat;
		global $item;
		global $content_page;
		global $action;
		global $id;
		global $_POST;
		$cat=$_POST['cat'];
		$subcat=$_POST['subcat'];
		$item=$_POST['item'];
		$content_page=$_POST['content_page'];
		$action=$_POST['action'];
		$id=$_POST['id'];
	}

	/**
	 * Generate hidden form fields for positions
	 * @return string HTML hidden input fields
	 */
	function hidden_positions()
	{
		global $cat;
		global $subcat;
		global $item;
		global $content_page;
		global $action;
		global $id;
		return "<input type=\"hidden\" name=\"cat\" value=\"".$cat."\">
<input type=\"hidden\" name=\"subcat\" value=\"".$subcat."\">
<input type=\"hidden\" name=\"item\" value=\"".$item."\">
<input type=\"hidden\" name=\"content_page\" value=\"".$content_page."\">
<input type=\"hidden\" name=\"action\" value=\"".$action."\">
<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
	}

	/**
	 * Get list of available backup files
	 * @return array Backup directory names
	 */
	function get_backups()
	{
		global $backup_folder;
		$dir=@opendir(SubStr($backup_folder,0,-1));
		$i=0;
		if($dir)
			{
			while (($file = @readdir($dir)) !== false)
				{
				if($file=="." || $file==".." || strstr($file,"."))
					{
					continue;
				}
				$back[$i]=$file;
				$i++;
			}
			@closedir($dir);
		}
		if($back) {
			@rsort($back);
		}
		return $back;
	}

	/**
	 * Recover item from backup
	 *
	 * @param id Item ID
	 * @param mode Recovery mode
	 * @param ids IDs to skip
	 * @return array|int Recovery options or 0
	 */
	function recover_item($id="",$mode=0,$ids="")
	{
		global $pms_db_prefix;
		global $backup_folder;
		$back=get_backups();
		if(!$back) return 0;
		$i=0;
		foreach($back as $b)
			{
			$a=utf8_decode(trim(@file_get_contents($backup_folder.$b."/Database.sql")));
			$a=explode(chr(10),$a);
			foreach($a as $line)
				{
				if(substr($line,0,12)!="INSERT INTO ") continue;
				$c=explode(" (",$line);
				if(substr($c[0],-4)!="item") continue;
				if(!$mode && !strstr($line,"VALUES ('$id',")) continue;
				unset($c[0]);
				$line=implode(" (",$c);
				$line="INSERT INTO ".$pms_db_prefix."item (".$line;
				$date=explode("_",$b);
				if(count($date)<5) $date="Nicht Auslesbar"; else $date=$date[2].".".$date[1].".".$date[0]." - ".$date[3].":".$date[4];
				$temp=explode(" ('",$line);
				$temp=explode("','",$temp[1]);
				if($mode && @in_array($temp[0],$ids)) continue;
				$found[$i][0]=$date;
				$found[$i][1]=$line;
				$found[$i][2]=$temp[0]; //id
				$found[$i][3]=$temp[3];
				$i++;
			}
		}
		if(!$i) return 0;
		return $found;
	}

	/**
	 * Export database backup
	 * @return array Export result statistics
	 */
	function do_export()
	{
		ini_set("max_execution_time","3600");
		global $pms_db_prefix;
		global $backup_folder;
		global $pms_db_connection;
		$sys_tables=array('bans','cat','comments','config','dynamic','item','menu','poll','subcat','user');
		for($i=0;$i<count($sys_tables);$i++)
			{
			$sys_tables[$i]=$pms_db_prefix.$sys_tables[$i];
		}
		global $pms_version;
		ini_set("max_execution_time",6000); // Export does need a lot of time
		umask(0);
		@mkdir(SubStr($backup_folder,0,-1));
		$time=date("Y")."_".date("m")."_".date("d")."_".date("H")."_".date("i")."_".rand(10000,99999);
		$exp="-- MYSQL Dump erzeugt von PMS, Version ".$pms_version."
-- Hinweis: Dies ist ein sehr einfacher MYSQL-Export. Wenn Sie einen besseren Export benï¿½tigen, nutzen Sie bitte den phpMyAdmin
-- Bitte seien Sie sich bewusst, dass wir keinerlei Garantie fï¿½r den erfolgreichen Export/Import zwischen verschiedenen PMS-Versionen geben kï¿½nnen
-- Wï¿½hlen Sie diesen Export wï¿½hrend des Installationsvorgangs aus, um ihn wiederherzustellen
-- www.TSGames.de?item=236 fï¿½r weitere Informationen ï¿½ber PMS
		
-- Nur fï¿½r phpMyAdmin-Import:
-- Entfernen Sie die Kommentare vor den Folgenden Zeilen, um alle alten Daten vor dem Einlesen zu lï¿½schen.
";
		
		$result = $pms_db_connection->fetchAllObject($pms_db_connection->list_tables());
		foreach($result as $r)
			{
			$tablename = $r->name;
			if(!in_array($tablename,$sys_tables))
				{
				continue; // we don't need to export the visitors
			}
			$exp.="-- TRUNCATE TABLE ".$tablename.";
	";
		}
		$exp.=chr(10);
		foreach($result as $r)
			{
			$table=$r->name;
			if(!in_array($table,$sys_tables))
				{
				continue; // we don't need to export the visitors
			}
			$exp=$exp."-- Daten fï¿½r Tabelle ".$table.chr(10);
			$link=$pms_db_connection->query("SELECT * FROM ".$table);
			if($link)
				{
				unset($field_name);
				$end=$link->numColumns();
				for($j=0;$j<$end;$j++)
					{
					$field_name[$j] = $link->columnName($j);
				}
				$dump = $pms_db_connection->fetchAllObject($link);
				foreach($dump as $d)
					{
					$exp.="INSERT INTO ".$table." (";
					for($k=0;$k<count($field_name);$k++)
						{
						$exp.=$field_name[$k];
						if($k!=count($field_name)-1)
							{
							$exp.=",";
						}
					}
					$exp.=") VALUES (";
					for($k=0;$k<count($field_name);$k++)
						{
						$exp=$exp."'".str_replace(array(chr(10),chr(13),"'"),array('\n','\r',"''"),$d->{$field_name[$k]})."'";
						if($k!=count($field_name)-1)
							{
							$exp.=",";
						}
					}
					$exp.=");
";
				}
			}
			$exp.=chr(10);
		}
		@mkdir($backup_folder.$time);
		$file=fopen($backup_folder.$time."/Database.sql","w+");
		fwrite($file,utf8_encode($exp));
		fclose($file);
		@mkdir($backup_folder.$time."/images");
		unset($a);
		$a[0]="images/subcat";
		$a[1]="images/item";
		$a[2]="images/user";
		$a[3]="images/uploads";
		$a[4]="template_files";
		$result=array(0,0);
		for($i=0;$i<count($a);$i++)
			{
			copy_recrusive($a[$i],$backup_folder.$time."/".$a[$i],$result);
		}
		$result[0]+=copy("/var/template/template.html",$backup_folder.$time."/template.html");
		$result[1]++;
		return $result;
	}

	/**
	 * Generate random alphanumeric string
	 *
	 * @param length String length
	 * @return string Random string
	 */
	function random_type($length){
		for($i=0;$i<$length;$i++){
			$x=rand(0,9);
			if($x>=10) $x=chr($x+87);
			$str.=$x;
		}
		return $str;
	}

	/**
	 * Generate RSS feed URL
	 *
	 * @param auto Auto-detect category
	 * @param c Category ID
	 * @param s Subcategory ID
	 * @return string RSS feed URL
	 */
	function get_rss($auto=1,$c=0,$s=0)
	{
		if($auto)
			{
			global $cat;global $subcat;
			$c=$cat;
			$s=$subcat;
		}
		global $config_values;
		if($config_values->speciallinks)
			{
			$str=$config_values->page.'/rss/';
			if($s) return $str.link_name(from_db("subcat",$s,"name")).'-'.$s.'s.rss';
			if($c) return $str.link_name(from_db("cat",$c,"name")).'-'.$c.'c.rss';
			return $str.link_name($config_values->name).'.rss';
		}
		if($s) return 'rss.php?subcat='.$s;
		if($c) return 'rss.php?cat='.$c;
		return 'rss.php';
	}

	/**
	 * Remove slashes and fix HTML entities
	 *
	 * @param str String to process
	 * @return string Processed string
	 */
	function my_stripslashes($str)
	{
		$str=stripslashes($str);
		$str=str_replace(array("&lt;","&gt;"),array("&amp;lt;","&amp;gt;"),$str);
		return $str;
	}

	/**
	 * Clear user session data and login cookie
	 * @return void
	 */
	function delete_sessions()
	{
		global $cookie_domain;
		unset($_SESSION['userid']);
		unset($_SESSION['usertyp']);
		unset($_SESSION['pmsglobal']);
		unset($_SESSION['loginip']);
		unset($_SESSION['last_login']);
		unset($_SESSION['update_notice']);
		@setcookie("login_pw","",time()-3600,"/",$cookie_domain);
		$_COOKIE["login_pw"]="";
	}

	/**
	 * Calculate and format time difference
	 *
	 * @param time Past timestamp
	 * @return string Formatted time difference
	 */
	function time_diff($time)
	{
		$diff=time()-$time;
		$sec=$diff%60;
		$min=intval($diff/60)%60;
		$hour=intval($diff/3600);
		if($sec!=1) $s="n";
		if($min!=1) $m="n";
		if($hour!=1) $h="n";
		if($hour) $str=$hour." Stunde".$h.", ";
		if($min || $hour) $str.=$min." Minute".$m." und ";
		$str.=$sec." Sekunde".$s;
		return $str;
	}

	/**
	 * Get browser information from user agent
	 *
	 * @param a User agent string
	 * @return string Browser name and version
	 */
	function browser($a)
	{
		return $a;
		if(strstr($a,"Firefox"))
			{
			$b=explode("Firefox/",$a);
			return "Firefox ".$b[1];
		}
	}

	/**
	 * Generate config section spacing HTML
	 *
	 * @param str Section title
	 * @return string HTML spacing markup
	 */
	function config_space($str="")
	{
		if($str=="") return "</table><br></td></tr>";
		return "<tr><td colspan=\"2\"><span style=\"color:#A0A0A0;font-weight:bold;\">".$str."</span></td></tr><tr><td colspan=\"2\"><table width=\"100%\" style=\"border:1px solid #A0A0A0;\">";
	}

	/**
	 * Calculate age from birthdate timestamp
	 *
	 * @param age Birthdate timestamp
	 * @return int Age in years
	 */
	function make_age($age)
	{
		//$a=intval(date(Y,time()-$age+86400));
		//if($a<0) $a=(70+$a)+67;
		$a=date("Y")-date("Y",$age);
		if(date("m",$age)>date("m") || date("m",$age)==date("m") && date("d",$age)>date("d")) $a--;
		return $a;
	}

	/**
	 * Format timestamp for display
	 *
	 * @param time Unix timestamp
	 * @param long Long format flag
	 * @param am Show AM/PM flag
	 * @param bypass Bypass relative format
	 * @return string Formatted date and time
	 */
	function make_date($time,$long,$am=0,$bypass=0)
	{
		
		$check1=intval($time/86400);
		$check2=intval(time()/86400);
		if(!$bypass && ($check1==$check2 || $check1==$check2-1 || $check1==$check2-2))
			{
			if($check1==$check2)
				$format=language("DAY_TODAY");
			elseif($check1==$check2-1)
				$format=language("DAY_ONE_AGO");
			elseif($check1==$check2-2)
				$format=language("DAY_TWO_AGO");
			
			if(strstr($format,"%1"))
				$str=str_replace("%1",make_date($time,$long,$am,1),$format);
			else
				$str=$format;
			$str.=",";
			if($long)
				{
				$str.=" ".language("TIME_AT");
			}
			$str.=" ".date(language("TIME_FORMAT"),$time);
			if($long)
				{
				$str.=" ".language("TIME_ADD");
			}
			return $str;
		}
		if($long)
			{
			$str=language("DATE_AT")." ".get_weekday($time).", ".language("DATE_ADD")." ";
		}
		$a=language("DATE_AT")." ";
		if(!$am)
			{
			unset($a);
		}
		$str.=$a.date(language("DATE_FORMAT"),$time);
		if(!$bypass) $str.=", ".date(language("TIME_FORMAT"),$time);
		if($long && !$bypass) $str.=" ".language("TIME_ADD");
		return $str;
	}

	/**
	 * Get error page item ID
	 * @return int Error page item ID
	 */
	function get_errorpage()
	{
		global $pms_db_prefix;
		global $pms_db_connection;
		$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE special = '3' LIMIT 1;");
		if($link)
			{
			$a=$pms_db_connection->fetchObject($link);
			$item=$a->id;
		}
		if(!$item) // No error? Get Frontpage
		{
			$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."item WHERE special = '1' LIMIT 1;");
			if($link)
				{
				$a=$pms_db_connection->fetchObject($link);
				$item=$a->id;
			}
		}
		return $item;
	}

	/**
	 * Generate checked attribute for checkboxes
	 *
	 * @param str Check value
	 * @return string " checked" or empty
	 */
	function make_check($str)
	{
		if(!$str)
			{
			return "";
		}
		return " checked";
	}

		/**
		 * Display star rating
		 *
		 * @param item Item ID
		 * @param what Item type
		 * @param num Number of ratings
		 * @param rating Rating value
		 * @param force Force display
		 * @return string HTML star rating
		 */
		function make_rating($item,$what="item",$num=0,$rating=0,$force=0)
		{
			global $pms_db_prefix;
			global $config_values;
			global $pms_db_connection;
			if(!$force)
				{
				if(!$config_values->rate || (!from_db("item",$item,"rate") && $what=="item") || ($what=="subcat" && count_db_exp("item","subcat = '$item' AND rate = '1'")<0))
					{
					return "";
				}
			}
			if($what=="item")
				{
				if($num) $a=$num;
				else $a=from_db("item",$item,"numratings");
				if($a)
					{
					if($rating) $rated=$rating;
					else $rated=from_db("item",$item,"rating");
					$rated/=$a;
				}
				else
					{
					$rated=0;
				}
			}
			if($what=="subcat")
				{
				$link=$pms_db_connection->query("SELECT SUM(numratings) FROM ".$pms_db_prefix."item WHERE subcat = '$item';");
				if($link)
					{
					$a=$pms_db_connection->fetch($link);
					$a=$a[0];
				}
				if($a)
					{
					$link=$pms_db_connection->query("SELECT SUM(rating) FROM ".$pms_db_prefix."item WHERE subcat = '$item';");
					if($link)
						{
						$b=$pms_db_connection->fetch($link);
						$b=$b[0];
					}
					$rated=$b/$a;
				}
			}
			$str="";
			$alt="Bewertung: ".round($rated,1);
			for($i=1;$i<6;$i++)
				{
				$b="0";
				if($rated>=$i-0.75)
					{
					$b="05";
				}
				if($rated>=$i-0.25)
					{
					$b="1";
				}
				$str.=make_imgalt("rate".$b.".gif",0,$alt,"template_files/","rate",$alt);
			}
			return $str;
		}

				/**
				 * Get weekday name from timestamp
				 *
				 * @param time Unix timestamp
				 * @return string Localized weekday name
				 */
				function get_weekday($time)
				{
					
					$week_count=date("w",$time);
					if($week_count==0)
						{
						return language("WEEKDAY_SUNDAY");
					}
					if($week_count==1)
						{
						return language("WEEKDAY_MONDAY");
					}
					if($week_count==2)
						{
						return language("WEEKDAY_TUESDAY");
					}
					if($week_count==3)
						{
						return language("WEEKDAY_WEDNESDAY");
					}
					if($week_count==4)
						{
						return language("WEEKDAY_THURSDAY");
					}
					if($week_count==5)
						{
						return language("WEEKDAY_FRIDAY");
					}
					if($week_count==6)
						{
						return language("WEEKDAY_SATURDAY");
					}
				}

?>
