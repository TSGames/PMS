<?
define("PMS_FRONTEND",1);
require('functions.php');
$template="/var/template/template.html";

// compress output using gzip

if($config_values->allow_compress && extension_loaded("zlib") && strstr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip"))
{
    @ob_start("ob_gzhandler");
}
// end of compressing


if($_GET["follow"]=="404"){
    $_GET["item"]=get_errorpage();
}
else if($config_values->speciallinks)
{
    $_GET2=$_GET;
    if($_GET["download"])
    {
        $_GET["action"]="download";
        $temp=explode("-",$_GET["download"]);
        $_GET["id"]=$temp[1];
    }
    $query=$_GET["follow"];
    $query=explode("-",$query);
    $what=substr($query[1],-1);
    if($what=="c") $_GET["cat"]=substr($query[1],0,-1);
    else if($what=="s") $_GET["subcat"]=substr($query[1],0,-1);
    else if($what=="u")
    {
        $_GET["action"]="user";
        $_GET["id"]=substr($query[1],0,-1);
    }
    else 
    {
        if(from_db("item",$query[1],"id"))
        $_GET["item"]=$query[1];
        else
        unset($query[1]);
    }
    if(count($query)<2 && $query[0])
    {
        $search_query=$query[0];
        $action="search";
    }
}
if(is_array($_GET2))
{
    foreach($_GET2 as $key => $value)
    {
        if($value)
        $_GET[$key]=$value;
    }
}
$cat=(int)$_GET["cat"];
$subcat=(int)$_GET["subcat"];
$item=(int)$_GET["item"];
if(!$action) $action=$_GET['action'];
$id=$_GET['id']*1;
$comments=$_GET['comments'];
$comment_get=$_GET['comment'];
$content_page=$_GET['page'];
$login=0;

$link=$pms_db_connection->query(make_sql("bans","","id"));
while($link && $a=$pms_db_connection->fetchObject($link))
{
    if(stristr($_SERVER['REMOTE_ADDR'],$a->ip))
    {
        unset($_POST);
        unset($_GET);
        unset($action);
        $item=-1;
        $link=$pms_db_connection->query(make_sql("item","special = '5'","id LIMIT 1"));
        if($link && $b=$pms_db_connection->fetchObject($link))
        {
            $item=$b->id;
        }
        $ip_is_banned=1;
        $banned_ip=$_SERVER['REMOTE_ADDR'];
        $banned_reason=def($a->reason);
        if(!$banned_reason || ctype_space($banned_reason))
        {
            $banned_reason=language("BAN_NO_REASON");
        }
        $banned_time=ban_time($a->time);
        if($banned_time==1)
        {
            $banned_time=$banned_time." Tag";
        }
        elseif($banned_time!=language("BAN_UNLIMITED"))
        {
            $banned_time=$banned_time." Tage";
        }
        break;
    }
}
if(($_POST['user_login']==language("USER_LOGIN") || (!$_SESSION['pmsglobal'] && $_COOKIE['login_id'] && $_COOKIE['login_pw'])) && !$login)
{
    if($_POST['user_login']==language("USER_LOGIN"))
    {
        
        load_hidden();
        if($action=="register")
        {
            $action="";
        }
    }
    $log_name=from_db("user",$_COOKIE['login_id']*1,"name");
    $log_pw=$_COOKIE['login_pw'];
    $do_md5=0;
    if($_POST['name'])
    {
        $do_md5=1;
        $log_name=$_POST['name'];
        $log_pw=$_POST['password'];
    }
    $log=do_login($log_name,$log_pw,0,$do_md5);
    if(is_array($log))
    {
        setcookie("login_id",$log[3],time()+60*60*24*1000,"/",$cookie_domain);
        if($_POST['save_login'])
        {
            setcookie("login_pw",md5($_POST['password']),time()+60*60*24*1000,"/",$cookie_domain);
        }
    }
    else
    {
        setcookie("login_id","",time()-3600,"/",$cookie_domain);
        setcookie("login_pw","",time()-3600,"/",$cookie_domain);
        if($log==1)
        {
            $login_fail=language("LOGIN_ERROR_INVALID_USER");
        }
        if($log==2)
        {
            $login_fail=language("LOGIN_ERROR_INVALID_PW");
        }
        if($log==3)
        {
            $login_fail=$lanuage["LOGIN_ERROR_USER_LOCKED"];
        }
    }
}

// modules
@include("modules/newsletter.php");


if($content_page<1)
{
    $content_page=1;
}
$page_limit=$config_values->page_limit;
if($page_limit<1)
{
    $page_limit=10;
}
$start_page=($content_page-1)*$page_limit;

if($action && !ctype_space($action))
{
    $action=strtolower($action);
    if(!@in_array($action,$valid_actions)) 
    {
        $item=get_errorpage();
        unset($action);
    }
}

if(@array_key_exists("recover_pass",$_POST))
{
    $name=$pms_db_connection->escape($_POST['name']);
    $action="password_recover";
    $link=$pms_db_connection->query(make_sql("user","name = '$name'","id"));
    $password_recover=language("PASSWORD_RECOVER_ERROR_INVALID_USER");
    if($link && $a=$pms_db_connection->fetchObject($link))
    {
        $id=$a->id;
        $name=from_db("user",$id,"name");
        $password_recover=language("PASSWORD_RECOVER_MAIL_SEND");
        $key=md5($name);
        my_mail(from_db("user",$id,"mail"),str_replace("%1",$config_values->name,language("PASSWORD_RECOVER_REQUEST_MAIL_SUBJECT")),
        str_replace(array("%1","%2"),array(from_db("user",$id,"name"),$config_values->page."/index.php?action=password_recover&user=".$id."&id=".$key),language("PASSWORD_RECOVER_REQUEST_MAIL_BODY")));
    }
}

if($_POST['user']==language("REGISTER_BUTTON"))
{
    $action="register";
    $name=$_POST['name'];
    $password=$_POST['password'];
    $passwordr=$_POST['passwordr'];
    $mail=$_POST['mail'];
    if(comp_spam($_POST['spam'],$_POST['session'],$_POST['spamcount']))
    {
        $a=make_user("",$name,$password,$passwordr,$mail,"",0,0,0,0,0,1,0,1);
        if(is_array($a))
        {
            unset($name);
            unset($password);
            unset($passwordr);
            unset($mail);
            if($a[1])
            {
                $register_fail=language("REGISTER_SUCCESS");
            }
            else
            {
                $register_fail=language("REGISTER_ERROR");
            }
        }
        else
        {
            $register_fail=$a;
        }
    }
    else
    {
        $register_fail=language("REGISTER_ERROR_SPAM");
    }
}
if($_SESSION['pmsglobal']==1)
{
    $login=1;
    $user_id=$_SESSION['userid'];
}
if($action=="logout")
{
    delete_sessions();
    unset($action);
    $login_fail=language("USER_LOGOUT_SUCCESS");
    $login=0;
}

$item_allowed_edit=$login==1 && from_db("user",$user_id,"typ")>1;
$item_edit_mode=$item_allowed_edit && $_GET["edit"];
if($_POST['item_edit'] && $_POST['item_id'] && $item_allowed_edit)
{
    $item=from_db("item",$_POST['item_id']*1,"id");
    if($item)
    {
        $name=$pms_db_connection->escape($_POST["edit_name"]);
        $description=$pms_db_connection->escape($_POST["edit_description"]);
        $content=$pms_db_connection->escape($_POST["edit_content"]);
        $pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET name = '$name', description = '$description', content = '$content', time_changed = '".time()."' WHERE id = '".$item."'");
        unset($name);unset($description);unset($content);
    }
}

// Subcat Jumping Script
$check=0;
if($item) $check=from_db("item",$item,"subcat");
if(!$check) $check=$subcat;
check_subcatjump($check);

if($_POST['rate']==language("RATE_BUTTON"))
{
    $item=$_POST['id'];
    $cname='rate_'.$item;
    if(!$_SESSION[$cname] && !$_COOKIE[$cname])
    {
        setcookie($cname,1,time()+60*60*24*1000,"/",$cookie_domain);
        $_SESSION[$cname]=1;
        if($_POST['rating']>=1 && $_POST['rating']<=5)
        {
            $rating=from_db("item",$item,"rating",0)+$_POST['rating'];
            $numratings=from_db("item",$item,"numratings",0)+1;
            user_points($user_id,10);
            $pms_db_connection->query("UPDATE ".$pms_db_prefix."item SET rating = '$rating', numratings = '$numratings' WHERE id = '$item' LIMIT 1;");
        }
    }
}
if($_POST['user_config']==language("USER_PANEL_SAVE") && $login)
{
    $action="user_panel";
    $password=$_POST['password'];
    $passwordr=$_POST['passwordr'];
    $mail=$_POST['mail'];
    $bday=$_POST['bday'];
    $top=$_POST['top'];
    $image_delete=$_POST['image_delete'];
    $a=make_user($user_id,from_db("user",$user_id,"name"),$password,$passwordr,$mail,$_POST['website'],from_db("user",$user_id,"typ"),$_FILES["image"]["name"],$_FILES["image"]["tmp_name"],$image_delete,$bday,$top,from_db("user",$user_id,"active"),0,$_POST['signatur'],$_POST['showmail']);
    if(is_array($a))
    {
        $register_fail=$a[0];
    }
    else
    {
        $register_fail=$a;
    }
}
if($_GET['search']==language("SEARCH_BUTTON") || $_GET['search_query'])
$action="search";


if($_POST['poll']==language("POLL_VOTE") || $_POST['poll']==language("POLL_RESULTS"))
{
    load_hidden();
    $poll_id=$_POST['poll_id'];
    $cname='poll'.$poll_id;
    if(!$_SESSION[$cname] && !$_COOKIE[$cname] && $_POST['poll']==language("POLL_VOTE"))
    {
        setcookie($cname,1,time()+60*60*24*1000,"/",$cookie_domain);
        $_SESSION[$cname]=1;
        $_POST["answer"]*=1;
        user_points($user_id,15);
        $do="UPDATE ".$pms_db_prefix."poll SET answers".$_POST["answer"]." = answers".$_POST["answer"]." + 1 WHERE id = '$poll_id' LIMIT 1;";
        $pms_db_connection->query($do);
    }
    $current_poll=$poll_id;
}
if(@array_key_exists("edit_comment",$_POST) && $login && (from_db("user",$_SESSION['userid'],"typ")>0 || from_db("comments",$_POST['id'],"user")==$user_id))
{
    $item=$_POST['item']*1;
    if($item==$gb_item)
    {
        $action="guestbook";
    }
    $commentid=$pms_db_connection->escape($_POST['id']);
    $com_title=$pms_db_connection->escape($_POST['title']);
    $com_comment=$pms_db_connection->escape($_POST['comment']);
    if($pms_db_connection->query("UPDATE ".$pms_db_prefix."comments SET title = '$com_title', comment = '$com_comment' WHERE id = '$commentid' LIMIT 1;"))
    {
        $last_comment=language("COMMENT_SUCCESS");
        if($action=="guestbook")
        $last_comment=language("GUESTBOOK_SUCCESS");
    }
    else
    {
        $last_comment=language("COMMENT_ERROR");
        if($action=="guestbook")
        $last_comment=language("GUESTBOOK_ERROR");
        
    }
    unset($com_title);
    unset($com_comment);
}
if($action=="user" && $id)
{
    $id=from_db("user",$id,"id");
    if($id)
    {
        $name=from_db("user",$id,"name");
        $current_pos_name=str_replace("%1",$name,language("USER_VIEW_TITLE"));
        $content=user_out("",$id,1,0,1,1);
    }
    else
    {
        $item=get_errorpage();
    }
}
elseif($action=="user")
{
    $item=get_errorpage();
}
if($_POST['post_comment']==language("COMMENT_SEND") || $_POST['post_comment']==language("GUESTBOOK_SEND"))
{
    $item=$pms_db_connection->escape($_POST['item']);
    if(from_db("item",$item,"special")==4) // guestbook
    $action="guestbook";
    
    if($login) $com_user=$user_id;
    $ip=$_SERVER["REMOTE_ADDR"];
    $date=time();
    if(!$user)
    {
        $com_name=$pms_db_connection->escape($_POST['name']);
        $com_mail=$pms_db_connection->escape($_POST['mail']);
    }
    $com_title=$pms_db_connection->escape($_POST['title']);
    $com_comment=$pms_db_connection->escape($_POST['comment']);
    $ok=1;
    if(!comp_spam($_POST['spam'],$_POST['session'],$_POST['spamcount']) && !$com_user)
    {
        $last_comment=language("COMMENT_ERROR_SPAM");
        $ok=0;
    }
    if(!$com_name && !$com_user)
    {
        $last_comment=language("COMMENT_ERROR_NAME");
        $ok=0;
    }
    if((!check_mail($com_mail) && $com_mail) && !$com_user)
    {
        $last_comment=language("COMMENT_ERROR_MAIL_INVALID");
        $ok=0;
    }
    if(!trim($com_comment))
    {
        $last_comment=language("COMMENT_ERROR_EMPTY");
        if($action=="guestbook")
        {
            $last_comment=language("GUESTBOOK_ERROR_EMPTY");
        }
        $ok=0;
    }
    
    if($action=="guestbook" && !$guestbook_activated)
    {
        $ok=0;
        $last_comment=language("GUESTBOOK_ERROR_NOT_ACTIVATED");
    }
    $link=$pms_db_connection->query(make_sql("comments","item = '$item' AND title = '$com_title' AND comment = '$com_comment' AND user = '$com_user'","id"));
    if($link && $ok)
    {
        $a=$pms_db_connection->fetchObject($link);
        if($a->id)
        {
            $last_comment=language("COMMENT_ERROR_MULTIPLE_POST");
            if($action=="guestbook")
            $last_comment=language("GUESTBOOK_ERROR_MULTIPLE_POST");
            
            $ok=0;
        }
    }
    if($ok)
    {
        if($pms_db_connection->query("INSERT INTO ".$pms_db_prefix."comments (item,title,comment,name,user,date,mail,ip) VALUES ('$item','$com_title','$com_comment','$com_name','$com_user','$date','$com_mail','$ip');"))
        {
            $last_comment=-1; 
            // Keine ausgabe ist besser!
            if($action=="guestbook")
            {
                $link=$pms_db_connection->query(make_sql("user","typ >= '1' AND mail_guestbook = '1'"));
                while($link && $a=$pms_db_connection->fetchObject($link))
                my_mail($a->mail,language("GUESTBOOK_MAIL_SUBJECT"),str_replace(array("%1","%2","%3"),array($a->name,$config_values->name,$config_values->page."/?action=guestbook#comments"),language("GUESTBOOK_MAIL_BODY")));
                
            }
            else
            {
                $link=$pms_db_connection->query(make_sql("user","typ >= '1' AND mail_comments = '1'"));
                while($link && $a=$pms_db_connection->fetchObject($link))
                my_mail($a->mail,language("COMMENT_MAIL_SUBJECT"),str_replace(array("%1","%2","%3"),array($a->name,$config_values->name,$config_values->page."/?item=".$item."#comments"),language("COMMENT_MAIL_BODY")));
                
            }
        }
        else
        {
            $last_comment=language("COMMENT_ERROR");
            if($action=="guestbook")
            $last_comment=language("GUESTBOOK_ERROR");
            
        }
    }
}
if(!$item && ($cat || $subcat))
{
    if($cat && !$subcat)
    {
        if(!from_db("cat",$cat,"id"))
        {
            $item=get_errorpage();
        }
    }
    if($subcat)
    {
        if(!from_db("subcat",$subcat,"id"))
        {
            $item=get_errorpage();
        }
    }
}
if($config_values->topusers)
{
    $max=$config_values->numtopuser;
    $a=count_db("user");
    if($a<$max)
    $max=$a;
    if($action=="topuser")
    {
        $current_pos_name=str_replace("%1",$max,language("TOP_USER_TITLE"));
        $content="<div class=\"item_heading\">".str_replace(array("%1","%2"),array($config_values->name,$max),language("TOP_USER_HEADING"))."</div><br>
        ";
    }
    $link=$pms_db_connection->query(make_sql("user","LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.user=".$pms_db_prefix."user.id) WHERE ".$pms_db_prefix."user.top='1'","points DESC",$pms_db_prefix."user.*,count(".$pms_db_prefix."comments.id)*60+".$pms_db_prefix."user.points as points",$pms_db_prefix."user.id",0));
    unset($users);
    for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
    {
        $users[$i][0]=$a->points;
        $users[$i][1]=$a->id;
    }
    $top_rand=rand(0,$max-1);
    for($i=0;$i<$max && $i<count($users);$i++)
    {
        if($action=="topuser")
        {
            $content.=user_out($i+1,$users[$i][1],1,1,0,0,$users[$i][0]);
        }
        if($top_rand==$i)
        {
            $top_user="<table class=\"top_users\"><tr><td><div align=\"center\">".user_out($i+1,$users[$i][1],0,1,0,0,$users[$i][0])."[".make_link(language("TOP_USER_MORE"),"action=topuser","","","")."]</div></td></tr></table>";
        }
    }
}
elseif($action=="topuser")
{
    $item=get_errorpage();
}
if($item)
{
    if((!from_db("item",$item,"available") && from_db("user",$_SESSION['userid'],"typ")<2) || !from_db("item",$item,"id"))
    {
        $item=get_errorpage();
    }
    $cat=from_db("item",$item,"cat");
    $subcat=from_db("item",$item,"subcat");
    if((($cat && !from_db("cat",$cat,"available")) || ($subcat && !from_db("subcat",$subcat,"available"))) && from_db("user",$_SESSION['userid'],"typ")<2)
    {
        $item=get_errorpage();
    }
}
$link=$pms_db_connection->query(make_sql("item","special = '2'","id"));
if($link)
{
    $a=$pms_db_connection->fetchObject($link);
    $dlpage_item=$a->id;
}
if($action=="download" || ($item==$dlpage_item && $dplage_item))
{
    $action="download";
    $item=$dlpage_item;
    $file=from_db("item",$id,"name");
    $file_link=from_db("item",$id,"link");
    $subcat=from_db("item",$id,"subcat");
    $cat=from_db("item",$id,"cat");
    if(from_db("item",$id,"typ")!=2)
    {
        $site_not_found=1;
        unset($action);
    }
}

// check if item == guestbook

if($action=="guestbook" || ($gb_item==$item && $item))
{
    $action="guestbook";
    $link=$pms_db_connection->query(make_sql("item","special = '4'","id"));
    if($link)
    {
        $a=$pms_db_connection->fetchObject($link);
        $item=$a->id;
    }
    else
    {
        $site_not_found=1;
    }
}
elseif($action=="guestbook")
{
    $site_not_found=1;
    unset($action);
}

if($action=="user_panel" && $login)
{
    $current_pos_name=language("USER_PANEL_TITLE");
    $content="<table class=\"content_table\"><tr><td><div class=\"item_heading user_panel\">".str_replace("%1",from_db("user",$user_id,"name"),language("USER_PANEL_HEADING"))."</div><br>
    ".form();
    if($register_fail)
    {
        $content.="<div class=\"register_fail\">".$register_fail."<br></div>";
    }
    $image=from_db("user",$user_id,"image");
    $mail=from_db("user",$user_id,"mail");
    $website=from_db("user",$user_id,"website");
    $signatur=stripslashes(from_db("user",$user_id,"signatur"));
    $top=make_check(from_db("user",$user_id,"top"));
    $showmail=make_check(from_db("user",$user_id,"showmail"));
    $bday=from_db("user",$user_id,"bday");
    if($bday)
    $bday=date("d.m.Y",$bday);
    else
    unset($bday);
    
    $link=$pms_db_connection->query("SELECT COUNT(id) FROM ".$pms_db_prefix."comments WHERE user = '$user_id';");
    if($link && $a=mysqli_fetch_array($link))
    {
        $points=$a[0]*60;
    }
    $points+=from_db("user",$user_id,"points");
    $content.="<table class=\"user_edit\">
    <tr><td>".language("USER_PANEL_PW")."</td><td><input type=\"password\" name=\"password\" maxlength=\"32\"></td></tr>
    <tr><td>".language("USER_PANEL_PW_REPEAT")."</td><td><input type=\"password\" name=\"passwordr\" maxlength=\"32\"></td></tr>
    <tr><td>".language("USER_PANEL_MAIL")."</td><td><input type=\"text\" name=\"mail\" maxlength=\"100\" value=\"".$mail."\"></td></tr>
    <tr><td>".language("USER_PANEL_WEBSITE")."</td><td><input type=\"text\" name=\"website\" maxlength=\"128\" value=\"".$website."\"></td></tr>
    <tr><td>".language("USER_PANEL_SIG")."</td><td><textarea cols=\"30\" rows=\"3\" name=\"signatur\" maxlength=\"150\">".$signatur."</textarea></td></tr>
    <tr><td>".language("USER_PANEL_STATUS")."</td><td>".$user_typ[from_db("user",$user_id,"typ")]."</td></tr>";
    if($config_values->topusers)
    $content.="<tr><td>".language("USER_PANEL_POINTS")."</td><td>".$points."</td></tr>
    <tr><td colspan=\"2\">".language("USER_PANEL_POINTS_DESCRIPTION")."</td></tr>";
    $content.="<tr><td>".language("USER_PANEL_BIRTH")."</td><td><input type=\"text\" name=\"bday\" value=\"".$bday."\">
    <tr><td>".language("USER_PANEL_AVATAR")."</td><td><input type=\"file\" name=\"image\">";
    if($image)
    {
        $content.="</td></tr><tr><td>".make_contentimg("user",$user_id,$image,0)."</td><td><input type=\"checkbox\" name=\"image_delete\" value=\"1\"> ".language("USER_PANEL_AVATAR_DELETE");
    }
    $top="<tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"top\" value=\"1\" ".$top."> ".language("USER_PANEL_SHOW_TOP")."</center></td></tr>";
    if(!$config_values->topusers)
    {
        $top="<input type=\"hidden\" name=\"top\" value=\"".from_db("user",$user_id,"top")."\">";
    }
    $content.="
    <tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"showmail\" value=\"1\" ".$showmail."> ".language("USER_PANEL_SHOW_MAIL")."</center></td></tr>".$top."
    <br><br><td></tr>
    <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"user_config\" value=\"".language("USER_PANEL_SAVE")."\"></center></td></tr>
    </table></center>
    </td></tr></table>
    </form>";
}
elseif($action=="user_panel")
{
    $site_not_found=1;
}
if($comment_get=="delete" && $id && $login)
{
    if(from_db("user",$user_id,"typ")>=1)
    {
        $pms_db_connection->query("DELETE FROM ".$pms_db_prefix."comments WHERE id = '$id' LIMIT 1;");
    }
}
if($subcat)
{
    $cat=from_db("subcat",$subcat,"cat");
}
if(/*$_SERVER['QUERY_STRING']=="" && */!$action && !$cat && !$subcat && !$item && !$modul_frontpage) // Start page
    {
        $frontpage=1;
        unset($add);
        $add=" AND available = 1";
        if($login && from_db("user",$_SESSION["userid"],"typ")>1) unset($add);
        $link=$pms_db_connection->query(make_sql("item","special = '1'".$add,"id"));
        if($link && $a=$pms_db_connection->fetchObject($link))
        $item=$a->id;
        else 
        {
            $frontpage=0;
            $site_not_found=1;
        }
    }
    $user_typ2=0;
    if($login)
    {
        $user_typ2=from_db("user",$user_id,"typ")+1;
    }
    // menu old position, now moved!
    if($action=="search")
    {
        if($_GET['query'])
        $search_query=$_GET['query'];
        if($_GET['search_query'])
        $search_query=$_GET['search_query'];
        
        $result=explode(" ",$search_query);
        $search_query2=stripslashes($search_query);
        if(substr($search_query2,0,1)=='"' && substr($search_query2,-1)=='"')
        {
            unset($result);
            $result[0]=substr($search_query,2,-2);
            $search_query2=stripslashes($result[0]);
            $search_query3=stripslashes($search_query);
        }
        if(!$search_query3)$search_query3=$search_query2;
        if(strlen($search_query2)>=3)
        {
            $mysql="(";
            $mysqli_array[0]="name";
            $mysqli_array[1]="description";
            $mysqli_array[2]="content";
            for($j=0;$j<count($mysqli_array);$j++)
            {
                if($j!=0)
                {
                    $mysql.="OR ";
                }
                
                for($i=0;$i<count($result);$i++)
                {
                    if($i!=0)
                    {
                        $mysql.="AND ";
                    }
                    
                    $mysql.="(".$mysqli_array[$j]." LIKE '%".$pms_db_connection->escape($result[$i])."%'";
                    unset($check);$check=htmlentities($result[$i]);
                    if($result[$i]!=$check) $mysql.=" OR ".$mysqli_array[$j]." LIKE '%".$pms_db_connection->escape($check)."%'";
                    $mysql.=") ";
                }
            }
            $av="available = 1 AND visible = 1 AND ";
            if(from_db("user",$_SESSION['userid'],"typ")>2)
            unset($av);
            $mysql.=") AND ".$av."typ != '3'";
            $num_results=count_db_exp("item","WHERE ".$mysql);
        }
        $search_query2=clear_comment($search_query2);
        
        if($num_results!=1 || strlen($search_query2)<3)
        {
            $current_pos_name=str_replace("%1",$search_query2,language("SEARCH_HEADING"));
            
            $con_limit=make_limits_search("item",$mysql);
            
            $add_limit="";
            if($con_limit)
            $add_limit="<br>";
            
            //$mysql=$mysql." LIMIT $page_limit,$start_page;";
            $list=$config_values->search_list;
            $use_list=$list && file_exists($template_lists_folder."/".$list);
            
            $content="<table class=\"content_table\"><tr><td><div class=\"item_heading\">".str_replace("%1",$search_query2,language("SEARCH_HEADING"))."</div>
            ";
            if(strlen($search_query2)<3)
            {
                unset($add_limit);
                unset($con_limit);
            }
            $content.=$con_limit.$add_limit."
            <table><tr><td>";
            if(strlen($search_query2)>=3)
            {
                if(!$search_order) $search_order="name";
                $link=$pms_db_connection->query(make_sql("item",$mysql,$search_order));
                unset($result);
                for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
                {
                    $result[$i][0]=$a->id;
                    $result[$i][1]=$a->cat;
                    $result[$i][2]=$a->subcat;
                    if($use_list)$objects[$i]=$a;
                }
                if($i>0)
                {
                    if(!$con_limit)
                    {
                        $content.="<br>";
                    }
                }
                else
                {
                    $search_error="SEARCH_NORESULTS";
                    $content.=language($search_error);
                }
            }
            else
            {
                $search_error="SEARCH_SHORTQUERY";
                $content.=language($search_error);
            }
            if($use_list)
            {
                $query=$search_query2;
                $num_results=$i;
                $rows=$config_values->list_rows;
                $search=1;
                ob_start();
                $content="<table class=\"content_table\"><tr><td>";
                include $template_lists_folder."/".$list;
                $content.=ob_get_clean()."</td></tr></table>";
            }
            else
            {
                for($i=$start_page;$i<$start_page+$page_limit;$i++)
                {
                    if(from_db("user",$_SESSION['userid'],"typ")<2)
                    {
                        if(!from_db("cat",$result[$i][1],"available") || !from_db("subcat",$result[$i][2],"available"))
                        {
                            continue;
                        }
                    }
                    $content.=make_contentsmall("item",$result[$i][0]);
                }
                $content.=$add_limit.$con_limit.'</td></tr></table>
                <table width="100%"><tr><th align="right" class="search_url"><br>';
                $content.=$config_values->page."/index.php?action=".$action."&query=".$search_query2."&page=".$content_page."</th></tr></table></td></tr></table>";
            }
        }
        else
        {
            $link=$pms_db_connection->query(make_sql("item",$mysql,"id"));
            if($link) $a=$pms_db_connection->fetchObject($link);
            $item=$a->id;
            $subcat=from_db("item",$item,"subcat");
            check_subcatjump($subcat);
            $cat=from_db("subcat",$subcat,"cat");
            unset($action);
        }
    }
    
    $content=smileys($content);
    // content
    
    @include "access.php";
    check_subcatjump($subcat);
    
    if(!$modul_frontpage && !$item && !$action=="search" && !$site_not_found)
    {
        if($cat && !$subcat)
        {
            if(!from_db("cat",$cat,"available") && from_db("user",$_SESSION['userid'],"typ")<2)
            {
                unset($cat);
                unset($subcat);
                $item=get_errorpage();
            }
            else
            {
                $con_limit=make_limits("subcat","cat",$cat);
                $current_pos_name=from_db("cat",$cat,"name");
                $key1="subcat";
                $key2="cat";
                $search=$cat;
            }
        }
        if($cat && $subcat)
        {
            if((!from_db("cat",from_db("subcat",$subcat,"cat"),"available") || !from_db("subcat",$subcat,"available")) && from_db("user",$_SESSION['userid'],"typ")<2)
            {
                unset($cat);
                unset($subcat);
                $item=get_errorpage();
            }
            else
            {
                $con_limit=make_limits("item","subcat",$subcat);
                $current_pos_name=from_db("subcat",$subcat,"name");
                $key1="item";
                $key2="subcat";
                $search=$subcat;
                $cat=from_db("subcat",$subcat,"cat");
            }
        }
        $add_limit="";
        if($con_limit)
        {
            $add_limit="<br>";
        }
        unset($edit);
        
        if($item_allowed_edit)
        {
            if($subcat || $cat)
            {
                if($subcat)
                {
                    $edit=" [<a class=\"item_edit\" href=\"admin.php?action=subcat&edit=".$subcat."\" target=\"_blank\">".language("ITEM_EDIT")."</a>]";
                    $edit2="<br><div class=\"item_add\">[<a class=\"item_add\" href=\"admin.php?action=item&new=yes&cat=".$cat."&subcat=".$subcat."\" target=\"_blank\">".language("ITEM_ADD")."</a>]</div>";
                }
                else
                {
                    $edit=" [<a class=\"item_edit\" href=\"admin.php?action=cat&edit=".$cat."\" target=\"_blank\">".language("ITEM_EDIT")."</a>]";
                    $edit2="<br><div class=\"item_add\">[<a class=\"item_add\" href=\"admin.php?action=subcat&new=yes&cat=".$cat."\" target=\"_blank\">".language("SUBCAT_ADD")."</a>]</div>";
                }
            }
        }
        $content="<table class=\"content_table\"><tr><td>";
        $list=from_db($key2,$search,"list");
        if($key1=="subcat")
        $av="available = 1 AND ";
        else
        $av="available = 1 AND visible = 1 AND ";
        if(from_db("user",$_SESSION['userid'],"typ")>1)
        {
            unset($av);
        }
        $use_list=$list && file_exists($template_lists_folder."/".$list);
        $limit=" LIMIT $start_page,$page_limit";
        if($use_list) unset($limit);
        $link=$pms_db_connection->query(make_sql($key1,$av.$key2." = '".$search."'","sort,name".$limit));
        $linkData = $pms_db_connection->fetchAllObject($link);
        if(!$use_list)
        {
            $content.="
            <div class=\"list_heading\">".from_db($key2,$search,"name").$edit."</div>".$edit2."
            <br>
            <table width=\"100%\" height=\"100%\"><tr><td>".$con_limit.$add_limit;
            $max=$config_values->list_rows;
            if($link && count($linkData)<$max) $max=mysqli_num_rows($link);
            if($max>1)
            {
                $add1="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr>";
                $add2="<td valign=\"top\" width=\"".round((100/$max),2)."%\">";
                $content.=$add1.$add2;
            }
            unset($b);
            $img=0;
            for($i=0;$i<count($linkData);$i++)
            {
                $b[$i]=$linkData[$i];
                if($a->image) $img=1;
            }
            if(is_array($b))
            {
                $i=0;
                foreach($b as $a)
                {
                    $content.=make_contentsmall($key1,$a->id,$img);
                    if($max>1)
                    {
                        if($i%$config_values->list_rows==$config_values->list_rows-1) $content.="</td></tr></table>".$add1.$add2;
                        else $content.="</td>".$add2;
                    }
                    $i++;
                }
            }
            if($max>1) $content.="</td></tr></table>";
            $content=smileys($content);
            $content.=$add_limit.$con_limit.'</td></tr></table>';
        }
        else // custom list view
        {
            $link2=$pms_db_connection->query(make_sql($key2,"id = '".$search."'",""));
            if($link2) $object=$pms_db_connection->fetchObject($link2);
            for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++) $objects[$i]=$a;
            $rows=$config_values->list_rows;
            $edit_link=$edit;
            $add_link=$edit2;
            unset($link);
            unset($link2);
            ob_start();
            unset($search);
            include $template_lists_folder."/".$list;
            $content.=ob_get_clean();
        }
        $content.="</td></tr></table>";
    }
    
    if($action=="item")
    {
        $cat=from_db("item",$item,"cat");
        $subcat=from_db("item",$item,"subcat");
    }
    if($action=="password_recover" && $password_recovery_activated)
    {
        $current_pos_name=language("PASSWORD_RECOVER_TITLE");
        $content="<table class=\"content_table\"><tr><td><div class=\"item_heading password_heading\">".language("PASSWORD_RECOVER_HEADING")."</div><br>";
        $user_sel=$_GET["user"]*1; // security
        $user_code=$_GET["id"];
        if($user_sel && $user_code)
        {
            $name=from_db("user",$user_sel,"name");
            $key=md5($name);
            $content.="<div class=\"password_recover\">";
            if($key==$user_code)
            {
                unset($pass);
                for($i=0;$i<2;$i++)
                {
                    $a=chr(rand(65,90));
                    $b=chr(rand(97,122));
                    $c=rand(0,9);
                    $pass=$pass.$a.$b.$c;
                }
                $pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET password = '".md5($pass)."' WHERE id = '$user_sel' LIMIT 1;");
                $content.=language("PASSWORD_RECOVER_SUCCESS");
                my_mail(from_db("user",$user_sel,"mail"),str_replace("%1",$config_values->name,language("PASSWORD_RECOVER_MAIL_SUBJECT")),
                str_replace(array('%1','%2'),array(from_db("user",$user_sel,"name"),$pass),language("PASSWORD_RECOVER_MAIL_BODY")));
            }
            else
            {
                $content.=language("PASSWORD_RECOVER_ERROR");
            }
            $content.="</div></center>";
        }
        else
        {
            if($password_recover)
            $content.="<div class=\"password_info\">".$password_recover."<br><br></div>";
            
            $content.=form()."<div align=\"center\"><table>
            <tr><td>".language("PASSWORD_RECOVER_NAME")."</td><td><input type=\"text\" name=\"name\" maxlength=\"32\"></td></tr>
            <tr><td></td></tr>
            <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"recover_pass\" value=\"".language("PASSWORD_RECOVER_BUTTON")."\"></center></td></tr>
            </table></center></div>
            </form>";
        }
        $content.="</td></tr></table>";
    }
    elseif($action=="password_recover")
    {
        $site_not_found=1;
    }
    
    if($action=="register_finish" && $_GET['id'] && $_GET['key'] && $register_activated)
    {
        $current_pos_name=language("REGISTER_FINISH_TITLE");
        $id=$_GET['id'];
        $content="<table class=\"content_table\"><tr><td class=\"finish_registration\"><div class=\"finish_registration item_heading\">".language("REGISTER_FINISH_HEADING")."</div><br><br>";
        $name=from_db("user",$id,"name");
        $key=from_db("user",$id,"register");
        if($key==$_GET['key'])
        {
            $content.=language("REGISTER_FINISH_SUCCESS");
            $pms_db_connection->query("UPDATE ".$pms_db_prefix."user SET active = 1 WHERE id = '$id' LIMIT 1;");
        }
        else
        {
            $content.=language("REGISTER_FINISH_ERROR");
        }
        $content.="</td></tr></table>";
    }
    elseif($action=="register_finish")
    {
        $site_not_found=1;
    }
    if($action=="sitemap")
    {
        $current_pos_name=language("SITEMAP_TITLE");
        $content="<table class=\"content_table\"><tr><td><div class=\"item_heading sitemap_heading\">".str_replace("%1",$config_values->name,language("SITEMAP_HEADING"))."</div><br>
        <table class=\"sitemap_table\">";
        $av="available = '1'";
        if($login && from_db("user",$_SESSION['userid'],"typ")>1)
        {
            unset($av);
        }
        $link=$pms_db_connection->query(make_sql("cat",$av,"sort,name"));
        while($link && $a=$pms_db_connection->fetchObject($link))
        {
            $content.="<tr><td>".make_link($a->name,"",$a->id,0,0)."</td></tr>
            ";
            $av="available = '1' AND ";
            if($login && from_db("user",$_SESSION['userid'],"typ")>1)
            {
                unset($av);
            }
            $link2=$pms_db_connection->query(make_sql("subcat",$av."cat = '$a->id'","sort,name"));
            while($link2 && $b=$pms_db_connection->fetchObject($link2))
            {
                $content.="<tr><td style=\"padding-left:20px\">".make_link($b->name,"",$a->id,$b->id,0)."</td></tr>
                ";
                $av="available = '1' AND visible = '1' AND ";
                if($login && from_db("user",$_SESSION['userid'],"typ")>1)
                {
                    unset($av);
                }
                $link3=$pms_db_connection->query(make_sql("item",$av."cat = '$a->id' AND subcat = '$b->id'","sort,name"));
                if(!$b->jump || mysqli_num_rows($link3)!=1)
                {
                    while($link3 && $c=$pms_db_connection->fetchObject($link3))
                    {
                        $content.="<tr class=\"sitemap_table\"><td class=\"sitemap_table\" style=\"padding-left:40px\">".make_link($c->name,"",$a->id,$b->id,$c->id)."</td></tr>
                        ";
                    }
                }
            }
        }
        $content.="</table></td></tr></table>";
    }
    
    if($action=="register" && $register_activated && !$login)
    {
        $current_pos_name=language("REGISTER_TITLE");
        $content="<table class=\"content_table\"><tr><td><div class=\"item_heading register\">".str_replace("%1",$config_values->name,language("REGISTER_HEADING"))."</div>
        <br>".form();
        if(!$register_error)
        {
            $content.="<div class=\"register_fail\">".$register_fail."<br></div>";
        }
        $content.="<div class=\"register_form\" align=\"center\">
        <table>
        <tr><td width=\"170px\">".language("REGISTER_NAME")."</td><td><input type=\"text\" name=\"name\" maxlength=\"32\" value=\"".$name."\"></td></tr>".make_antispam()."
        <tr><td>".language("REGISTER_PW")."</td><td><input type=\"password\" name=\"password\" maxlength=\"32\" value=\"".$password."\"></td></tr>
        <tr><td>".language("REGISTER_PW_REPEAT")."</td><td><input type=\"password\" name=\"passwordr\" maxlength=\"32\" value=\"".$passwordr."\"></td></tr>
        <tr><td>".language("REGISTER_MAIL")."</td><td><input type=\"text\" name=\"mail\" maxlength=\"100\" value=\"".$mail."\"><br><br></td></tr>
        <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"user\" value=\"".language("REGISTER_BUTTON")."\"></center></td></tr>
        </table></div>
        </form></td></tr></table>";
    }
    elseif($action=="register")
    {
        $site_not_found=1;
    }
    
    if($item || $site_not_found)
    {
        if($site_not_found)
        {
            $item=get_errorpage();
            unset($cat);unset($subcat);
        }
        if(from_db("item",$item,"typ")==4)
        {
            include(from_db("item",$item,"link"));
        }
        else
        {
            $current_pos_name=from_db("item",$item,"name");
            $av="available = 1 AND visible = 1 AND ";
            if(from_db("user",$_SESSION['userid'],"typ")>2)
            unset($av);
            
            $link=$pms_db_connection->query(make_sql("item",$av."subcat = '$subcat'","sort,name"));
            if(from_db("item",$item,"typ")!=3 && !$frontpage)
            {
                $max=0;
                unset($ids);
                while($link && $a=$pms_db_connection->fetchObject($link))
                {
                    $ids[$max]=$a->id;
                    if($a->id==$item)
                    $current=$max;
                    
                    $max++;
                }
                if($current>0)
                {
                    $last="<th align=\"left\"><div class=\"switch\">".make_link(language("ITEM_PREVIOUS"),"",$cat,$subcat,$ids[$current-1])."</div></th>";
                }
                if($max>$current+1)
                {
                    $next="<th align=\"right\"><div class=\"switch\">".make_link(language("ITEM_NEXT"),"",$cat,$subcat,$ids[$current+1])."</div></th>";
                }
            }
            unset($edit);
            if($item_edit_mode && $item)
            $edit=" [<a class=\"item_edit\" href=\"admin.php?action=item&edit=".$item."\">".language("ITEM_EDIT_EXTENDED")."</a>]";
            else if($item_allowed_edit && $item)
            $edit=" [<a class=\"item_edit\" href=\"index.php?item=".$item."&edit=true\">".language("ITEM_EDIT")."</a>]";
            
            if($item_edit_mode)
            $content.=form().'<input type="hidden" name="item_id" value="'.$item.'">';
            $content.="<table class=\"content_table\"><tr><td class=\"item_heading_td\"><div class=\"item_heading\">";
            
            $content.=edit_out(from_db("item",$item,"name"),"name","item_heading",$item_edit_mode).$edit."</div></td></tr>";
            if(from_db("item",$item,"showuser") && $config_values->writtenby)
            {
                $time=from_db("item",$item,"time");
                $refreshed="";
                $time_changed=from_db("item",$item,"time_changed");
                if($time_changed && $time_changed!=$time) $refreshed="<br> ".str_replace("%1",make_date($time_changed,1),language("ITEM_LAST_REFRESHED"));
                $content.="<tr><td><div class=\"item_user\">";
                if(from_db("item",$item,"typ")==2) // Download
                $add=language("ITEM_ADDED_BY");
                else $add=language("ITEM_WRITTEN_BY");
                $content.=str_replace(array("%1","%2"),array(from_db("user",from_db("item",$item,"user"),"name"),make_date($time,1)),$add);
                
                $content.=$refreshed."
                </div></td></tr>";
            }
            unset($rate_string);
            $a=from_db("item",$item,"numratings");
            $rate_string.="<div class=\"rating\">".language("RATE_INFO")." ";
            $rate_string.=make_rating($item,"item",0,0,1)." ";
            if($a==1) $rate_string.=language("RATE_CURRENT_ONE");
            else $rate_string.=str_replace("%1",$a,language("RATE_CURRENT_MULTI"));
            
            $cookie_name='rate_'.$item;
            if(!$_SESSION[$cookie_name] && !$_COOKIE[$cookie_name])
            {
                $rate_string.="<br>
                ".form()."
                <input type=\"hidden\" name=\"id\" value=\"".$item."\">".language("RATE_BAD")." ";
                for($i=1;$i<6;$i++)
                {
                    $sel="";
                    if($i==3)
                    {
                        $sel=" checked";
                    }
                    $rate_string.=" <input type=\"radio\" name=\"rating\" value=\"".$i."\"".$sel.">".$i;
                }
                $rate_string.=" ".language("RATE_GOOD")."&nbsp;&nbsp;<input type=\"submit\" name=\"rate\" value=\"".language("RATE_BUTTON")."\"></form><br>";
            }
            else
            {
                $rate_string.="<br><br>";
            }
            $content.="<tr><td class=\"item_heading_space\"><br>";
            if($config_values->rate && from_db("item",$item,"rate") && !$item_edit_mode)
            {
                $content.=$rate_string;
            }
            $content.="
            </td></tr>";
            $image=from_db("item",$item,"image");
            $typ=from_db("item",$item,"typ");
            if($typ==2)
            {
                if($config_values->predownload==1)
                {
                    if($config_values->speciallinks)$to=$config_values->page."/content/download/".link_name(from_db("item",$item,"name"))."-".$item.".html";
                    else $to="?action=download&id=".$item;
                }
                else
                {
                    $to=from_db("item",$item,"link");
                }
                $content.="<tr><td><div class=\"download\"><a href=\"".$to."\">".language("ITEM_DOWNLOAD_BUTTON")."</a></div></td></tr>";
            }
            $descr=from_db("item",$item,"description");
            if(!$item_edit_mode) $descr=def($descr);
            $des=edit_out($descr,"description","item_intro",$item_edit_mode,1);
            if($des && !ctype_space($des))
            {
                $content.="<tr><td class=\"item_intro_td\"><div class=\"item_intro\">".$des."</div><br></td></tr>";
            }
            $i_con=edit_out(from_db("item",$item,"content"),"content","",$item_edit_mode,2);
            if($typ!=1 && $image)
            {
                if(strstr($i_con,"#item_picture"))
                {
                    if(!$item_edit_mode)$i_con=str_replace("#item_picture",make_contentimg("item",$item,$image,1),$i_con);
                }
                else
                $content.="<tr><td><center>".make_contentimg("item",$item,$image,1)."<br><br></td></tr>";
            }
            if($i_con && !ctype_space($i_con))
            {
                $content.="
                <tr><td><div class=\"content_text\">".$i_con."</div><br></td></tr>";
            }
            if($last || $next)$content.="
            <tr style=\"width:100%;\"><td><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr>".$last.$next."</tr></table></td></tr>";
            $content.="</table>";
            if($item_edit_mode)
            $content.='<div align="center"><input type="submit" name="item_edit" value="'.language("ITEM_EDIT_SAVE").'"></form></div>';
            
            
            if($action=="download")
            {
                $content=replace_download($content,$id,$file,$file_link);
                $current_pos_name=replace_download($current_pos_name,$id,$file,$file_link);
                $link=$pms_db_connection->query(make_sql("dynamic","","id"));
                while($link && $a=$pms_db_connection->fetchObject($link))
                {
                    $search=$a->searcher;
                    $replace=$a->replacer;
                    if($a->makebr)
                    {
                        $search=def($search);
                        $replace=dynamic_string(def($replace),$item_allowed_edit,$a->id);
                    }
                    
                    $content=str_replace($search,$replace,$content);
                    $current_pos_name=str_replace($search,$replace,$current_pos_name);
                }
            }
            if($ip_is_banned)
            {
                $content=str_replace(array("#ip","#reason","#time"),array($banned_ip,$banned_reason,$banned_time),$content);
                $current_pos_name=str_replace(array("#ip","#reason","#time"),array($banned_ip,$banned_reason,$banned_time),$current_pos_name);
                $link=$pms_db_connection->query(make_sql("dynamic","","id"));
                while($link && $a=$pms_db_connection->fetchObject($link))
                {
                    $search=$a->searcher;
                    $replace=dynamic_string(def($replace),$item_allowed_edit,$a->id);
                    if($a->makebr)
                    {
                        $search=def($search);
                        $replace=dynamic_string(def($replace),$item_allowed_edit);
                    }
                    $content=str_replace($search,$replace,$content);
                    $current_pos_name=str_replace($search,$replace,$current_pos_name);
                }
            }
            
            if($item_edit_mode)
            $content.=get_tinymce("edit_content",500);
            //$content=make_dynamic($content);
            //$content=do_check($content);
            if(($config_values->comments && from_db("item",$item,"comments")) || $action=="guestbook")
            {
                if($action!="guestbook" || $guestbook_activated)
                {
                    $name="<input type=\"text\" maxlength=\"32\" size=\"40\" name=\"name\" value=\"".$com_name."\">";
                    $rand=rand(1000,9999);
                    $anti_spam=make_antispam();
                    $mail="<input type=\"text\" maxlength=\"64\" size=\"40\" name=\"mail\" value=\"".$com_mail."\">";
                    if($login==1)
                    {
                        $anti_spam="";
                        $name=from_db("user",$user_id,"name");
                        $mail=from_db("user",$user_id,"mail");
                    }
                    $comment_str=$comment_str."
                    <a name=\"comments\"></a>
                    <center><table align=\"center\" width=\"540px\">";
                    $com_title_top=language("COMMENT_TOP");
                    $com_title_middle=language("COMMENT_TITLE");
                    $com_title_bottom=language("COMMENT_BOTTOM");
                    $com_send=language("COMMENT_SEND");
                    
                    if($action=="guestbook")
                    {
                        $com_title_top=language("GUESTBOOK_TOP");
                        $com_title_middle=language("GUESTBOOK_TITLE");
                        $com_title_bottom=language("GUESTBOOK_BOTTOM");
                        $com_send=language("GUESTBOOK_SEND");
                    }
                    if($last_comment && $last_comment!=-1)
                    {
                        $comment_str=$comment_str."<tr><td colspan=\"2\"><div class=\"comment_error\"><center>".$last_comment."</center></div><br><br></td></tr>";
                    }
                    if($last_comment!=-1)
                    {
                        $comment_str=$comment_str.form()."<input type=\"hidden\" name=\"item\" value=\"".$item."\">";
                        if($login && (from_db("user",$_SESSION['userid'],"typ")>0 || from_db("comments",$id,"user")==$user_id) && $comment_get=="edit" && $id)
                        {
                            $com_title_top=language("COMMENT_EDIT");
                            $com_send=language("COMMENT_SAVE");
                            $com_title_bottom=language("COMMENT_BOTTOM");
                            if($action=="guestbook")
                            {
                                $com_title_top=language("GUESTBOOK_EDIT");
                                $com_send=language("GUESTBOOK_SAVE");
                            }
                            $link=$pms_db_connection->query(make_sql("comments","id = '$id'","id LIMIT 1"));
                            if($link)
                            {
                                $a=$pms_db_connection->fetchObject($link);
                            }
                            $name=$a->name;
                            if($a->user)
                            {
                                $name=from_db("user",$a->user,"name");
                            }
                            $comment_str=$comment_str."
                            <input type=\"hidden\" name=\"id\" value=\"".$id."\">
                            <tr><td colspan=\"2\"><div class=\"comment_write\">".$com_title_top."</div></td></tr>
                            <tr><td width=\"210px\">".language("COMMENT_NAME")."</td><td width=\"370px\">".$name."</td></tr>
                            <tr><td>".$com_title_middle."</td><td><input type=\"text\" name=\"title\" maxlength=\"64\" size=\"40\" value=\"".str_replace('"','&quot;',clear_comment($a->title))."\">
                            <tr><td>".$com_title_bottom."</td><td><textarea name=\"comment\" rows=\"5\" cols=\"39\">".clear_comment($a->comment)."</textarea></td></tr>
                            <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"edit_comment\" value=\"".$com_send."\"><br><br></center></form></td></tr></table>";
                        }
                        else
                        {
                            $comment_str=$comment_str."
                            <tr><td colspan=\"2\"><div class=\"comment_write\">".$com_title_top."</div></td></tr>
                            <tr><td width=\"210px\">".language("COMMENT_NAME")."</td><td width=\"370px\">".$name."</td></tr>
                            <tr><td>".language("COMMENT_MAIL");
                            if(!$login)
                            {
                                $comment_str.="<br>".language("COMMENT_MAIL_INFO");
                            }
                            $comment_str=$comment_str."</td><td>".$mail."</td></tr>".$anti_spam."
                            <tr><td>".$com_title_middle."</td><td><input type=\"text\" name=\"title\" maxlength=\"64\" size=\"40\" value=\"".str_replace('"','&quot;',stripslashes($com_title))."\">
                            <tr><td>".$com_title_bottom."</td><td><textarea name=\"comment\" rows=\"5\" cols=\"39\">".stripslashes($com_comment)."</textarea></td></tr>
                            <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"post_comment\" value=\"".$com_send."\"><br><br></center></form></td></tr></table>";
                        }
                    }
                }
                $comment_str=$comment_str."<table width=\"538px\">";
                // comments:
                $limit=$config_values->numcomments;
                $limit_name=language("COMMENT_SHOWALL");
                if($action=="guestbook")
                $limit_name=language("GUESTBOOK_SHOWALL");
                
                if($comments)
                {
                    if($comments=="all")
                    {
                        $comments=0;
                    }
                    $limit=$comments;
                }
                $limit2=$limit;
                if($limit<=0)
                {
                    $limit2=0;
                    $limit="";
                }
                else
                {
                    $limit=" LIMIT ".($limit+1);
                }
                $link=$pms_db_connection->query(make_sql("comments","item = '$item'","id DESC".$limit));
                for($i=0;($i<$limit2 || $limit2==0) && $link && $a=$pms_db_connection->fetchObject($link);$i++)
                {
                    $name=clear_comment($a->name);
                    $mail=$a->mail;
                    $ava="";
                    $add_name=" (<i>Gast</i>)";
                    $sig="";
                    $sig1="";
                    $sig2="";
                    if($a->user)
                    {
                        $name=make_link(clear_comment(from_db("user",$a->user,"name")),"action=user&id=".$a->user);
                        $add_name=" (<i>".$user_typ[from_db("user",$a->user,"typ")]."</i>)";
                        $mail=from_db("user",$a->user,"mail");
                        $ava=make_contentimg("user",$a->user,from_db("user",$a->user,"image"),0);
                        $sig=from_db("user",$a->user,"signatur");
                        if($sig && !ctype_space($sig))
                        {
                            $sig1="<hr class=\"signatur_line\"><div class=\"signatur\">";
                            $sig2="</div>";
                        }
                        else
                        {
                            $sig="";
                        }
                    }
                    $date=$a->date;
                    $title=$a->title;
                    if(!$title)
                    {
                        $title=language("COMMENT_LIST_NO_TITLE");
                    }
                    $ip="";
                    $rowspan=3;
                    unset($c_edit);
                    if($login)
                    {
                        $item_add=$item;
                        if($action=="guestbook")
                        {
                            unset($item_add);
                            $add_act="action=".$action."&";
                        }
                        if(from_db("user",$user_id,"typ")>=1 || $a->user == $user_id)
                        {
                            $c_edit=make_link_mark(make_img("edit.png",0)." ",$add_act."comment=edit&id=".$a->id,0,0,$item_add,"comments");
                        }
                        if(from_db("user",$user_id,"typ")>=1)
                        {
                            $delete=make_link_mark(make_img("delete.png",0),$add_act."comment=delete&id=".$a->id,0,0,$item_add,"comments");
                            $ip="<tr><td colspan=\"2\">".language("COMMENT_LIST_IP")." ".$a->ip."</td></tr>";
                            $rowspan+=1;
                        }
                    }
                    $comment_str=$comment_str."<tr><td width=\"434px\" colspan=\"2\"><a name=\"comment_".$a->id."\"></a><div class=\"comment_heading\">".clear_comment($title)."</div></td><td width=\"40px\">".$c_edit.$delete."</td><td width=\"64px\" rowspan=\"".$rowspan."\">".$ava."</td></tr>
                    <tr><td>".language("COMMENT_LIST_FROM")." ".$name.$add_name."</td><td>".language("COMMENT_LIST_DATE")." ".make_date($date,0)."</td></tr>".$ip."
                    <tr><td colspan=\"2\">";
                    if(($mail && !$a->user) || ($a->user && from_db("user",$a->user,"showmail")))
                    $comment_str=$comment_str.language("COMMENT_LIST_MAIL")." ".make_mail(clear_comment($mail));
                    
                    $comment_str=$comment_str."</td></tr>";
                    $comment_str=$comment_str."<tr><td colspan=\"4\"><div class=\"comment_content\">".clear_comment(def($a->comment))."</div>".$sig1.stripslashes(clear_comment(def($sig))).$sig2."<br></td></tr>";
                }
                $comment_str=$comment_str."</table>";
                if($link)
                {
                    if($a=$pms_db_connection->fetchObject($link))
                    {
                        $comment_str=$comment_str.make_link_mark($limit_name,"comments=all",$cat,$subcat,$item,"comments");
                    }
                }
                $comment_str=$comment_str."</center>";
            }
        }
    }
    // menu
    $link=$pms_db_connection->query(make_sql("menu","visible = 1 AND usertyp <= '$user_typ2'","sort,name"));
    if($config_values->menu_mode)
    {
        $menu='<div class="menu"><ul>';
        while($link && $a=$pms_db_connection->fetchObject($link)){
            $menu.='<li>';
            if($a->typ==0)
            {
                $class="";
                if($a->item)
                {
                    if($a->item==$item)
                    $class="menu_active";
                }
                else if($a->subcat)
                {
                    if($a->subcat==$subcat)
                    $class="menu_active";
                }
                else if($a->cat)
                {
                    if($a->cat==$cat)
                    $class="menu_active";
                }
                $menu.=make_link_mark($a->name,"",$a->cat,$a->subcat,$a->item,"",$class,0,"",0);
                if($a->popup && !$a->item)
                {
                    $what="subcat";
                    $what2="cat";
                    $filter="available = 1 AND ";
                    $id=$a->cat;
                    if($a->subcat)
                    {
                        $id=$a->subcat;
                        $what="item";
                        $what2="subcat";
                        $filter="visible = 1 AND available = 1 AND ";
                    }
                    if(from_db("user",$user_id,"typ")>1) unset($filter);
                    $link2=$pms_db_connection->query(make_sql($what,$filter.$what2." = '".$id."'","sort,name"));
                    if($link2 && mysqli_num_rows($link2))
                    {
                        $menu.='<!--[if IE 7]><!--></a><!--<![endif]--><ul><!--[if lte IE 6]><table class="menu_table"><tr><td><![endif]-->';
                        while($b=$pms_db_connection->fetchObject($link2))
                        {
                            unset($id2);
                            $id1=$b->id;
                            if($a->subcat)
                            {
                                $id1=$a->subcat;
                                $id2=$b->id;
                            }
                            $menu.='<li>'.make_link_mark($b->name,"",$a->cat,$id1,$id2,"").'</li>';
                        }
                    }
                    $menu.='<!--[if lte IE 6]></td></tr></table></a><![endif]--></ul></li>';
                }
                else $menu.="</a></li>";
            }
            else if($a->typ==1)
            {
                $class="";
                if(substr($plugin_intern[$a->plugin][1],0,1)!="#")
                {
                    if($plugin_intern[$a->plugin][1]==$action)
                    $class="menu_active";
                    $menu.=make_link($a->name,"action=".$plugin_intern[$a->plugin][1],0,0,0,$class)."</li>";
                }
                else
                {
                    if($a->plugin==6 && $frontpage) $class=" class=\"menu_active\"";
                    $menu.="<a".$class." href=\"".substr($plugin_intern[$a->plugin][1],1)."\">".$a->name."</a></li>";
                }
            }
            else if($a->typ==2)
            $menu.="<".$a->extern.">".$a->name."</a></li>";
            if($a->typ==3)
            $menu.="<a href=\"#\">".$a->name."</a></li>";
        }
        $menu.='</ul></div>';
    }
    else
    {
        $break=$config_values->menubreak;
        $vertical=$config_values->vertical;
        $menu_height=$config_values->menu_height;
        $menu_width=$config_values->menu_width;
        if($vertical)
        {
            $menu="<table class=\"menu_outer\"><tr><td><table class=\"menu_inner\">";
        }
        for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++)
        {
            if($break>1)
            {
                if($i%$break==0 && $i!=0)
                {
                    if(!$vertical)
                    {
                        $menu=$menu."</tr><tr>";
                    }
                    else
                    {
                        $menu=$menu."</table></td><td><table class=\"menu_inner\">";
                    }
                }
            }
            if($vertical)
            {
                $menu=$menu."<tr>";
            }
            $menu=$menu."<td width=\"".$menu_width."px\" height=\"".$menu_height."px\" class=\"menu\">";
            if($a->typ==0)
            $menu.=make_link($a->name,"",$a->cat,$a->subcat,$a->item,"menu");
            else if($a->typ==1)
            {
                if(substr($plugin_intern[$a->plugin][1],0,1)!="#")
                {
                    $menu.=make_link($a->name,"action=".$plugin_intern[$a->plugin][1],0,0,0,"menu");
                }
                else
                {
                    $menu.="<a class=\"menu\" href=\"".substr($plugin_intern[$a->plugin][1],1)."\">".$a->name."</a>";
                }
            }
            else if($a->typ==2)
            $menu.="<".$a->extern." class=\"menu\">".$a->name."</a>";
            if($a->typ==3)
            $menu.=$a->name;
            
            $menu=$menu."</td>
            ";
            if($vertical)
            {
                $menu=$menu."</tr>";
            }
        }
        if($vertical)
        {
            $menu=$menu."</table></td></tr></table>";
        }
    }
    
    // poll
    $link=$pms_db_connection->query(make_sql("poll","available = 1","sort,question"));
    $c=0;
    $d=0;
    while($link && $a=$pms_db_connection->fetchObject($link))
    {
        $ids[$c]=$a->id;
        $question[$c]=$a->question;
        for($i=1;$i<=10;$i++)
        {
            $to="answer".$i;
            $answer[$c][$i]=$a->$to;
            $to="answers".$i;
            $answers2[$c][$i]=$a->$to;
            $all[$c]+=$answers2[$c][$i];
        }
        if($current_poll)
        {
            if($ids[$c]==$current_poll)
            {
                $sel=$c;
            }
        }
        if(!$_SESSION['poll'.$ids[$c]] && !$_COOKIE['poll'.$ids[$c]])
        {
            $novote[$d]=$c;
            $d++;
        }
        $c++;
    }
    if($login==1 && from_db("user",$user_id,"typ")>1)
    {
        $poll=$poll."[<a href=\"admin.php?action=poll&new=yes\" target=\"_blank\">".language("POLL_ADD")."</a>]";
    }
    if($c==0)
    {
        $poll=$poll."<div class=\"poll_question\">".language("POLL_NOT_ACTIVE")."</div>";
    }
    else
    {
        $c--;
        $d--;
        if(!$current_poll)
        {
            $sel=rand(0,$c);
            if($d>=0)
            {
                $sel=rand(0,$d);
                $sel=$novote[$sel];
            }
        }
        $max_sel=$answers2[$sel][1];
        for($i=1;$i<=10;$i++)
        {
            if($answers2[$sel][$i]>$max_sel)
            {
                $max_sel=$answers2[$sel][$i];
            }
        }
        $poll=$poll."<div class=\"poll_question\">".$question[$sel]."</div>";
        if($login==1 && from_db("user",$user_id,"typ")>1)
        {
            $poll=$poll."[<a href=\"admin.php?action=poll&edit=".$ids[$sel]."\" target=\"_blank\">".language("POLL_EDIT")."</a>]<br>";
        }
        $poll=$poll."
        <br>";
        if(!$_SESSION["poll".$ids[$sel]] && !$_COOKIE["poll".$ids[$sel]] && !$current_poll)
        {
            $poll=$poll.form()."<input type=\"hidden\" name=\"poll_id\" value=\"".$ids[$sel]."\">";
            for($i=1;$i<=10;$i++)
            {
                if($answer[$sel][$i])
                {
                    $sele="";
                    if(!$first)
                    {
                        $first=1;
                        $sele=" checked";
                    }
                    $poll=$poll."<div class=\"poll_answer\"><input type=\"radio\" name=\"answer\" value=\"".$i."\"".$sele.">".$answer[$sel][$i]."</div>";
                }
            }
            $poll=$poll.hidden_positions()."<br><center><input type=\"submit\" name=\"poll\" value=\"".language("POLL_VOTE")."\">
            <br><input type=\"submit\" name=\"poll\" value=\"".language("POLL_RESULTS")."\"></center></form>";
        }
        else
        {
            for($i=1;$i<=10;$i++)
            {
                if($answer[$sel][$i])
                {
                    $width=120;
                    if($all[$sel]!=0)
                    {
                        $width=($answers2[$sel][$i]/$max_sel)*120;
                    }
                    $poll=$poll."<table><tr><td><div class=\"poll_answer\">".$answer[$sel][$i]." (".$answers2[$sel][$i].")</div></td></tr></table><table><tr><td class=\"poll_bar".$i."\" width=\"".$width."px\" height=\"4px\"></td></tr></table>";
                }
            }
            $poll=$poll."<br>".$all[$sel]." ".language("POLL_PARTICIPANTS");
        }
    }
    // Module: latest_comments
    $date=time()-86400*(int)$config_values->latest_comments_days;
    $count=count_db_exp("comments","WHERE date>='".$date."'");
    $count=rand(0,$count-1);
    $link=$pms_db_connection->query(make_sql("comments","date>='".$date."'","id LIMIT ".$count.",1"));
    if($link && $a=$pms_db_connection->fetchObject($link))
    {
        $item_temp=$a->item;
        $subcat_temp=from_db("item",$item_temp,"subcat");
        $subcat_temp=from_db("subcat",$subcat_temp,"cat");
        $name=$a->name;
        if($a->user) $name=make_link(clear_comment(from_db("user",$a->user,"name")),"action=user&id=".$a->user,0,0,0,"latest_comments_from");
        $latest_comments="<div class=\"latest_comments_from\">Von ".$name.":</div><div class=\"latest_comments_comment\">".
        small_comment(stripslashes(clear_comment(def($a->comment))))."</div>
        <div class=\"latest_comments_link\">[".make_link_mark("Anzeigen","comments=all",$cat_temp,$subcat_temp,$item_temp,"comment_".$a->id,"latest_comments_link")."]";
    }
    else
    $latest_comments="<div class=\"latest_comments_none\">".language("LATEST_COMMENTS_NONE")."</div>";
    
    if(!$login)
    {
        if($last_login)
        {
            $last_login=$last_login."<br>";
        }
        $user_str.=$last_login.form().hidden_positions()."<table class=\"user_panel\">";
        if($login_fail)
        {
            $user_str.="<tr><td colspan=\"2\"><center><div class=\"login_fail\">".$login_fail."</div></td></tr>";
        }
        $user_now="";
        if($_COOKIE["login_id"])
        {
            $user_now=from_db("user",$_COOKIE["login_id"]*1,"name");
        }
        $user_str.="
        <tr><td>".language("USER_NAME")."</td><td><input type=\"text\" name=\"name\" size=\"6\" value=\"".$user_now."\"></td></tr>
        <tr><td>".language("USER_PW")."</td><td><input type=\"password\" name=\"password\" size=\"6\"></td></tr>
        <tr><td colspan=\"2\"><center><input type=\"checkbox\" name=\"save_login\" value=\"1\"> ".language("USER_STAY_LOGGED_IN")."</center></td></tr>
        <tr><td colspan=\"2\"><center><input type=\"submit\" name=\"user_login\" value=\"".language("USER_LOGIN")."\"></center>
        </td></tr>";
        if($register_activated)
        {
            $user_str.="<tr><td colspan=\"2\"><center>".make_link_mark(language("USER_REGISTER"),"action=register",0,0,0,"","user_register")."</center></td></tr>";
        }
        if($password_recovery_activated)
        {
            $user_str.="<tr><td colspan=\"2\"><center>".make_link_mark(language("USER_PASSWORD_LOST"),"action=password_recover",0,0,0,"","user_pw_recover")."</center></td></tr>";
        }
        $user_str.="</table></form>";
    }
    else
    {
        $user_str.="<table class=\"user_panel\"><tr><td>".str_replace("%1",from_db("user",$user_id,"name"),language("USER_ONLINE"))."</td></tr>
        <tr><td>";
        if(!$_SESSION['last_login'])
        {
            $user_str.=language("USER_FIRST_TIME_ONLINE");
        }
        else
        {
            $user_str.=str_replace("%1",make_date($_SESSION['last_login'],0,1),language("USER_LAST_TIME_ONLINE"));
        }
        $user_str.="</td></tr>";
        $a=make_contentimg("user",$user_id,from_db("user",$user_id,"image"),0);
        if($a)
        $user_str.="<tr><td style=\"text-align:center;\">".$a."</td></tr>";
        
        $user_str.="<tr><td><center>(".make_link(language("USER_SETTINGS"),"action=user_panel",0,0,0,"user_settings").")</center></td></tr>
        <tr><td><center>(".make_link(language("USER_LOGOUT"),"action=logout",$cat,$subcat,$item,"user_logout",$id).")</center></td></tr></table>";
    }
    $title=$config_values->name;
    $con=$config_values->title;
    if($con)
    {
        $title=$title." - ".$current_pos_name;
    }
    $position_row=make_link("Home","","","","","position_row");
    if(!$subcat_jump && $item && (!$action || $action=="download") && $cat && $subcat)
    {
        $position_row.=" -&gt; ".make_link(from_db("cat",$cat,"name"),"",$cat,"","","position_row")." -&gt; ".make_link(from_db("subcat",$subcat,"name"),"","",$subcat,"","position_row")." -&gt; ";
        if(!$action) $position_row.=from_db("item",$item,"name"); else $position_row.=make_link(from_db("item",$id,"name"),"","","",$id,"position_row")." -&gt; Download";
    }
    elseif($subcat && !$action)
    {
        $position_row.=" -&gt; ".make_link(from_db("cat",$cat,"name"),"",$cat,"","","position_row")." -&gt; ".from_db("subcat",$subcat,"name");
    }
    elseif($cat && !$action)
    {
        $position_row.=" -&gt; ".from_db("cat",$cat,"name");
    }
    elseif($action)
    {
        $position_row.=" -&gt; ".$current_pos_name;
    }
    elseif(from_db("item",$item,"special")==3)
    {
        $position_row.=" -&gt; ".$current_pos_name;
    }
    else
    {
        $position_row="Home";
    }
    $link=$pms_db_connection->query(make_sql("user","","id"));
    $first_bday=1;
    $day=date('d');
    $month=date('m');
    while($link && $bday=$pms_db_connection->fetchObject($link))
    {
        if($bday->bday && $day==date('d',$bday->bday) && $month==date('m',$bday->bday))
        {
            if($first_bday)
            $first_bday=0;
            else
            $birthday.=", ";
            
            $birthday.="\"".make_link($bday->name,"action=user&id=".$bday->id,"","","")."\" (".make_age($bday->bday).")";
        }
    }
    if(!$first_bday)
    $birthday="<div class=\"birthday\">".str_replace("%1",$birthday,language("BIRTHDAY"))."</div>";
    
    $mincomments=$config_values->mincomments;
    unset($add);
    if(!$login || from_db("user",$login,"typ")<2) $add=" AND available = '1' AND (visible = '1' or special != '0')";
    $link=$pms_db_connection->query(make_sql("item","","id",$pms_db_prefix."item.*, COUNT(".$pms_db_prefix."comments.id) AS comments_num","id HAVING comments_num >='".$mincomments."' AND special != '4' AND comments = '1'".$add,"id","LEFT JOIN ".$pms_db_prefix."comments ON (".$pms_db_prefix."comments.item = ".$pms_db_prefix."item.id)"));
    $discuss_array=array();
    for($i=0;$link && $a=$pms_db_connection->fetchObject($link);$i++) $discuss_array[$i]=$a->id;
    if(count($discuss_array)>0)
    {
        $random_top=$discuss_array[array_rand($discuss_array)];
        $most_discussed=content_mostdiscussed($random_top);
    }
    else
    {
        $most_discussed="<table><tr><td><center>".language("MOST_DISCUSSED_NONE")."</center></td></tr></table>";
    }
    include('counter.php');
    
    $user_counter="
    <div class=\"user_counter\">".language("COUNTER_OVERALL")." ".$number_visitors."<br>
    ".language("COUNTER_ONLINE")." ".count_db_exp("visitors_counter","WHERE time>='".(time()-60*$config_values->visitors_lifetime)."'")."<br>
    ".language("COUNTER_TODAY")." ".$config_values->visitors_today."<br>
    ".language("COUNTER_YESTERDAY")." ".$config_values->visitors_yesterday."<br>
    ".language("COUNTER_IP")." ".$_SERVER["REMOTE_ADDR"]."<br>
    ".language("COUNTER_COMMENTS")." ".count_db("comments")."<br>
    ".language("COUNTER_VALUES")." ".sum_db("item","numratings")."<br>
    ".language("COUNTER_ITEMS")." ".count_db("item")."<br>
    ".language("COUNTER_USERS")." ".count_db("user")."</div>";
    $search_plugin=form("","get")."<table class=\"search\"><tr class=\"search\"><td class=\"search\"><center><input type=\"text\" class=\"search_field\" size=\"17\" name=\"search_query\" value=\"".str_replace('"',"&quot;",$search_query3)."\"><br>
    <input type=\"submit\" class=\"search_button\" name=\"search\" value=\"".language("SEARCH_BUTTON")."\"></form></center></td></tr></table>";
    $poll=smileys($poll);
    unset($dyn);
    
    for($i=0;!$item_edit_mode && $i<2;$i++)
    {
        $content=replace_dynamic($content,$dyn,!$dyn);
        if(!$dyn)
        {
            $dyn=$content[1];
            $content=$content[0];
        }
        $content=make_dynamic($content);
        $content=do_check(make_dynamic(trim($content)));
    }
    
    $position_row=replace_dynamic($position_row);
    if($comment_str) $comment_str=smileys($comment_str);
    
    if(file_exists($template))
    {
        $search=get_template();
        $out=$template_content;
        if(!strstr($out,"#comments_list")) $out=str_replace("#content","#content#comments_list",$out);
        $replace=array($content,$title,$menu,$user_str,$poll,$footer,$user_counter,$birthday,$top_user,$most_discussed,$search_plugin,$position_row,$latest_comments,$comment_str,$newsletter);
        for($i=0;$i<2;$i++)
        $out=replace_dynamic(do_check(make_dynamic(trim($out))));
        
        if($item_edit_mode)
        {
            $search[count($search)]=$search[0];
            $replace[count($replace)]=$content;
            unset($search[0]);
            unset($replace[0]);
        }
        $out=str_replace($search,$replace,$out);
        echo ($out);
    }
    ?>