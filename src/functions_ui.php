<?php
// Module: functions_ui.php

	/**
	 * Display warning message box
	 *
	 * @param str Warning message
	 * @return string HTML warning box
	 */
	function warning_box($str)
	{
		return '<table class="warning"><tr><td>'.$str.'</td></tr></table>';
	}

	/**
	 * Generate selection list dropdown
	 *
	 * @param cur Current selection
	 * @param name List name/ID
	 * @return string HTML select dropdown
	 */
	function get_lists($cur,$name="list")
	{
		global $template_lists_folder;
		$dir=@opendir($template_lists_folder);
		if($dir)
			{
			$str="<select name=\"".$name."\"><option value=\"\"";
			if(!$cur) $str.=" selected";
			$str.=">&lt;Standard&gt;</option>";
			for($i=0,$j=0;($a=@readdir($dir))!==FALSE;$i++)
				{
				if($a=="." || $a==".." || strlen($a)>32) continue;
				$c=explode(".",$a);
				$c=strtolower($c[count($c)-1]);
				if($c!="php" && $c!="php5") continue;
				$check="";
				if($a==$cur) $check=" selected";
				$str.="<option value=\"".$a."\"".$check.">".SubStr($a,0,-strlen($c)-1)."</option>";
				$j++;
			}
			$str.="</select>";
			if($j) return $str;
			else return "";
			@closedir($dir);
		}
		return "";
	}

	/**
	 * Generate reference selection dropdown
	 *
	 * @param head Heading
	 * @param info Information
	 * @param what Reference type
	 * @param name Field name
	 * @param sort Sort field
	 * @param action Action parameter
	 * @param what2 Secondary reference type
	 * @return string HTML select dropdown
	 */
	function select_reference($head,$info,$what,$name="name",$sort="sort",$action="",$what2="")
	{
		global $pms_db_reference;
		global $pms_db_prefix;
		global $pms_db_connection;
		$str="";
		if(!$action) $action=$what;
		$link=$pms_db_connection->query("SELECT id,".$name." FROM ".$pms_db_reference.$what." ORDER BY ".$sort.",".$name);
		$link2=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix.$what." ORDER BY ".$sort.",".$name);
		$ok=0;
		if($link && mysqli_num_rows($link))
			{
			for($i=0;$link2 && $a=$pms_db_connection->fetchObject($link2);$i++) $ids[$i]=$a->id;
			$str.=form("","get")."<input type=\"hidden\" name=\"action\" value=\"".$action."\">
<table><tr><td>".$info."</td><td><select name=\"reference\">";
			while($a=$pms_db_connection->fetchObject($link))
				{
				if(@in_array($a->id,$ids)) continue;
				$ok++;
				$str.="<option value=\"".$a->id."\">".$a->$name."</option>";
			}
			$str.="</td></tr><tr><td colspan=\"2\"><div style=\"text-align:center;\"><input type=\"submit\" name=\"create\" value=\"Erzeugen\"></div></td></tr></table></form>";
		}
		if($what2) $what=$what2;
		if(!$ok)
			$str="Es ist kein Referenzobjekt angelegt. Legen Sie das Element zuerst im Haupt-PMS an!
