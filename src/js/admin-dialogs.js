/**
 * Admin Dialogs JavaScript
 * Handles dialog/modal interactions, confirmations, etc.
 */

/**
 * Show confirmation dialog before performing action
 * Used for delete and other destructive operations
 *
 * @param {string} message Message to display
 * @param {string} itemLabel Label for item type (e.g. "Menüeintrag")
 * @returns {boolean} True if confirmed, false if cancelled
 */
function confirm_action(message, itemLabel) {
	itemLabel = itemLabel || 'Eintrag';
	var fullMessage = (message || 'Möchten Sie diesen ' + itemLabel + ' wirklich löschen?') +
	                  '\n\nDiese Aktion kann nicht rückgängig gemacht werden.';
	return confirm(fullMessage);
}

/**
 * Show alert dialog
 *
 * @param {string} message Message to display
 * @param {string} title Optional dialog title
 */
function show_alert(message, title) {
	if(title) {
		message = title + '\n\n' + message;
	}
	alert(message);
}

/**
 * Show info message (non-blocking)
 *
 * @param {string} message Message to display
 * @param {number} duration Duration in milliseconds (default: 3000)
 */
function show_info_message(message, duration) {
	duration = duration || 3000;
	var msgDiv = document.createElement('div');
	msgDiv.style.position = 'fixed';
	msgDiv.style.top = '20px';
	msgDiv.style.right = '20px';
	msgDiv.style.backgroundColor = '#4CAF50';
	msgDiv.style.color = 'white';
	msgDiv.style.padding = '15px';
	msgDiv.style.borderRadius = '4px';
	msgDiv.style.zIndex = '10000';
	msgDiv.style.maxWidth = '300px';
	msgDiv.textContent = message;

	document.body.appendChild(msgDiv);

	setTimeout(function() {
		msgDiv.style.opacity = '0';
		msgDiv.style.transition = 'opacity 0.3s';
		setTimeout(function() {
			document.body.removeChild(msgDiv);
		}, 300);
	}, duration);
}

/**
 * Show error message
 *
 * @param {string} message Error message
 * @param {number} duration Duration in milliseconds (default: 5000)
 */
function show_error_message(message, duration) {
	duration = duration || 5000;
	var msgDiv = document.createElement('div');
	msgDiv.style.position = 'fixed';
	msgDiv.style.top = '20px';
	msgDiv.style.right = '20px';
	msgDiv.style.backgroundColor = '#f44336';
	msgDiv.style.color = 'white';
	msgDiv.style.padding = '15px';
	msgDiv.style.borderRadius = '4px';
	msgDiv.style.zIndex = '10000';
	msgDiv.style.maxWidth = '300px';
	msgDiv.textContent = message;

	document.body.appendChild(msgDiv);

	setTimeout(function() {
		msgDiv.style.opacity = '0';
		msgDiv.style.transition = 'opacity 0.3s';
		setTimeout(function() {
			document.body.removeChild(msgDiv);
		}, 300);
	}, duration);
}

/**
 * Close dialog/modal
 *
 * @param {string} dialogId Dialog element ID
 */
function close_dialog(dialogId) {
	var dialog = document.getElementById(dialogId);
	if(dialog) {
		dialog.style.display = 'none';
	}
}

/**
 * Open dialog/modal
 *
 * @param {string} dialogId Dialog element ID
 */
function open_dialog(dialogId) {
	var dialog = document.getElementById(dialogId);
	if(dialog) {
		dialog.style.display = 'block';
	}
}
