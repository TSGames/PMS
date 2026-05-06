<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

/**
 * Admin table header template
 * Renders <table> with column headers
 *
 * @param string $headers Pipe-delimited format: "Column1:width|Column2:width|..."
 * @return void Outputs HTML
 */

// Parse header string: "Name:200px|Status:80px|Actions:100px"
$parts = explode('|', $headers);
$cols = [];

echo '<table class="group">';
echo '<tr>';
foreach($parts as $part) {
	$part = trim($part);
	if(strpos($part, ':') !== false) {
		list($name, $width) = explode(':', $part, 2);
		$name = trim($name);
		$width = trim($width);
		echo '<th style="width: '.$width.';">'.$name.'</th>';
	} else {
		echo '<th>'.$part.'</th>';
	}
}
echo '</tr>';
