<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

// Module: admin_action_dispatcher.php
// Action routing dispatcher for admin handlers

/**
 * Dispatch admin action to appropriate handler function
 */
function dispatch_admin_action($action)
{
	global $pms_db_connection;

	$action_map = [
		'home'          => 'handle_admin_home',
		'menu'          => 'handle_admin_menu',
		'user'          => 'handle_admin_user',
		'cat'           => 'handle_admin_cat',
		'subcat'        => 'handle_admin_subcat',
		'item_restore'  => 'handle_admin_item_restore',
		'item_recover'  => 'handle_admin_item_recover',
		'item'          => 'handle_admin_item',
		'var'           => 'handle_admin_var',
		'poll'          => 'handle_admin_poll',
		'bans'          => 'handle_admin_bans',
		'config'        => 'handle_admin_config',
		'events'        => 'handle_admin_events',
		'backup'        => 'handle_admin_backup',
		'activity'      => 'handle_admin_activity',
	];

	if (!$action) {
		$action = 'home';
	}

	if (isset($action_map[$action])) {
		$handler = $action_map[$action];

		if ($action === 'item_recover' && !from_db("item", $_GET["item"], "id")) {
			return;
		}

		if (function_exists($handler)) {
			$handler();
		}
	}
}

?>
