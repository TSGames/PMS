/**
 * Interactive Image Cropping Modal
 * Handles client-side image display, cropping UI, and AJAX submission
 */

(function() {
  'use strict';

  // Global state
  const state = {
    modal: null,
    canvas: null,
    ctx: null,
    image: null,
    imageFile: null,
    crop: {
      x: 0,
      y: 0,
      w: 100,
      h: 100
    },
    canvasDims: { w: 600, h: 600 },
    imageDims: { w: 0, h: 0 },
    dragging: false,
    dragHandle: null,
    isProcessing: false
  };

  /**
   * Initialize and show crop modal
   * @param {Image} img - Loaded image element
   * @param {string} filename - Original filename
   * @param {Blob} blob - Original file blob
   */
  window.showCropModal = function(img, filename, blob) {
    state.image = img;
    state.imageFile = filename;
    state.imageDims.w = img.width;
    state.imageDims.h = img.height;

    createModal();
    initializeCanvas();
    initializeHandlers();
    setInitialCropArea();
    drawCanvas();
    showModal();
  };

  /**
   * Create modal DOM structure
   */
  function createModal() {
    const overlay = document.createElement('div');
    overlay.className = 'crop-modal-overlay';
    overlay.id = 'crop-modal-overlay';

    const container = document.createElement('div');
    container.className = 'crop-modal-container';

    const header = document.createElement('div');
    header.className = 'crop-modal-header';
    header.textContent = 'Bild zuschneiden';

    const body = document.createElement('div');
    body.className = 'crop-modal-body';

    const canvasContainer = document.createElement('div');
    canvasContainer.className = 'crop-canvas-container';

    const canvas = document.createElement('canvas');
    canvas.id = 'crop-canvas';
    canvas.className = 'crop-canvas';
    canvasContainer.appendChild(canvas);
    state.canvas = canvas;
    state.ctx = canvas.getContext('2d');

    const info = document.createElement('div');
    info.className = 'crop-info';
    info.id = 'crop-info';
    info.innerHTML = '<div class="crop-dimensions">0 × 0 px</div><div class="crop-aspect-ratio">Seitenverhältnis: 1:1</div>';

    body.appendChild(canvasContainer);
    body.appendChild(info);

    const footer = document.createElement('div');
    footer.className = 'crop-modal-footer';

    const btnCancel = document.createElement('button');
    btnCancel.className = 'btn-cancel';
    btnCancel.textContent = 'Abbrechen';
    btnCancel.onclick = closeCropModal;

    const btnCrop = document.createElement('button');
    btnCrop.className = 'btn-crop';
    btnCrop.textContent = 'Zuschneiden';
    btnCrop.id = 'btn-crop';
    btnCrop.onclick = processCrop;

    footer.appendChild(btnCancel);
    footer.appendChild(btnCrop);

    container.appendChild(header);
    container.appendChild(body);
    container.appendChild(footer);

    overlay.appendChild(container);
    overlay.onclick = (e) => {
      if (e.target === overlay) closeCropModal();
    };

    state.modal = overlay;
    document.body.appendChild(overlay);
  };

  /**
   * Initialize canvas dimensions and context
   */
  function initializeCanvas() {
    const img = state.image;
    const maxDim = 600;

    // Calculate canvas dimensions (preserve aspect ratio)
    let canvasW = img.width;
    let canvasH = img.height;

    if (canvasW > maxDim || canvasH > maxDim) {
      const ratio = img.width / img.height;
      if (ratio > 1) {
        canvasW = maxDim;
        canvasH = maxDim / ratio;
      } else {
        canvasH = maxDim;
        canvasW = maxDim * ratio;
      }
    }

    state.canvasDims.w = canvasW;
    state.canvasDims.h = canvasH;

    state.canvas.width = canvasW;
    state.canvas.height = canvasH;
  }

  /**
   * Initialize event handlers for canvas interaction
   */
  function initializeHandlers() {
    const canvas = state.canvas;

    // Mouse events
    canvas.addEventListener('mousedown', handlePointerDown);
    canvas.addEventListener('mousemove', handlePointerMove);
    canvas.addEventListener('mouseup', handlePointerUp);
    canvas.addEventListener('mouseleave', handlePointerUp);

    // Touch events
    canvas.addEventListener('touchstart', handlePointerDown);
    canvas.addEventListener('touchmove', handlePointerMove);
    canvas.addEventListener('touchend', handlePointerUp);
    canvas.addEventListener('touchcancel', handlePointerUp);

    // Prevent default drag behavior
    canvas.addEventListener('dragstart', (e) => e.preventDefault());
  }

  /**
   * Set initial crop area (centered, 80% of image)
   */
  function setInitialCropArea() {
    const cw = state.canvasDims.w;
    const ch = state.canvasDims.h;

    const size = Math.min(cw, ch) * 0.8;

    state.crop.w = size;
    state.crop.h = size;
    state.crop.x = (cw - size) / 2;
    state.crop.y = (ch - size) / 2;
  }

  /**
   * Draw canvas with image and crop overlay
   */
  function drawCanvas() {
    const ctx = state.ctx;
    const canvas = state.canvas;
    const img = state.image;

    // Clear canvas
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw image
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    // Draw semi-transparent overlay outside crop area
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';

    // Top
    ctx.fillRect(0, 0, canvas.width, state.crop.y);
    // Bottom
    ctx.fillRect(0, state.crop.y + state.crop.h, canvas.width, canvas.height - (state.crop.y + state.crop.h));
    // Left
    ctx.fillRect(0, state.crop.y, state.crop.x, state.crop.h);
    // Right
    ctx.fillRect(state.crop.x + state.crop.w, state.crop.y, canvas.width - (state.crop.x + state.crop.w), state.crop.h);

    // Draw crop area border
    ctx.strokeStyle = '#1976d2';
    ctx.lineWidth = 2;
    ctx.strokeRect(state.crop.x, state.crop.y, state.crop.w, state.crop.h);

    // Draw handles
    drawHandles();

    // Update info display
    updateInfoDisplay();
  }

  /**
   * Draw interactive resize handles
   */
  function drawHandles() {
    const ctx = state.ctx;
    const handles = getHandlePositions();

    ctx.fillStyle = '#1976d2';
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 2;

    Object.values(handles).forEach(h => {
      ctx.fillRect(h.x - 8, h.y - 8, 16, 16);
      ctx.strokeRect(h.x - 8, h.y - 8, 16, 16);
    });
  }

  /**
   * Get positions of all resize handles
   */
  function getHandlePositions() {
    const c = state.crop;
    return {
      'nw': { x: c.x, y: c.y },
      'ne': { x: c.x + c.w, y: c.y },
      'sw': { x: c.x, y: c.y + c.h },
      'se': { x: c.x + c.w, y: c.y + c.h },
      'n': { x: c.x + c.w / 2, y: c.y },
      's': { x: c.x + c.w / 2, y: c.y + c.h },
      'w': { x: c.x, y: c.y + c.h / 2 },
      'e': { x: c.x + c.w, y: c.y + c.h / 2 }
    };
  }

  /**
   * Determine which handle is being dragged
   */
  function getHandleAtPoint(x, y) {
    const handles = getHandlePositions();
    const threshold = 12;

    for (const [name, pos] of Object.entries(handles)) {
      if (Math.abs(x - pos.x) < threshold && Math.abs(y - pos.y) < threshold) {
        return name;
      }
    }
    return null;
  }

  /**
   * Get pointer coordinates (handles mouse and touch)
   */
  function getPointerCoords(e) {
    if (e.touches && e.touches.length > 0) {
      const touch = e.touches[0];
      const rect = state.canvas.getBoundingClientRect();
      return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top
      };
    } else {
      const rect = state.canvas.getBoundingClientRect();
      return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
      };
    }
  }

  /**
   * Handle pointer down (start drag)
   */
  function handlePointerDown(e) {
    e.preventDefault();
    const pos = getPointerCoords(e);
    const handle = getHandleAtPoint(pos.x, pos.y);

    if (handle) {
      state.dragging = true;
      state.dragHandle = handle;
      state.canvas.style.cursor = getCursorForHandle(handle);
    }
  }

  /**
   * Handle pointer move (drag)
   */
  function handlePointerMove(e) {
    e.preventDefault();
    const pos = getPointerCoords(e);

    if (!state.dragging) {
      const handle = getHandleAtPoint(pos.x, pos.y);
      state.canvas.style.cursor = handle ? getCursorForHandle(handle) : 'crosshair';
      return;
    }

    updateCropArea(pos.x, pos.y, state.dragHandle);
    drawCanvas();
  }

  /**
   * Handle pointer up (end drag)
   */
  function handlePointerUp(e) {
    if (state.dragging) {
      state.dragging = false;
      state.dragHandle = null;
      state.canvas.style.cursor = 'crosshair';
    }
  }

  /**
   * Get cursor style for handle type
   */
  function getCursorForHandle(handle) {
    const cursors = {
      'nw': 'nwse-resize',
      'ne': 'nesw-resize',
      'sw': 'nesw-resize',
      'se': 'nwse-resize',
      'n': 'ns-resize',
      's': 'ns-resize',
      'w': 'ew-resize',
      'e': 'ew-resize'
    };
    return cursors[handle] || 'pointer';
  }

  /**
   * Update crop area based on handle drag
   */
  function updateCropArea(x, y, handle) {
    const c = state.crop;
    const minSize = 30;
    const maxW = state.canvasDims.w;
    const maxH = state.canvasDims.h;

    switch (handle) {
      case 'nw':
        if (x > 0 && y > 0 && x < c.x + c.w - minSize && y < c.y + c.h - minSize) {
          c.w += c.x - x;
          c.h += c.y - y;
          c.x = x;
          c.y = y;
        }
        break;
      case 'ne':
        if (x < maxW && y > 0 && x > c.x + minSize && y < c.y + c.h - minSize) {
          c.w = x - c.x;
          c.h += c.y - y;
          c.y = y;
        }
        break;
      case 'sw':
        if (x > 0 && y < maxH && x < c.x + c.w - minSize && y > c.y + minSize) {
          c.w += c.x - x;
          c.h = y - c.y;
          c.x = x;
        }
        break;
      case 'se':
        if (x < maxW && y < maxH && x > c.x + minSize && y > c.y + minSize) {
          c.w = x - c.x;
          c.h = y - c.y;
        }
        break;
      case 'n':
        if (y > 0 && y < c.y + c.h - minSize) {
          c.h += c.y - y;
          c.y = y;
        }
        break;
      case 's':
        if (y < maxH && y > c.y + minSize) {
          c.h = y - c.y;
        }
        break;
      case 'w':
        if (x > 0 && x < c.x + c.w - minSize) {
          c.w += c.x - x;
          c.x = x;
        }
        break;
      case 'e':
        if (x < maxW && x > c.x + minSize) {
          c.w = x - c.x;
        }
        break;
    }

    // Clamp values
    c.x = Math.max(0, Math.min(c.x, maxW - minSize));
    c.y = Math.max(0, Math.min(c.y, maxH - minSize));
    c.w = Math.max(minSize, Math.min(c.w, maxW - c.x));
    c.h = Math.max(minSize, Math.min(c.h, maxH - c.y));
  }

  /**
   * Update dimension and aspect ratio display
   */
  function updateInfoDisplay() {
    const infoEl = document.getElementById('crop-info');
    if (!infoEl) return;

    const crop = state.crop;
    const imgRatio = (state.imageDims.w / state.imageDims.h).toFixed(2);
    const cropRatio = (crop.w / crop.h).toFixed(2);

    infoEl.innerHTML = `
      <div class="crop-dimensions">${Math.round(crop.w)} × ${Math.round(crop.h)} px</div>
      <div class="crop-aspect-ratio">Seitenverhältnis: ${cropRatio} (Original: ${imgRatio})</div>
    `;
  }

  /**
   * Scale crop coordinates from canvas to original image
   */
  function scaleCropToOriginal() {
    const scaleX = state.imageDims.w / state.canvasDims.w;
    const scaleY = state.imageDims.h / state.canvasDims.h;

    return {
      x: Math.round(state.crop.x * scaleX),
      y: Math.round(state.crop.y * scaleY),
      w: Math.round(state.crop.w * scaleX),
      h: Math.round(state.crop.h * scaleY)
    };
  }

  /**
   * Process crop and send to server
   */
  function processCrop() {
    const btn = document.getElementById('btn-crop');
    if (state.isProcessing || !btn) return;

    // Validate crop dimensions
    if (state.crop.w < 10 || state.crop.h < 10) {
      alert('Zuschnittfläche muss mindestens 10×10 Pixel groß sein');
      return;
    }

    state.isProcessing = true;
    btn.disabled = true;
    btn.textContent = 'Wird verarbeitet...';

    // Scale crop to original image dimensions
    const cropOrig = scaleCropToOriginal();

    // Send AJAX request
    const formData = new FormData();
    formData.append('action', 'crop_image_ajax');
    formData.append('image_file', state.imageFile);
    formData.append('crop_x', cropOrig.x);
    formData.append('crop_y', cropOrig.y);
    formData.append('crop_w', cropOrig.w);
    formData.append('crop_h', cropOrig.h);

    fetch('admin.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update hidden form fields with image info
        const itemForm = document.querySelector('form[name="pms_form"]') || document.querySelector('form');
        if (itemForm) {
          const imgWidthInput = itemForm.querySelector('input[name="width_img"]');
          const imgHeightInput = itemForm.querySelector('input[name="height_img"]');
          const imgNameInput = itemForm.querySelector('input[name="image_add"]');

          if (imgWidthInput) imgWidthInput.value = cropOrig.w;
          if (imgHeightInput) imgHeightInput.value = cropOrig.h;
          if (imgNameInput) imgNameInput.value = state.imageFile;
        }

        // Update image preview if it exists
        updateImagePreview();

        // Close modal
        closeCropModal();

        // Show success message
        const infoBox = document.getElementById('crop-info');
        if (infoBox) {
          infoBox.textContent = 'Bild erfolgreich zugeschnitten!';
        }
      } else {
        alert('Fehler beim Zuschneiden: ' + (data.error || 'Unbekannter Fehler'));
      }
    })
    .catch(error => {
      console.error('Crop error:', error);
      alert('Fehler beim Zuschneiden des Bildes');
    })
    .finally(() => {
      state.isProcessing = false;
      btn.disabled = false;
      btn.textContent = 'Zuschneiden';
    });
  }

  /**
   * Update image preview in form if present
   */
  function updateImagePreview() {
    // Look for existing image preview
    const previewImg = document.querySelector('img[alt*="Preview"]') ||
                       document.querySelector('.crop-preview-img') ||
                       document.querySelector('img.item-preview');

    if (previewImg && state.image) {
      previewImg.src = state.image.src;
    }
  }

  /**
   * Show modal
   */
  function showModal() {
    if (state.modal) {
      state.modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  /**
   * Close and destroy modal
   */
  window.closeCropModal = function() {
    if (state.modal) {
      state.modal.remove();
    }
    document.body.style.overflow = '';
    resetState();
  };

  /**
   * Reset global state
   */
  function resetState() {
    state.modal = null;
    state.canvas = null;
    state.ctx = null;
    state.image = null;
    state.imageFile = null;
    state.dragging = false;
    state.dragHandle = null;
    state.isProcessing = false;
  }

})();
