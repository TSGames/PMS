<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

/**
 * Admin confirmation dialog template
 * Renders delete/confirm dialog with buttons
 *
 * @param string $message Message to display
 * @param string $item_label Label for the item being deleted (e.g. "Menüeintrag")
 * @param string $action Current action name
 * @param int $item_id ID of item to delete
 * @param string $submit_button_name Name of submit button to use
 * @param string $submit_button_label Label for submit button
 * @return void Outputs HTML
 */

echo form().heading('Bestätigung erforderlich');
?>
<table>
<tr><td colspan="2">
Möchten Sie diesen <?php echo htmlspecialchars($item_label); ?> wirklich löschen?<br>
Diese Aktion kann nicht rückgängig gemacht werden.
</td></tr>
<tr><td colspan="2"><center>
<input type="hidden" name="id" value="<?php echo $item_id; ?>">
<input type="submit" name="<?php echo htmlspecialchars($submit_button_name); ?>" value="<?php echo htmlspecialchars($submit_button_label); ?>" style="background-color: #cc0000; color: white;">
<a href="admin.php?action=<?php echo htmlspecialchars($action); ?>" class="button">Abbrechen</a>
</center></td></tr>
</table>
</form>
