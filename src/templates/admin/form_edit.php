<?php
if (!defined('PMS_ADMIN_ENTRY')) {
	header('HTTP/1.0 403 Forbidden');
	exit('Direct access not allowed');
}

/**
 * Admin form edit template
 * Wraps form with consistent heading, hidden ID field, and submit button
 *
 * @param string $title Form title
 * @param string $form_content HTML form fields
 * @param string $submit_label Button label (e.g. "Speichern")
 * @param string|null $form_id Optional form name for JavaScript
 * @param string|null $id_value Optional hidden ID value
 * @return void Outputs HTML
 */

echo form().heading($title);
?>
<table>
<?php
if($id_value !== null) {
	echo '<input type="hidden" name="id" value="'.$id_value.'">';
}
echo $form_content;
?>
<tr><td colspan="2"><center>
<input type="submit" name="submit" value="<?php echo htmlspecialchars($submit_label); ?>">
</center></td></tr>
</table>
</form>
