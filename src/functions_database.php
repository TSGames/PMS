<?php
// Module: functions_database.php

	/**
	 * Build SQL SELECT query
	 *
	 * @param table Table name
	 * @param exp WHERE expression
	 * @param order ORDER BY field
	 * @param field SELECT fields (default *)
	 * @param group GROUP BY field
	 * @param add_where Add default WHERE clause
	 * @param join JOIN clause
	 * @return string SQL SELECT query
	 */
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

	/**
	 * Compare two values for sorting
	 *
	 * @param a First value
	 * @param b Second value
	 * @return int Comparison result (-1, 0, 1)
	 */
	function compare($a,$b)
	{
		if($a[0]==$b[0])
			{
			return 0;
		}
		return $a[0] < $b[0]? 1: -1;
	}

	/**
	 * Count records matching WHERE expression
	 *
	 * @param table Table name
	 * @param exp WHERE expression
	 * @return int Number of matching records
	 */
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

	/**
	 * Sum values from table
	 *
	 * @param table Table name
	 * @param where WHERE expression
	 * @param exp Field to sum
	 * @return float Sum of values
	 */
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

	/**
	 * Count total records in table
	 *
	 * @param table Table name
	 * @return int Total record count
	 */
	function count_db($table)
	{
		return count_db_exp($table,"");
	}

?>
