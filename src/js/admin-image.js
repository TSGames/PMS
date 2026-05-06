/**
 * Admin Image JavaScript
 * Handles image preview, manipulation, and drag-drop interactions
 */

/**
 * Display image preview
 *
 * @param {string} imageId Image element ID
 * @param {string} imageUrl Image source URL
 * @param {number} maxWidth Maximum display width (optional)
 * @param {number} maxHeight Maximum display height (optional)
 */
function show_image_preview(imageId, imageUrl, maxWidth, maxHeight) {
	var imgElement = document.getElementById(imageId);
	if(!imgElement) {
		var container = document.querySelector('[data-image-container="'+imageId+'"]');
		if(container) {
			imgElement = document.createElement('img');
			imgElement.id = imageId;
			container.appendChild(imgElement);
		} else {
			return;
		}
	}

	imgElement.src = imageUrl;
	if(maxWidth) imgElement.style.maxWidth = maxWidth + 'px';
	if(maxHeight) imgElement.style.maxHeight = maxHeight + 'px';
	imgElement.style.display = 'block';
}

/**
 * Hide image preview
 *
 * @param {string} imageId Image element ID
 */
function hide_image_preview(imageId) {
	var imgElement = document.getElementById(imageId);
	if(imgElement) {
		imgElement.style.display = 'none';
	}
}

/**
 * Delete image preview
 *
 * @param {string} imageId Image element ID
 * @param {string} action Admin action name
 * @param {string} imageName Image filename
 */
function delete_image_preview(imageId, action, imageName) {
	if(!confirm('Bild wirklich löschen?')) {
		return;
	}

	window.location.href = 'admin.php?action=' + action + '&delete=' + imageName;
}

/**
 * Show image manipulation controls
 *
 * @param {string} containerId Container element ID
 */
function show_image_controls(containerId) {
	var container = document.getElementById(containerId);
	if(container) {
		container.style.display = 'block';
	}
}

/**
 * Hide image manipulation controls
 *
 * @param {string} containerId Container element ID
 */
function hide_image_controls(containerId) {
	var container = document.getElementById(containerId);
	if(container) {
		container.style.display = 'none';
	}
}

/**
 * Set image dimensions in form fields
 *
 * @param {string} widthFieldName Width input field name
 * @param {string} heightFieldName Height input field name
 * @param {number} width Image width
 * @param {number} height Image height
 */
function set_image_dimensions(widthFieldName, heightFieldName, width, height) {
	var widthField = document.querySelector('[name="'+widthFieldName+'"]');
	var heightField = document.querySelector('[name="'+heightFieldName+'"]');

	if(widthField) widthField.value = width;
	if(heightField) heightField.value = height;
}

/**
 * Get image dimensions from form fields
 *
 * @param {string} widthFieldName Width input field name
 * @param {string} heightFieldName Height input field name
 * @returns {Object} Object with {width, height} properties
 */
function get_image_dimensions(widthFieldName, heightFieldName) {
	var widthField = document.querySelector('[name="'+widthFieldName+'"]');
	var heightField = document.querySelector('[name="'+heightFieldName+'"]');

	return {
		width: widthField ? parseInt(widthField.value) : 0,
		height: heightField ? parseInt(heightField.value) : 0
	};
}

/**
 * Calculate image aspect ratio
 *
 * @param {number} width Image width
 * @param {number} height Image height
 * @returns {number} Aspect ratio (width/height)
 */
function get_image_aspect_ratio(width, height) {
	return height > 0 ? width / height : 0;
}

/**
 * Constrain dimensions while maintaining aspect ratio
 *
 * @param {number} width Current width
 * @param {number} height Current height
 * @param {number} maxWidth Maximum width allowed
 * @param {number} maxHeight Maximum height allowed
 * @returns {Object} Constrained {width, height}
 */
function constrain_image_dimensions(width, height, maxWidth, maxHeight) {
	var ratio = get_image_aspect_ratio(width, height);

	if(width > maxWidth) {
		width = maxWidth;
		height = Math.round(width / ratio);
	}

	if(height > maxHeight) {
		height = maxHeight;
		width = Math.round(height * ratio);
	}

	return {width: width, height: height};
}
