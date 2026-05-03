<?php
// Module: functions_content.php

	/**
	 * Sanitize user-submitted content
	 *
	 * @param content Raw content to clean
	 * @return string Cleaned content
	 */
	function cleanup_content($content)
	{
		$content=explode('<img id="##pms_replace_image_temp"',$content);
		if(count($content)>1)
			{
			$c_new=$content[0];
			for($i=1;$i<count($content);$i++)
				{
				$add=explode(">",$content[$i]);
				unset($add[0]);
				$add=implode(">",$add);
				$c_new.=$add;
			}
		}
		else
			$c_new=$content[0];
		return $c_new;
	}

	/**
	 * Strip HTML tags from string
	 *
	 * @param str String with HTML
	 * @return string Plain text without HTML
	 */
	function remove_html($str)
	{
		return str_replace(array('&lt;','&gt;','&nbsp;','<br>'),array('<','>',' ','
'),$str);
	}

	/**
	 * Replace dynamic placeholders with content
	 *
	 * @param str Content string
	 * @param smiley Replace smileys flag
	 * @return string Content with placeholders replaced
	 */
	function make_dynamic($str,$smiley=1)
	{
		global $special_tags;
		global $cat;
		global $subcat;
		global $item;
		global $content_page;
		global $action;
		global $id;
		global $number_visitors;
		global $pms_db_connection;
		for($j_zaehler=0;$j_zaehler<count($special_tags);$j_zaehler++)
			{
			$str2=explode($special_tags[$j_zaehler][0],$str);
			if($smiley) $str=smileys($str2[0]);
			for($i_zaehler=1;$i_zaehler<count($str2);$i_zaehler++)
				{
				$str3=explode($special_tags[$j_zaehler][1],$str2[$i_zaehler]);
				if($j_zaehler==0)
					{
					$str.="<table width=\"100%\"><tr><td width=\"100%\" class=\"code\">".$str3[0]."</td></tr></table>";
				}
				if($j_zaehler==1)
					{
					try {
						eval('ob_start();' .remove_html($str3[0]).'$str.=ob_get_clean();');
					}catch(ParseError|Exception $e) {
						$str.=print_r($e, true);
					}
				}
				if($smiley) $str.=smileys($str3[1]);
			}
		}
		return $str;
	}

	/**
	 * Legacy dynamic content replacement
	 *
	 * @param str Content string
	 * @return string Processed content
	 */
	function make_dynamicold($str)
	{
		global $special_tags;
		for($j_zaehler=0;$j_zaehler<count($special_tags);$j_zaehler++)
			{
			$str2=explode($special_tags[$j_zaehler],$str);
			$str=smileys($str2[0]);
			for($i_zaehler=1;$i_zaehler<count($str2);$i_zaehler++)
				{
				if($i_zaehler%2==1)
					{
					if($j_zaehler==0)
						{
						$str.="<table width=\"100%\"><tr><td width=\"100%\" div class=\"code\">".$str2[$i_zaehler]."</td></tr></table>";
					}
					if($j_zaehler==1)
						{
						eval('ob_start();'.str_replace(array("<br>","<br />","<br/>"),"",$str2[$i_zaehler]).'$str.=ob_get_clean();');
					}
				}
				else
					{
					$str.=smileys($str2[$i_zaehler]);
				}
			}
		}
		return $str;
	}

	/**
	 * Format comment text
	 *
	 * @param str Comment text
	 * @return string Formatted comment
	 */
	function small_comment($str)
	{
		global $config_values;
		$max_length=$config_values->latest_comments_chars;
		$diff=intval($max_length/10);
		$words=explode(" ",$str);
		$length=0;
		if(strlen($str)<=$max_length) return($str);
		for($i=0;$i<count($words);$i++)
			{
			$w_length=strlen($words[$i]);
			if($length+$w_length>$max_length)
				{
				if($length<$max_length-$diff)
					$str_new.=SubStr($words[$i],0,($max_length-$length))." ";
				break;
			}
			$length+=$w_length+1;
			$str_new.=$words[$i]." ";
		}
		return SubStr($str_new,0,-1)."...";
	}

	/**
	 * Clean comment content
	 *
	 * @param str Comment text
	 * @return string Cleaned comment
	 */
	function clear_comment($str)
	{
		$search=array('<','>','&lt;br&gt;');
		$replace=array('&lt;','&gt;','<br>');
		return str_replace($search,$replace,$str);
	}

	/**
	 * Get comment text from array
	 *
	 * @param a Comment array
	 * @return string Comment text
	 */
	function get_comment_str($a)
	{
		
		if($a==0)
			$b=language("COMMENTS_SHOW_NONE");
		else if($a==1)
			$b=language("COMMENTS_SHOW_ONE");
		else if($a>1)
			$b=str_replace("%1",$a,language("COMMENTS_SHOW_MULTI"));
		return $b;
	}

	/**
	 * Retrieve comments for item
	 *
	 * @param id Item ID
	 * @return array Array of comments
	 */
	function get_comments($id)
	{
		global $config_values;
		if($config_values->comments && from_db("item",$id,"comments"))
			{
			$a=count_db_exp("comments","WHERE item = '$id'");
			$b=get_comment_str($a);
			
			return make_link_mark($b,"comments=all","","",$id,"comments");
		}
		return "";
	}

	/**
	 * Generate small content preview
	 *
	 * @param what Content type
	 * @param id Content ID
	 * @param img Include image flag
	 * @return string HTML content preview
	 */
	function make_contentsmall($what,$id,$img=1)
	{
		global $pms_db_prefix;
		global $config_values;
		global $pms_db_connection;
		$sel_what="*";
		if($what=="item" && $config_values->commentssmall && $config_values->comments)
			{
			$select=" LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.item=".$pms_db_prefix."item.id)";
			$sel_what=$pms_db_prefix."item.*,COUNT(".$pms_db_prefix."comments.id) AS comments_num ";
			$sel_group=$pms_db_prefix."item.id";
		}
		$link=$pms_db_connection->query(make_sql($what.$select,$pms_db_prefix.$what.".id = '$id'",$pms_db_prefix.$what.".id LIMIT 1",$sel_what,$sel_group));
		if($link)
			{
			$a=$pms_db_connection->fetchObject($link);
			if(!$a->id)
				{
				return "";
			}
			$link1="cat";
			if($what=="subcat")
				{
				$link2="id";
				$link3=0;
				$rate=make_rating($id,$what);
				if($rate)
					{
					$rate="<tr><td>".$rate;
				}
			}
			if($what=="item")
				{
				$link2="subcat";
				$link3="id";
				$rate=make_rating($id,"item",$a->numratings,$a->rating);
				if($rate)
					{
					$rate="<tr><td>".$rate;
				}
				if($config_values->commentssmall && $config_values->comments)
					{
					$comments=make_link_mark(get_comment_str($a->comments_num),"comments=all","","",$id,"comments","num_comments");
					if($comments && $rate)
						{
						$rate.=" (".$comments.")";
					}
					elseif($comments)
						{
						$rate="<tr><td>(".$comments.")";
					}
				}
			}
			if($rate)
				{
				$rate.="</td></tr>";
			}
			$str="<table><tr>";
			if($img)
				{
				$li=make_contentimg($what,$a->id,$a->image,0);
				if($li) $li=make_link($li,"",$a->$link1,$a->$link2,$a->$link3);
				$str.="<td rowspan=\"3\" height=\"64px\" width=\"78px\" style=\"text-align:center;\">".$li."
</td>";
			}
			$str.="<td class=\"item_link\">".make_link($a->name,"",$a->$link1,$a->$link2,$a->$link3)
."</td></tr>".$rate."<tr valign=\"top\"><td class=\"description\">
".make_dynamic(def($a->description))."</td></tr></table>
";
		}
		return $str;
	}

		/**
		 * Replace text smileys with emoji/images
		 *
		 * @param str Text with smileys
		 * @return string Text with smileys replaced
		 */
		function smileys($str)
		{
			if(!from_db("config",1,"smileys")) return $str;
			$s[0][0]="<input ";
			$s[0][1]=">";
			$s[1][0]="<textarea";
			$s[1][1]="</textarea>";
			$smiley_search=array(
':)',':-)'
				,':D',':-D',':d',':-D'
				,';)',';-)',
':(',':-(',
':\'(',
':|',':-|',
':P',':-P',':p',':-p');
				$smiley_replace=array(
					make_img("smileys/1.gif",0),make_img("smileys/1.gif",0),
					make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),make_img("smileys/2.gif",0),
					make_img("smileys/3.gif",0),make_img("smileys/3.gif",0),
					make_img("smileys/4.gif",0),make_img("smileys/4.gif",0),
					make_img("smileys/5.gif",0),
					make_img("smileys/6.gif",0),make_img("smileys/6.gif",0),
					make_img("smileys/7.gif",0),make_img("smileys/7.gif",0),make_img("smileys/7.gif",0),make_img("smileys/7.gif",0));
					if(!strstr($str,$s[0][0]) && !strstr($str,$s[1][0]))
						{
						return str_replace($smiley_search,$smiley_replace,$str);
					}
					
					for($j=0;$j<count($s);$j++)
						{
						$str2=explode($s[$j][0],$str);
						//$str2[0]=str_replace($smiley_search,$smiley_replace,$str2[0]);
						for($i=0;$i<count($str2);$i++)
							{
							$str3=explode($s[$j][1],$str2[$i]);
							for($k=1;$k<count($str3);$k++)
								{
								if(strstr($str3[$k],$s[0][0]) || strstr($str3[$k],$s[1][0]))
									{
									$k++;
									continue;
								}
								$str3[$k]=str_replace($smiley_search,$smiley_replace,$str3[$k]);
							}
							$str2[$i]=implode($s[$j][1],$str3);
						}
						$str=implode($s[$j][0],$str2);
					}
					return $str;
				}

				/**
				 * Build limit conditions for search
				 *
				 * @param who Search target
				 * @param exp Search expression
				 * @return string SQL LIMIT clause
				 */
				function make_limits_search($who,$exp)
				{
					global $cat;
					global $subcat;
					global $item;
					global $content_page;
					global $page_limit;
					global $start_page;
					global $action;
					global $search_query;
					global $pms_db_prefix;
					global $pms_db_connection;
					
					$link=$pms_db_connection->query("SELECT COUNT(id) FROM ".$pms_db_prefix.$who." WHERE ".$exp.";");
					if($link && $a=$pms_db_connection->fetch($link))
						{
						if($a[0]<=$page_limit)
							{
							return "";
						}
						$count=$a[0]/$page_limit;
						if($count-intval($count)!=0)
							{
							$count=intval($count)+1;
						}
						$add=" ...";
						if($content_page>$count)
							{
							$content_page=$count;
							$start_page=($content_page-1)*$page_limit;
						}
						$s=$content_page-5;
						$e=$content_page+5;
						if($e>=$count)
							{
							$e=$count;
							$add="";
						}
						if($s<1)
							{
							$s=1;
						}
						for($i=$s;$i<=$e;$i++)
							{
							if($i!=$s)
								{
								$str.=" | ";
							}
							else
								{
								$str.="Seite: ";
								if($i>1)
									{
									$str.="... ";
								}
							}
							if($i==$content_page)
								{
								$str.=$i;
							}
							else
								{
								$str2="";
								if($action=="search")
									{
									$str2="action=".$action."&query=".$search_query."&";
								}
								$str2=$str2."page=";
								$str.=make_link($i,$str2.$i,$cat,$subcat,$item);
							}
						}
						$str.=$add;
						return $str;
					}
				}

				/**
				 * Get most discussed content
				 *
				 * @param id Content ID
				 * @return array Most discussed items
				 */
				function content_mostdiscussed($id)
				{
					$str="<table><tr><td><center>".make_link(from_db("item",$id,"name"),"","","",$id)."</td>
</tr><tr><td><center>".make_link(make_contentimg("item",$id,from_db("item",$id,"image"),0),"","","",$id)."</td></tr>
<tr><td><center>";
					$c=get_comments($id);
					if($c)
						{
						$str.="(".$c.")";
					}
					return $str."</center></td></tr></table>";
				}

				/**
				 * Build pagination limits
				 *
				 * @param who Content type
				 * @param who2 Secondary type
				 * @param id Content ID
				 * @return string Pagination limit
				 */
				function make_limits($who,$who2,$id)
				{
					$av="available = 1 AND ";
					if($who=="item")
						{
						$av=$av."visible = 1 AND ";
					}
					if(from_db("user",$_SESSION['userid'],"typ")>2)
						{
						unset($av);
					}
					return make_limits_search($who,$av.$who2." = '$id'");
				}

				/**
				 * Replace download references in content
				 *
				 * @param str Content string
				 * @param id Content ID
				 * @param file File reference
				 * @param link Link reference
				 * @return string Content with downloads replaced
				 */
				function replace_download($str,$id,$file,$link)
				{
					
					$search=array("#id","#file","#button");
					$replace=array($id,$file,"<div class=\"download_button\"><a href=\"".$link."\">".language("DOWNLOAD_BUTTON")."</a></div>");
					//<form action=\"".$link."\" method=\"post\"><input type=\"submit\" value=\"Download!\">
					return str_replace($search,$replace,$str);
				}

				/**
				 * Replace dynamic content markers
				 *
				 * @param content Content string
				 * @param dyn Dynamic flag
				 * @param return Return instead of echo
				 * @return string|void Processed content or echo
				 */
				function replace_dynamic($content,$dyn=0,$return=0)
				{
					global $item_allowed_edit;
					global $pms_db_connection;
					if(!$dyn)
						{
						unset($dyn);
						$link=$pms_db_connection->query(make_sql("dynamic","","id"));
						$ok=0;
						for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
							{
							$dyn[$i]=$a;
							$ok=1;
						}
					}
					if($dyn && count($dyn))
						{
						foreach($dyn as $a)
							{
							$search=$a->searcher;
							$replace=$a->replacer;
							if($a->makebr)
								{
								$search=def($search);
								$replace=dynamic_string(def($replace),$item_allowed_edit,$a->id);
							}
							$content=str_replace($search,$replace,$content);
						}
					}
					if($return) return array($content,$dyn);
					return $content;
				}

				/**
				 * Validate and format content
				 *
				 * @param content Content to validate
				 * @return string Validated content
				 */
				function do_check($content)
				{
					$do[0]="item";
					$do[1]="subcat";
					$do[2]="user";
					$do[3]="item_link";
					$do[4]="subcat_link";
					$do[5]="cat_link";
					$do[6]="user_link";
					for($i=0;$i<count($do);$i++)
						{
						$str=explode("#".$do[$i].":",$content);
						if(count($str)>1)
							{
							for($j=1;$j<count($str);$j++)
								{
								$space="<";
								$a=explode("<",$str[$j]);
								$b=explode(" ",$str[$j]);
								if(strlen($a[0])>strlen($b[0]))
									{
									$space=" ";
									$a=$b;
								}
								$b=explode(".",$str[$j]);
								if(strlen($a[0])>strlen($b[0]))
									{
									$space=".";
									$a=$b;
								}
								$b=explode(",",$str[$j]);
								if(strlen($a[0])>strlen($b[0]))
									{
									$space=",";
									$a=$b;
								}
								if($i!=2 && $i<3)
									{
									$a[0]=make_contentsmall($do[$i],$a[0]);
								}
								elseif($i<3)
									{
									$a[0]=user_out("",$a[0],1);
								}
								elseif($i==3)
									{
									$a[0]=make_link(from_db("item",$a[0],"name"),"","","",$a[0]);
								}
								elseif($i==4)
									{
									$a[0]=make_link(from_db("subcat",$a[0],"name"),"","",$a[0],"");
								}
								elseif($i==5)
									{
									$a[0]=make_link(from_db("cat",$a[0],"name"),"",$a[0],"","");
								}
								else
									{
									$a[0]=make_link(from_db("user",$a[0],"name"),"action=user&id=".$a[0],"","","");
								}
								$str[$j]=implode($space,$a);
							}
							$content=implode("",$str);
						}
					}
					return $content;
				}

?>
