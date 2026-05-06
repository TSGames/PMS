<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

/**
 * Admin template helper functions
 * Provides convenient shortcuts for rendering reusable admin template components
 */

/**
 * Render table header with column definitions
 *
 * @param string $headers Pipe-delimited: "Name:200px|Status:80px|Actions:100px"
 * @return string|false HTML table header or false on error
 */
function render_table_header($headers) {
	ob_start();
	include 'templates/admin/table_header.php';
	return ob_get_clean();
}

/**
 * Close and output table row closing tags
 *
 * @return string HTML table closing
 */
function render_table_footer() {
	return '</table>';
}

/**
 * Render edit/create form wrapper
 *
 * @param string $title Form title
 * @param string $form_content HTML form field content
 * @param string $submit_label Submit button label (default: "Speichern")
 * @param string $form_id Optional form identifier for JS
 * @param mixed $id_value Optional hidden ID value
 * @return string|false HTML form or false on error
 */
function render_form_edit($title, $form_content, $submit_label = 'Speichern', $form_id = null, $id_value = null) {
	ob_start();
	include 'templates/admin/form_edit.php';
	return ob_get_clean();
}

/**
 * Render single form field (text input)
 *
 * @param string $label Field label
 * @param string $name Input name attribute
 * @param string $value Input value
 * @param string $type Input type (default: text)
 * @param int $size Input size/cols
 * @return string HTML form field
 */
function render_form_field($label, $name, $value = '', $type = 'text', $size = 60) {
	$html = '<tr><td>'.$label.':</td><td>';

	if($type === 'textarea') {
		$html .= '<textarea name="'.htmlspecialchars($name).'" rows="10" cols="'.$size.'">'.htmlspecialchars($value).'</textarea>';
	} elseif($type === 'checkbox') {
		$checked = $value ? ' checked' : '';
		$html .= '<input type="checkbox" name="'.htmlspecialchars($name).'" value="1"'.$checked.'>';
	} else {
		$html .= '<input type="'.$type.'" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" size="'.$size.'">';
	}

	$html .= '</td></tr>';
	return $html;
}

/**
 * Render dropdown/select field
 *
 * @param string $label Field label
 * @param string $name Select name attribute
 * @param array $options Array of value => label pairs
 * @param mixed $selected Currently selected value
 * @return string HTML select field
 */
function render_form_select($label, $name, $options = [], $selected = null) {
	$html = '<tr><td>'.$label.':</td><td>';
	$html .= '<select name="'.htmlspecialchars($name).'">';

	foreach($options as $value => $label) {
		$sel = ($value === $selected || $value == $selected) ? ' selected' : '';
		$html .= '<option value="'.htmlspecialchars($value).'"'.$sel.'>'.htmlspecialchars($label).'</option>';
	}

	$html .= '</select></td></tr>';
	return $html;
}

/**
 * Render confirmation dialog
 *
 * @param string $message Confirmation message
 * @param string $item_label Item type label (e.g. "Menüeintrag")
 * @param string $action Current admin action
 * @param int $item_id Item ID to delete
 * @param string $submit_button_name Name attribute for submit button
 * @param string $submit_button_label Label text for submit button (default: "Löschen")
 * @return string|false HTML confirmation dialog or false on error
 */
function render_dialog_confirm($message = null, $item_label = 'Eintrag', $action = '', $item_id = 0, $submit_button_name = 'delete', $submit_button_label = 'Löschen') {
	ob_start();
	include 'templates/admin/dialog_confirm.php';
	return ob_get_clean();
}

/**
 * Render action links (edit, delete, copy)
 *
 * @param string $action Current admin action
 * @param int $item_id Item ID
 * @param bool $include_copy Include "copy" link (default: false)
 * @param bool $include_delete Include "delete" link (default: true)
 * @return string HTML action links
 */
function render_action_links($action, $item_id, $include_copy = false, $include_delete = true) {
	$html = '<a href="admin.php?action='.htmlspecialchars($action).'&edit='.$item_id.'">Bearbeiten</a>';

	if($include_copy) {
		$html .= ' | <a href="admin.php?action='.htmlspecialchars($action).'&copy='.$item_id.'">Kopieren</a>';
	}

	if($include_delete) {
		$html .= ' | <a href="admin.php?action='.htmlspecialchars($action).'&delete='.$item_id.'">Löschen</a>';
	}

	return $html;
}

/**
 * Generate table row from array data
 *
 * @param array $row Data array
 * @param array $columns Column names to display
 * @return string HTML table row
 */
function render_table_row($row, $columns = []) {
	$html = '<tr>';

	if(empty($columns)) {
		// Auto-detect columns from row keys
		$columns = array_keys($row);
	}

	foreach($columns as $col) {
		$value = isset($row[$col]) ? $row[$col] : '';
		$html .= '<td>'.htmlspecialchars($value).'</td>';
	}

	$html .= '</tr>';
	return $html;
}

?>