<br><br>
[<a href=\"admin.php?action=".$what."\">Zurï¿½ck</a>]";
		$str=heading($head)."<br>".$str;
		return $str;
	}

	/**
	 * Generate back navigation button
	 * @return string HTML back button
	 */
	function back_button()
	{
		return "[<a href=\"javascript:history.back()\">Zurï¿½ck</a>]";
	}

	/**
	 * Generate HTML form opening tag
	 *
	 * @param on_submit Form submit action
	 * @param method HTTP method (post/get)
	 * @param add Additional HTML attributes
	 * @return string HTML form opening tag
	 */
	function form($on_submit="",$method="post",$add="")
	{
		if($add) $add="?".$add;
		if($on_submit) $on_submit=' onSubmit="'.$on_submit.'"';
		return '<form action="'.$_SERVER["PHP_SELF"].$add.'"'.$on_submit.' name="pms_form" method="'.$method.'" enctype="multipart/form-data" accept-charset="utf-8">';
	}

	/**
	 * Display OK or error message
	 * @return string|null HTML message display
	 */
	function ok_error()
	{
		global $ok;
		global $error;
		if(!$ok && !$error) return "";
		$class="info_ok";
		$str=$ok;
		if($error)
			{
			$class="info_error";
			$str=$error;
		}
		echo '<table class="'.$class.'"><tr><td>'.$str.'</td></tr></table><br>';
	}

	/**
	 * Convert array to HTML table
	 *
	 * @param a Array data
	 * @param b Column headers
	 * @return string HTML table
	 */
	function array_table($a,$b)
	{
		for($i=0;$i<($a ? count($a) : 0);$i++)
			{
			$color="";
			if($i%2==0)
				{
				$color=" bgcolor=\"#FFFFFF\"";
			}
			$str.="<tr".$color.">";
			for($j=0;$j<=$b;$j++)
				{
				$str.="<td>".$a[$i][$j]."</td>";
			}
			$str.="</tr>
";
		}
		$str.="</table>";
		return $str;
	}

	/**
	 * Display term definition
	 *
	 * @param str Definition text
	 * @return string|array<array-key, string> HTML definition display
	 */
	function def($str)
	{
		return str_replace("
","<br>",$str);
	}

	/**
	 * Generate styled table header
	 *
	 * @param str Header text
	 * @param style CSS style
	 * @return string Styled HTML table header
	 */
	function table_header_style($str,$style)
	{
		$str=explode("|",$str);
		if($style)
			{
			$style=" class=\"".$style."\"";
			$style1="<div".$style.">";
			$style2="</div>";
		}
		$a="<tr".$style.">";
		for($i=0;$i<count($str);$i++)
			{
			$b=explode(":",$str[$i]);
			$a=$a."<td width=\"".$b[1]."\">".$style1.$b[0].$style2."</td>";
		}
		$a=$a."</tr>";
		return $a;
	}

	/**
	 * Generate table header row
	 *
	 * @param str Header text
	 * @return string HTML table header
	 */
	function table_header($str)
	{
		return table_header_style($str,"");
	}

	/**
	 * Format text for use in URLs
	 *
	 * @param str Text to format
	 * @param ignore Characters to ignore
	 * @return string URL-safe formatted text
	 */
	function link_name($str,$ignore="")
	{
		$not=array();
		if($ignore)
			{
			for($i=0;$i<strlen($ignore);$i++) $not[$i]=SubStr($ignore,$i,1);
		}
		for($i=0;$i<256;$i++)
			{
			if(in_array(chr($i),$not)) continue;
			$array[0][$i]=chr($i);
			if($i<48) $array[1][$i]="_";
			else if($i<58) $array[1][$i]=chr($i);
			else if($i<65) $array[1][$i]="_";
			else if($i<91) $array[1][$i]=chr($i);
			else if($i<97) $array[1][$i]="_";
			else if($i<123) $array[1][$i]=chr($i);
			else $array[1][$i]="_";
		}
		$a=str_replace(array("ï¿½","ï¿½","ï¿½","ï¿½","ï¿½","ï¿½"),array("Ae","Oe","Ue","ae","oe","ue"),$str);
		$a=str_replace($array[0],$array[1],$a);
		for($i=10;$i>1;$i--)
			{
			$str="";
			for($j=0;$j<$i;$j++)$str.="_";
			$l[$i]=$str;
		}
		$a=str_replace($l,"_",$a);
		return(trim($a,"_"));
	}

	/**
	 * Generate marked navigation link with special handling
	 *
	 * @param name Link text
	 * @param add Additional parameters
	 * @param cat Category ID
	 * @param subcat Subcategory ID
	 * @param item Item ID
	 * @param mark Mark parameter
	 * @param class CSS class
	 * @param id HTML ID
	 * @param target Link target
	 * @param close Close tag flag
	 * @return string HTML anchor link with marks
	 */
	function make_link_mark($name,$add,$cat,$subcat,$item,$mark="",$class="",$id=0,$target="",$close=1)
	{
		global $config_values;
		$special=$config_values->speciallinks;
		$site=$config_values->page."/";
		if($class)
			$class=" class=\"".$class."\"";
		
		if($target)
			$target=" target=\"".$target."\"";
		
		$start="<a".$class.$target." href=\"";
		$a="index.php";
		$b=0;
		if($add)
			{
			$b=1;
			$a=$a."?".$add;
			if($special)
				{
				$temp=explode("&",$add);
				if($temp[0]=="action=user") 
					{
					$temp2=explode("=",$temp[1]);
					$a=$site."content/".link_name(from_db("user",$temp2[1],"name"))."-".$temp2[1]."u".".html";
				}
				elseif(substr($temp[0],0,6)=="action" && substr($temp[0],-6)!="logout")
					{
					$add2=$temp;
					unset($add2[0]);
					$add2=implode("&",$add2);
					if($add2) $add2="?".$add2;
					$a=$site."action/".substr($temp[0],7).".html".$add2;
				}
				else $end=$add;
			}
			
		}
		if($item)
			{
			if($b==0)
				{
				$a=$a."?";
			}
			else
				{
				$a=$a."&";
			}
			$b=1;
			$a=$a."item=".$item;
			if($special) $a=$site."content/".link_name(from_db("item",$item,"name"))."-".$item.".html";
			
		}
		else if($subcat)
			{
			if($b==0)
				{
				$a=$a."?";
			}
			else
				{
				$a=$a."&";
			}
			$b=1;
			$a=$a."subcat=".$subcat;
			if($special) $a=$site."content/".link_name(from_db("subcat",$subcat,"name"))."-".$subcat."s".".html";
		}
		else if($cat)
			{
			if($b==0)
				{
				$a=$a."?";
			}
			else
				{
				$a=$a."&";
			}
			$b=1;
			$a=$a."cat=".$cat;
			if($special) $a=$site."content/".link_name(from_db("cat",$cat,"name"))."-".$cat."c".".html";
		}
		if($special) $b=0;
		if($id)
			{
			if($b==0)
				{
				$a=$a."?";
			}
			else
				{
				$a=$a."&";
			}
			$b=1;
			$a=$a."id=".$id;
		}
		if($mark)
			$mark="#".$mark;
		if($end)
			{
			if(!$b) $end="?".$end;
			else $end="&".$end;
		}
		$a=$a.$end.$mark."\">".$name;
		if($close) $a.="</a>";
		return $start.$a;
	}

	/**
	 * Generate navigation link
	 *
	 * @param name Link text
	 * @param add Additional parameters
	 * @param cat Category ID
	 * @param subcat Subcategory ID
	 * @param item Item ID
	 * @param class CSS class
	 * @param id HTML ID
	 * @param target Link target
	 * @return string HTML anchor link
	 */
	function make_link($name,$add,$cat=0,$subcat=0,$item=0,$class="",$id=0,$target="")
	{
		return make_link_mark($name,$add,$cat,$subcat,$item,"",$class,$id,$target);
	}

?>
