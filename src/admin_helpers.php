<?php
// Module: admin_helpers.php
// Shared helpers for admin action handlers

/**
 * Require minimum admin permission level
 * Terminates if insufficient permissions
 *
 * @param int $min_level Minimum user type (2=admin, 3=super-admin)
 * @return void Dies if permission insufficient
 */
function require_admin_permission($min_level = 2)
{
	global $_SESSION;
	if(!isset($_SESSION['userid']) || !isset($_SESSION['usertyp']))
		die('Unauthorized');
	if($_SESSION['usertyp'] < $min_level)
		die('Insufficient permissions');
}

/**
 * Load entity from database
 *
 * @param string $table Table name
 * @param int $id Entity ID
 * @return object|false Database record or false
 */
function load_admin_entity($table, $id)
{
	if(!$id) return false;
	return from_db($table, $id, '*');
}

/**
 * Handle entity deletion confirmation and action
 *
 * @param string $table Table name
 * @param int $id Entity ID
 * @param string $display_name Display name for confirmation
 * @param int $min_permission Minimum permission level required
 * @return bool True if delete confirmed and executed
 */
function handle_admin_delete($table, $id, $display_name, $min_permission = 2)
{
	global $pms_db_connection, $pms_db_prefix;

	if($_GET["delete"] == $id)
	{
		if(from_db("user", @$_SESSION['userid'], "typ") < $min_permission)
			return false;

		$pms_db_connection->query("DELETE FROM " . $pms_db_prefix . $table . " WHERE id = '$id' LIMIT 1");
		return true;
	}
	return false;
}

/**
 * Render entity list table with action links
 *
 * @param string $table Table name
 * @param array $columns Column names to display
 * @param string $action_name Action parameter for links
 * @param string $where_clause Optional WHERE clause
 * @return string HTML table
 */
function render_admin_entity_list($table, $columns, $action_name, $where_clause = "1")
{
	global $pms_db_connection, $pms_db_prefix;

	$link = $pms_db_connection->query(make_sql($table, $where_clause, implode(',', $columns) . ",id"));

	if(!$link)
		return '<p>Datenbankfehler.</p>';

	$result_count = 0;
	if(method_exists($link, 'num_rows'))
		$result_count = $link->num_rows;
	else if(function_exists('mysqli_num_rows'))
		/** @psalm-suppress InvalidArgument */
		$result_count = mysqli_num_rows($link);

	if($result_count == 0)
		return '<p>Keine Einträge gefunden.</p>';

	$html = '<table><tr>';
	foreach($columns as $col)
		$html .= '<th>' . htmlspecialchars($col) . '</th>';
	$html .= '<th>Aktionen</th></tr>';

	while($row = $pms_db_connection->fetchObject($link))
	{
		$html .= '<tr>';
		foreach($columns as $col)
			$html .= '<td>' . htmlspecialchars($row->$col) . '</td>';
		$html .= '<td>';
		$html .= '<a href="admin.php?action=' . $action_name . '&edit=' . $row->id . '">Bearbeiten</a> | ';
		$html .= '<a href="admin.php?action=' . $action_name . '&delete=' . $row->id . '" onclick="return confirm(\'Wirklich löschen?\')">Löschen</a>';
		$html .= '</td></tr>';
	}

	$html .= '</table>';
	return $html;
}

/**
 * Render admin form header with breadcrumb
 *
 * @param string $title Page title
 * @param string $action Current action
 * @return string HTML header
 */
function render_admin_header($title, $action = '')
{
	global $config_values;

	$breadcrumb = '<a href="admin.php">Home</a>';
	if($action)
		$breadcrumb .= ' &gt; ' . htmlspecialchars($title);

	return '<div style="margin-bottom:10px; font-size:0.9em;">' . $breadcrumb . '</div>' .
	       heading($title);
}

/**
 * Get user type label
 *
 * @param int $type User type code
 * @return string User type label
 */
function get_user_type_label($type)
{
	global $user_typ;
	return isset($user_typ[$type]) ? $user_typ[$type] : 'Unknown';
}

/**
 * Format timestamp for admin display
 *
 * @param int $timestamp Unix timestamp
 * @return string Formatted date/time
 */
function format_admin_timestamp($timestamp)
{
	if(!$timestamp) return 'Never';
	return date('d.m.Y H:i:s', $timestamp);
}

/**
 * Render confirmation dialog for destructive action
 *
 * @param string $message Message to display
 * @param string $confirm_url URL if confirmed
 * @param string $cancel_url URL if cancelled
 * @return string HTML confirmation form
 */
function render_admin_confirm_dialog($message, $confirm_url, $cancel_url = 'javascript:history.back()')
{
	return '<div style="text-align:center; padding:20px;">' .
	       '<p>' . htmlspecialchars($message) . '</p>' .
	       '<a href="' . $confirm_url . '" class="button" style="background:red;">Ja, löschen</a> ' .
	       '<a href="' . $cancel_url . '" class="button" style="background:gray;">Abbrechen</a>' .
	       '</div>';
}

?>
