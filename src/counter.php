<?php
// user counter
$this_ip=$pms_db_connection->escape(preg_replace('/^(\d+\.\d+)\..*$/', '$1',$_SERVER["REMOTE_ADDR"]));
$sid = $pms_db_connection->escape(session_id());
$browser=$pms_db_connection->escape($_SERVER["HTTP_USER_AGENT"]);
$user_id=$_SESSION["userid"];
$day=date('j');
if($cat) 
{
$typ=0;
$con=$cat;
}
if($subcat)
{
$typ=1;
$con=$subcat;
}
if($item)
{
$typ=2;
$con=$item;
}
if($action)
{
$typ=3;
$con=@array_search($action,$valid_actions);
}
if($admin_center) 
{
$typ=4;
$con=@array_search($action,$action_list);
}
if(from_db("config",1,"visitors_day")!=$day)
{
$yesterday=from_db("config",1,"visitors_today");
$pms_db_connection->query("UPDATE ".$pms_db_prefix."config SET visitors_today = 0, visitors_day = '$day', visitors_yesterday = '$yesterday';");
$pms_db_connection->query("TRUNCATE TABLE ".$pms_db_prefix."visitors;");
}
$min=86400;
if(from_db("config",1,"visitors_lifetime")*60>$min) $min=from_db("config",1,"visitors_lifetime")*60;
$time_up=time()-$min;
$time=time();
$pms_db_connection->query("DELETE FROM ".$pms_db_prefix."visitors_counter WHERE time < '$time_up'");
if(from_db("visitors_counter",$sid,"id")==$sid) $pms_db_connection->query("UPDATE ".$pms_db_prefix."visitors_counter SET browser = '$browser', user = '$user_id', typ = '$typ', content = '$con', time = '$time' WHERE id = '$sid'");
else $pms_db_connection->query("INSERT INTO ".$pms_db_prefix."visitors_counter (id,browser,user,typ,content,time) VALUES ('$sid','$browser','$user_id','$typ','$con','$time')");

$link=$pms_db_connection->query("SELECT id FROM ".$pms_db_prefix."visitors WHERE ip = '$this_ip' LIMIT 1;");
$ok=0;
if($link)
{
$ok=$pms_db_connection->fetchObject($link);
$ok=$ok->id;
}
if(!$ok)
{
$pms_db_connection->query("INSERT INTO ".$pms_db_prefix."visitors (ip) VALUES ('$this_ip');");
$pms_db_connection->query("UPDATE ".$pms_db_prefix."config SET visitors = visitors+1, visitors_today = visitors_today+1;");
}
$number_visitors=(int)from_db("config",1,"visitors")+(int)from_db("config",1,"visitors_increment");

// \\user counter
?>