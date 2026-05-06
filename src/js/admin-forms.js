/**
 * Admin Forms JavaScript
 * Handles form interactions, field visibility, button states, etc.
 */

/**
 * Disable/hide submit buttons
 * Used when form is being processed to prevent duplicate submissions
 *
 * @param {string|Array} buttonIds Single ID, comma-separated IDs, or array of IDs
 */
function disable_buttons(buttonIds) {
	var ids = Array.isArray(buttonIds) ? buttonIds :
	          typeof buttonIds === 'string' ? buttonIds.split(',') : [buttonIds];

	ids.forEach(function(id) {
		var elem = document.getElementById(id.trim());
		if(elem) {
			elem.style.display = 'none';
		}
	});
}

/**
 * Check radio button by name and value
 * Used to programmatically select radio options
 *
 * @param {string} name Radio button name attribute
 * @param {string|number} value Radio button value
 */
function check_radio(name, value) {
	var radios = document.querySelectorAll('input[name="'+name+'"]');
	radios.forEach(function(radio) {
		radio.checked = (radio.value == value);
	});
}

/**
 * Toggle element visibility
 *
 * @param {string} elementId Element ID
 * @param {boolean} show True to show, false to hide
 */
function toggle_visibility(elementId, show) {
	var elem = document.getElementById(elementId);
	if(elem) {
		elem.style.display = show ? '' : 'none';
	}
}

/**
 * Basic form validation
 * Checks required fields are not empty
 *
 * @param {string} formId Form ID
 * @returns {boolean} True if form valid
 */
function form_validation(formId) {
	var form = document.getElementById(formId);
	if(!form) form = document.querySelector('form');

	var requiredFields = form.querySelectorAll('[required]');
	var valid = true;

	requiredFields.forEach(function(field) {
		if(!field.value || field.value.trim() === '') {
			field.style.backgroundColor = '#ffcccc';
			valid = false;
		} else {
			field.style.backgroundColor = '';
		}
	});

	return valid;
}

/**
 * Show/hide dependent form sections
 * Used for conditional form field display
 *
 * @param {string} conditionValue Value that determines visibility
 * @param {Object} visibilityMap Map of condition values to element IDs
 *                 e.g. {0: 'section1', 1: 'section2', 2: 'section3'}
 */
function show_form_sections(conditionValue, visibilityMap) {
	Object.keys(visibilityMap).forEach(function(key) {
		var elemId = visibilityMap[key];
		var elem = document.getElementById(elemId);
		if(elem) {
			elem.style.display = (key == conditionValue) ? '' : 'none';
		}
	});
}

/**
 * Enable/disable form fields
 * Used to lock fields based on conditions
 *
 * @param {Array} fieldNames Field name attributes
 * @param {boolean} enable True to enable, false to disable
 */
function set_field_state(fieldNames, enable) {
	fieldNames.forEach(function(name) {
		var fields = document.querySelectorAll('[name="'+name+'"]');
		fields.forEach(function(field) {
			field.disabled = !enable;
		});
	});
}
