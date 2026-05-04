<?php
// Module: functions_user.php

	/**
	 * Get translated language string from global language array
	 *
	 * @param str Language key to translate
	 * @return string Translated language string or original key if not found
	 */
	function language($str)
	{
		global $language;
		if($language[$str]) return $language[$str];
		return $str;
	}

	/**
	 * Validate username for invalid special characters
	 *
	 * @param n Username to validate
	 * @return int 1 if valid, 0 if contains invalid characters
	 */
	function name_condition($n)
	{
		$invalid=array('[',']','(',')',"'",'"','{','}');
		foreach($invalid as $i)
			{
			if(strpos($n,$i)!==FALSE) return 0;
		}
		return 1;
	}

	/**
	 * Create or update a user account with validation
	 *
	 * @param id User ID for update, empty for new user
	 * @param name Username
	 * @param password Password (plain text)
	 * @param passwordr Password confirmation
	 * @param mail Email address
	 * @param website User website URL
	 * @param typ User type/role
	 * @param image Profile image
	 * @param image2 Secondary image
	 * @param image_delete Flag to delete existing image
	 * @param bday Birth date
	 * @param top Top status
	 * @param active Active status
	 * @param sendmail Send email flag
	 * @param signatur User signature
	 * @return string Error message on failure, or success indicator
	 */
	function make_user($id,$name,$password,$passwordr,$mail,$website,$typ,$image,$image2,$image_delete,$bday,$top,$active,$sendmail,$signatur = "",$showmail = 1)
	{
		global $pms_db_prefix;
		global $config_values;
		global $pms_db_connection;
		$register=time();
		$registerip=preg_replace('/^(\d+\.\d+)\..*$/', '$1', $_SERVER["REMOTE_ADDR"]);
		$ok=1;
		if(!$id || $password)
			{
			if($password!=$passwordr)
				{
				return language("USER_ERROR_PW_COMPARE");
			}
		}
		if(strlen($name)<3)
			{
			return language("USER_ERROR_SHORT_USERNAME");
		}
		if(!name_condition($name))
			{
			return language("USER_ERROR_CHAR_USERNAME");
		}
		if($ok)
			{
			$link=$pms_db_connection->query(make_sql("user","","id"));
			if($link)
				{
				while($a=$pms_db_connection->fetchObject($link))
					{
					if(strtolower($name)==strtolower($a->name) && (!$id || $id!=$a->id))
						{
						return language("USER_ERROR_USER_EXISTS");
					}
				}
			}
		}
		if($bday)
			{
			$bday=explode(".",$bday);
			if($bday[0] && $bday[1] && $bday[2])
				{
				$bday=@mktime(0,0,0,$bday[1],$bday[0],$bday[2]);
			}
			if($bday>time()-60*60*24*365 || $bday<time()-60*60*24*365*200)
				{
				return language("USER_ERROR_INVALID_BIRTH");
			}
		}
		if(!$id || $password)
			{
			if(strlen($password)<3 && $ok)
				{
				return language("USER_ERROR_SHORT_PW");
			}
		}
		if(!check_mail($mail) && $ok)
			{
			return language("USER_ERROR_INVALID_MAIL");
		}
		if($ok==1)
			{
			$img="";
			$name=$pms_db_connection->escape($name);
			$mail=$pms_db_connection->escape($mail);
			$website=$pms_db_connection->escape($website);
			$signatur=$pms_db_connection->escape($signatur);
			$dbay=$pms_db_connection->escape($bday);
			
			if($id && $image_delete)
				{
				del_contentimg("user",$id,from_db("user",$id,"image"));
				$img="image = 0,";
			}
			if($id && $password)
				{
				$password=", password = '".md5($password)."'";
			}
			$do="INSERT INTO ".$pms_db_prefix."user (name,password,mail,showmail,website,signatur,typ,register,registerip,bday,top,active) VALUES ('$name','".md5($password)."','$mail','$showmail','$website','$signatur','$typ','$register','$registerip','$bday','$top','$active');";
			if($id)
				{
				if($typ<1 && from_db("user",$id,"typ")>=1) // reset mails
				{
					/** @psalm-suppress InvalidGlobal */
					global $confirmation_dialogs;
					foreach($confirmation_dialogs as $f)
						$add.=", ".$f[1]." = '0'";
					
				}
				$do="UPDATE ".$pms_db_prefix."user SET name = '$name'".$password.", mail = '$mail', showmail = '$showmail',website = '$website', signatur = '$signatur', typ = '$typ',".$img."bday = '$bday', top = '$top', active = '$active'".$add." WHERE id = '$id' LIMIT 1;";
			}
			if($pms_db_connection->query($do))
				{
				if($image)
					{
					$end=substr($image,-3);
					/** @psalm-suppress InvalidGlobal */
					global $supported_img;
					if(in_array(strtolower($end),$supported_img))
						{
						if(!$id)
							{
							$link=$pms_db_connection->query(make_sql("user","","id"));
							while($link && $a=$pms_db_connection->fetchObject($link))
								{
								$id=$a->id;
							}
						}
						/** @psalm-suppress InvalidGlobal */
						global $image_path;
						/** @psalm-suppress InvalidGlobal */
						global $pms_db_prefix;
						$target=$image_path."user/".$id.".".$end;
						@copy($image2,$target);
						create_img($target,64,64);
						$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET image = '$end' WHERE id = '$id' LIMIT 1;");
					}
				}
				if($sendmail)
					{
					if(!$id)
						{
						$link=$pms_db_connection->query(make_sql("user","","id"));
						while($link && $d=$pms_db_connection->fetchObject($link))
							{
							$id=$d->id;
						}
					}
					$key=from_db("user",$id,"register");
					$c=$config_values->page;
					$subject=str_replace("%1",$config_values->name,language("REGISTER_MAIL_SUBJECT"));
					$body=str_replace(array("%1","%2","%3"),array($name,$c,$c."/index.php?action=register_finish&id=".$id."&key=".$key),language("REGISTER_MAIL_BODY"));
					
					my_mail($mail,$subject,$body);
					$link=$pms_db_connection->query(make_sql("user","typ >= '1' AND mail_register = '1'"));
					while($link && $a=$pms_db_connection->fetchObject($link))
						my_mail($a->mail,language("REGISTER_ADMIN_MAIL_SUBJECT"),str_replace(array("%1","%2","%3"),array($a->name,$config_values->name,$name),language("REGISTER_ADMIN_MAIL_BODY")));
					
				}
				$a[0]=language("USER_SAVE_SUCCESS");
				$a[1]=1;
				return $a;
			}
			else
				{
				$a[0]=language("USER_SAVE_ERROR");
				$a[1]=0;
				return $a;
			}
		}
	}

	/**
	 * Format ban duration for display
	 *
	 * @param time Unix timestamp of ban
	 * @return float|string Formatted ban time information
	 */
	function ban_time($time)
	{
		if($time>0)
			{
			return ceil(($time-time())/60/60/24);
		}
		else
			{
			return language("BAN_UNLIMITED");
		}
	}

	/**
	 * Authenticate user and create session
	 *
	 * @param name Username
	 * @param password Password (plain text)
	 * @param min_rights Minimum required user level
	 * @param do_md5 Whether password is MD5 encoded
	 * @return int|array User data on success, status code on failure
	 */
	function do_login($name,$password,$min_rights,$do_md5=1)
	{
		global $pms_db_prefix;
		global $pms_db_connection;
		$link=$pms_db_connection->query(make_sql("user","name LIKE '".$pms_db_connection->escape($name)."'","id"));
		if($do_md5)$password=md5($password);
		if($link && $a=$pms_db_connection->fetchObject($link))
			{
			if($password!=$a->password)
				{
				return 2;
			}
			if($a->active==0)
				{
				return 3;
			}
			if($min_rights>0 && $min_rights>$a->typ)
				{
				return 4;
			}
			$login2=time();
			$id=$a->id;
			$_SESSION['last_login']=from_db("user",$id,"login");
			
			$_SESSION['pmsglobal']=1;
			$_SESSION['userid']=$a->id;
			$_SESSION['usertyp']=$a->typ;
			/** @psalm-suppress InvalidGlobal */
			global $website_key;
			/** @psalm-suppress InvalidGlobal */
			global $login;
			$_SESSION['loginip']=$_SERVER["REMOTE_ADDR"];
			$_SESSION['website_key']=$website_key;
			$login=1;
			
			$pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET login = '$login2' WHERE id = '$id' LIMIT 1;");
			$str[0]=$a->name;
			$str[1]=$a->password;
			$str[2]=$a->typ;
			$str[3]=$a->id;
			return $str;
		}
		return 1;
	}

	/**
	 * Validate email address format
	 *
	 * @param mail Email address to validate
	 * @return int 1 if valid email, 0 otherwise
	 */
	function check_mail($mail)
	{
		if(strlen($mail)<8  || !stristr($mail,"@") || !stristr($mail,"."))
			{
			return 0;
		}
		return 1;
	}

	/**
	 * Format and display user information
	 *
	 * @param rang User rank/level
	 * @param id User ID
	 * @param all Include all details flag
	 * @param linkit Make name clickable link
	 * @param sig Include signature
	 * @param head Include header
	 * @param points User points/reputation
	 * @return string Formatted user HTML output
	 */
	function user_out($rang,$id,$all,$linkit=0,$sig=0,$head=0,$points="")
	{
		global $config_values;
		global $pms_db_connection;
		global $pms_db_prefix;
		if($config_values->topusers && $points=="")
			{
			$points_add=1;
			$link2=$pms_db_connection->query("SELECT COUNT(id)*60 FROM ".$pms_db_prefix."comments WHERE user = '$id';");
			if($link2 && $b=$pms_db_connection->fetch($link2))
				{
				$points=$b[0];
			}
		}
		$link=$pms_db_connection->query(make_sql("user","id = '$id'","id"));
		if($link)
			{
			$a=$pms_db_connection->fetchObject($link);
			if(!$a->id)
				{
				return "";
			}
			if($rang)
				{
				$rang=str_replace("%1",$rang,language("TOP_USER_RANK"))." ";
			}
			if(strtolower(substr($a->website,0,7))=="http://")
				{
					$a->website=substr($a->website,7);
				}
				$web="<a href=\"http://".$a->website."\" target=\"_blank\">"./*$a->website*/language("USER_VIEW_VISIT_WEBSITE")."</a>";
				if($all)
					{
					$web="<a href=\"http://".$a->website."\" target=\"_blank\">".$a->website."</a>";
				}
				if($points_add) $points+=$a->points;
				$str="<table";
				if($all)
					{
					$str.=" width=\"100%\"";
				}
				$str.="><tr><td rowspan=\"3\"";
				if($linkit)
					{
					$a->name=make_link($a->name,"action=user&id=".$id,"","","");
				}
				if(!$all)
					{
					$str.=" style=\"text-align:center;\"><b>".$rang.$a->name."</b><br>".str_replace("%1",$points,language("TOP_USER_POINTS"))."<br>";
				}
				else
					{
					$str.=" width=\"78px\">";
				}
				$str.="
".make_contentimg("user",$a->id,$a->image,0)."<br>";
				if(!$all && $a->website)
					{
					$str.=$web;
				}
				$str.="</td>";
				if($all)
					{
					$h1="<b>";
					$h2="</b>";
					if($head)
						{
						$h1="<div class=\"user_heading\">";
						$h2="</div>";
					}
					$str.="<td>".$h1.$rang.$a->name.$h2."</td></tr><tr valign=\"top\"><td class=\"description\">
<table width=\"100%\">";
					if($config_values->topusers)
						$str.="<tr><td width=\"120px\">".language("USER_VIEW_POINTS")."</td><td>".$points."</td></tr>";
					
					$str.="<tr><td>".language("USER_VIEW_REGISTERED")."</td><td>".make_date($a->register,0)."</td></tr>
<tr><td>".language("USER_VIEW_LAST_LOGIN")."</td><td>".make_date($a->login,0)."</td></tr>";
					if($a->bday)
						{
						$str.="<tr><td>".language("USER_VIEW_BIRTH")."</td><td>".date(language("DATE_FORMAT"),$a->bday)." (".make_age($a->bday).")</td></tr>";
					}
					/** @psalm-suppress InvalidGlobal */
					global $user_typ;
					$str.="
<tr><td>".language("USER_VIEW_TYP")."</td><td>".$user_typ[$a->typ]."</td></tr>";
					if($a->showmail)
						{
						$str.="
<tr><td>".language("USER_VIEW_MAIL")."</td><td>".make_mail($a->mail)."</a></td></tr>";
					}
					if($a->website)
						{
						$str.="<tr><td>".language("USER_VIEW_WEBSITE")."</td><td>".$web."</td></tr>";
					}
					if($sig && $a->signatur)
						{
						$str.="<tr><td>".language("USER_VIEW_SIGNATUR")."</td><td>".def($a->signatur)."</td></tr>";
					}
					$str.="</tr></table>
</td>";
				}
				$str.="</tr></table>
";
			}
			return $str;
		}

				/**
				 * Update user points/reputation
				 *
				 * @param id User ID
				 * @param points Points to add/subtract
				 * @return void
				 */
				function user_points($id,$points)
				{
					global $pms_db_prefix;
					global $pms_db_connection;
					if($id)
						{
						return $pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET points=points+$points WHERE id = '$id' LIMIT 1");
					}
				}

?>
