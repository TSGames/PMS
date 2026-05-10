/**
 * Admin Tables JavaScript
 * Handles table interactions: sorting, row selection, pagination, etc.
 */

/**
 * Sort table by column
 * Moves selected row before or after adjacent rows
 *
 * @param {string} action Admin action name
 * @param {number} newPosition New sort position
 * @param {number} itemId Item ID to reorder
 */
function sort_table_item(action, newPosition, itemId) {
	window.location.href = 'admin.php?action=' + action + '&sort=yes&pos=' + newPosition + '&id=' + itemId;
}

/**
 * Toggle row selection
 * Useful for batch operations
 *
 * @param {string} tableId Table ID
 * @param {boolean} selectAll True to select all, false to deselect
 */
function toggle_row_selection(tableId, selectAll) {
	var table = document.getElementById(tableId);
	if(!table) return;

	var checkboxes = table.querySelectorAll('input[type="checkbox"][name*="ids"]');
	checkboxes.forEach(function(checkbox) {
		checkbox.checked = selectAll;
	});
}

/**
 * Highlight table row on hover
 *
 * @param {Element} rowElement Table row element
 * @param {boolean} highlight True to highlight, false to remove
 */
function highlight_row(rowElement, highlight) {
	if(highlight) {
		rowElement.style.backgroundColor = '#f0f0f0';
	} else {
		rowElement.style.backgroundColor = '';
	}
}

/**
 * Add hover effects to table rows
 *
 * @param {string} tableId Table ID
 * @param {string} hoverColor Background color on hover (default: #f0f0f0)
 */
function init_table_hover(tableId, hoverColor) {
	hoverColor = hoverColor || '#f0f0f0';
	var table = document.getElementById(tableId);
	if(!table) return;

	var rows = table.querySelectorAll('tbody tr');
	rows.forEach(function(row) {
		row.addEventListener('mouseover', function() {
			highlight_row(row, true);
		});
		row.addEventListener('mouseout', function() {
			highlight_row(row, false);
		});
	});
}

/**
 * Initialize table with event handlers
 * Call once on page load for interactive tables
 */
function init_admin_tables() {
	// Auto-init hover for all tables with class "group"
	var tables = document.querySelectorAll('table.group');
	tables.forEach(function(table, index) {
		var tableId = table.id || 'table_' + index;
		init_table_hover(tableId);
	});
}

// Initialize on document ready
if(document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init_admin_tables);
} else {
	init_admin_tables();
}
