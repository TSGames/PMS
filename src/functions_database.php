<?php
// Module: functions_database.php

	function make_sql($table,$exp="",$order="id",$field="*",$group="",$add_where=1,$join="")
	{
		global $pms_db_prefix;
		if($join) $join=" ".$join;
		if($exp)
			{
			if($add_where) $exp=" WHERE ".$exp;
			else $exp=" ".$exp;
		}
		if($order) $order=" ORDER BY ".$order;
		if($group) $group=" GROUP BY ".$group;
		return "Select ".$field." FROM ".$pms_db_prefix.$table.$join.$exp.$group.$order.";";
	}

	function compare($a,$b)
	{
		if($a[0]==$b[0])
			{
			return 0;
		}
		return $a[0] < $b[0]? 1: -1;
	}

	function count_db_exp($table,$exp)
	{
		global $pms_db_connection;
		global $pms_db_prefix;
		if($link=$pms_db_connection->query("SELECT COUNT(id) FROM ".$pms_db_prefix.$table." ".$exp.";"))
			{
			$link=$pms_db_connection->fetch($link);
			return $link[0];
		}
		return(0);
	}

	function sum_db($table,$where,$exp="")
	{
		global $pms_db_connection;
		global $pms_db_prefix;
		if($link=$pms_db_connection->query("SELECT SUM(".$where.") FROM ".$pms_db_prefix.$table." ".$exp))
			{
			$link=$pms_db_connection->fetch($link);
			if(!$link[0])
				{
				$link[0]=0;
			}
			return $link[0];
		}
		return 0;
	}

	function count_db($table)
	{
		return count_db_exp($table,"");
	}

?>
