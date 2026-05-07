/**
 * Interactive Image Cropping Modal
 * Handles client-side image display, cropping UI, and AJAX submission
 */

(function() {
  'use strict';

  // Output size limits (longest side in pixels)
  const SIZE_PRESETS = { s: 200, m: 700, l: 1600 };

  const state = {
    modal: null,
    canvas: null,
    ctx: null,
    image: null,
    imageFile: null,
    blob: null,
    itemId: '',
    outputSize: 'l',   // 's' | 'm' | 'l'
    crop: { x: 0, y: 0, w: 100, h: 100 },
    canvasDims: { w: 600, h: 600 },
    imageDims: { w: 0, h: 0 },
    dragging: false,
    dragHandle: null,
    dragOffset: { x: 0, y: 0 },
    isProcessing: false,
    _docMove: null,
    _docUp: null
  };

  // ─── Public API ────────────────────────────────────────────────────────────

  window.showCropModal = function(img, filename, blob, itemId) {
    state.image      = img;
    state.imageFile  = filename;
    state.blob       = blob;
    state.itemId     = (itemId !== undefined && itemId !== null && itemId !== '') ? itemId : '';
    state.imageDims.w = img.width;
    state.imageDims.h = img.height;

    createModal();
    initializeCanvas();
    attachCanvasHandlers();
    setInitialCropArea();
    drawCanvas();
    showModal();
  };

  window.closeCropModal = function() {
    detachDocHandlers();
    if (state.modal) state.modal.remove();
    document.body.style.overflow = '';
    resetState();
  };

  // ─── Modal DOM ─────────────────────────────────────────────────────────────

  function createModal() {
    const overlay = document.createElement('div');
    overlay.className = 'crop-modal-overlay';
    overlay.id = 'crop-modal-overlay';
    overlay.onclick = (e) => { if (e.target === overlay) window.closeCropModal(); };

    const container = document.createElement('div');
    container.className = 'crop-modal-container';

    // Header
    const header = document.createElement('div');
    header.className = 'crop-modal-header';
    header.textContent = 'Bild zuschneiden';

    // Body
    const body = document.createElement('div');
    body.className = 'crop-modal-body';

    const canvasContainer = document.createElement('div');
    canvasContainer.className = 'crop-canvas-container';

    const canvas = document.createElement('canvas');
    canvas.id = 'crop-canvas';
    canvas.className = 'crop-canvas';
    canvasContainer.appendChild(canvas);
    state.canvas = canvas;
    state.ctx    = canvas.getContext('2d');

    // Size preset buttons
    const presets = document.createElement('div');
    presets.className = 'crop-size-presets';
    presets.innerHTML =
      '<span>Ausgabegröße:</span>' +
      '<button type="button" data-preset="s">Klein (200px)</button>' +
      '<button type="button" data-preset="m">Mittel (700px)</button>' +
      '<button type="button" data-preset="l" class="active">Groß (1600px)</button>';
    presets.addEventListener('click', function(e) {
      const btn = e.target.closest('button[data-preset]');
      if (btn) applyPreset(btn.dataset.preset);
    });

    // Info bar
    const info = document.createElement('div');
    info.className = 'crop-info';
    info.id = 'crop-info';
    info.innerHTML = '<div class="crop-dimensions">0 × 0 px</div>' +
                     '<div class="crop-aspect-ratio">Seitenverhältnis: –</div>';

    body.appendChild(canvasContainer);
    body.appendChild(presets);
    body.appendChild(info);

    // Footer
    const footer = document.createElement('div');
    footer.className = 'crop-modal-footer';

    const btnCancel = document.createElement('button');
    btnCancel.type = 'button';
    btnCancel.className = 'btn-cancel';
    btnCancel.textContent = 'Abbrechen';
    btnCancel.onclick = window.closeCropModal;

    const btnCrop = document.createElement('button');
    btnCrop.type = 'button';
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

    state.modal = overlay;
    document.body.appendChild(overlay);
  }

  // ─── Canvas init ───────────────────────────────────────────────────────────

  function initializeCanvas() {
    const img = state.image;
    const maxDim = 580;
    let cw = img.width, ch = img.height;

    if (cw > maxDim || ch > maxDim) {
      if (cw / ch > 1) { cw = maxDim; ch = Math.round(maxDim * img.height / img.width); }
      else             { ch = maxDim; cw = Math.round(maxDim * img.width  / img.height); }
    }

    state.canvasDims.w = cw;
    state.canvasDims.h = ch;
    state.canvas.width  = cw;
    state.canvas.height = ch;
  }

  function setInitialCropArea() {
    const cw = state.canvasDims.w, ch = state.canvasDims.h;
    const s  = Math.min(cw, ch) * 0.8;
    state.crop.w = s;
    state.crop.h = s;
    state.crop.x = (cw - s) / 2;
    state.crop.y = (ch - s) / 2;
  }

  // ─── Size presets ──────────────────────────────────────────────────────────

  function applyPreset(size) {
    state.outputSize = size;
    // Update button active styling
    const presets = state.modal && state.modal.querySelectorAll('.crop-size-presets button');
    if (presets) presets.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.preset === size);
    });
    updateInfoDisplay();
  }

  /** Given crop pixel dimensions, returns final {w,h} after rescale */
  function finalDimensions(cropW, cropH) {
    const maxSide = SIZE_PRESETS[state.outputSize];
    if (!maxSide) return { w: cropW, h: cropH }; // 'l' = no rescale

    const longest = Math.max(cropW, cropH);
    if (longest <= maxSide) return { w: cropW, h: cropH }; // already small enough

    const scale = maxSide / longest;
    return {
      w: Math.round(cropW * scale),
      h: Math.round(cropH * scale)
    };
  }

  // ─── Drawing ───────────────────────────────────────────────────────────────

  function drawCanvas() {
    const ctx = state.ctx, canvas = state.canvas, img = state.image;
    const c = state.crop;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    // Dark overlay outside crop
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.fillRect(0,         0,         canvas.width, c.y);
    ctx.fillRect(0,         c.y + c.h, canvas.width, canvas.height - c.y - c.h);
    ctx.fillRect(0,         c.y,       c.x,          c.h);
    ctx.fillRect(c.x + c.w, c.y,       canvas.width - c.x - c.w, c.h);

    // Crop border
    ctx.strokeStyle = '#1976d2';
    ctx.lineWidth   = 2;
    ctx.strokeRect(c.x, c.y, c.w, c.h);

    // Rule-of-thirds grid lines
    ctx.strokeStyle = 'rgba(255,255,255,0.35)';
    ctx.lineWidth   = 1;
    for (let i = 1; i <= 2; i++) {
      const lx = c.x + c.w * i / 3, ly = c.y + c.h * i / 3;
      ctx.beginPath(); ctx.moveTo(lx, c.y);       ctx.lineTo(lx, c.y + c.h); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(c.x, ly);       ctx.lineTo(c.x + c.w, ly); ctx.stroke();
    }

    drawHandles();
    updateInfoDisplay();
  }

  function drawHandles() {
    const ctx = state.ctx;
    const hs  = getHandlePositions();
    ctx.fillStyle   = '#1976d2';
    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth   = 2;
    Object.values(hs).forEach(h => {
      ctx.fillRect(h.x - 7, h.y - 7, 14, 14);
      ctx.strokeRect(h.x - 7, h.y - 7, 14, 14);
    });
  }

  function getHandlePositions() {
    const c = state.crop;
    return {
      nw: { x: c.x,           y: c.y           },
      ne: { x: c.x + c.w,     y: c.y           },
      sw: { x: c.x,           y: c.y + c.h     },
      se: { x: c.x + c.w,     y: c.y + c.h     },
      n:  { x: c.x + c.w / 2, y: c.y           },
      s:  { x: c.x + c.w / 2, y: c.y + c.h     },
      w:  { x: c.x,           y: c.y + c.h / 2 },
      e:  { x: c.x + c.w,     y: c.y + c.h / 2 }
    };
  }

  function updateInfoDisplay() {
    const el = document.getElementById('crop-info');
    if (!el) return;
    const scaleX = state.imageDims.w / state.canvasDims.w;
    const scaleY = state.imageDims.h / state.canvasDims.h;
    const cropW = Math.round(state.crop.w * scaleX);
    const cropH = Math.round(state.crop.h * scaleY);
    const out   = finalDimensions(cropW, cropH);
    const ratio = cropH > 0 ? (cropW / cropH).toFixed(2) : '–';
    const rescaled = (out.w !== cropW || out.h !== cropH)
      ? ' → <strong>' + out.w + ' × ' + out.h + ' px</strong>' : '';
    el.innerHTML =
      '<div class="crop-dimensions">Ausschnitt: ' + cropW + ' × ' + cropH + ' px' + rescaled + '</div>' +
      '<div class="crop-aspect-ratio">Seitenverhältnis: ' + ratio + '</div>';
  }

  // ─── Interaction ───────────────────────────────────────────────────────────

  function attachCanvasHandlers() {
    const c = state.canvas;
    c.addEventListener('mousedown',   onCanvasDown);
    c.addEventListener('mousemove',   onCanvasHover);
    c.addEventListener('touchstart',  onCanvasDown,  { passive: false });
    c.addEventListener('touchmove',   onTouchMove,   { passive: false });
    c.addEventListener('touchend',    onTouchEnd,    { passive: false });
    c.addEventListener('touchcancel', onTouchEnd,    { passive: false });
  }

  function detachDocHandlers() {
    if (state._docMove) document.removeEventListener('mousemove', state._docMove);
    if (state._docUp)   document.removeEventListener('mouseup',   state._docUp);
    state._docMove = null;
    state._docUp   = null;
  }

  /** Returns canvas-space coordinates, accounting for CSS scaling */
  function canvasCoords(clientX, clientY) {
    const rect   = state.canvas.getBoundingClientRect();
    const scaleX = state.canvas.width  / rect.width;
    const scaleY = state.canvas.height / rect.height;
    return {
      x: (clientX - rect.left) * scaleX,
      y: (clientY - rect.top)  * scaleY
    };
  }

  function clientFromEvent(e) {
    if (e.touches && e.touches.length > 0) {
      return { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
    }
    return { clientX: e.clientX, clientY: e.clientY };
  }

  function getHandleAt(x, y) {
    const hs  = getHandlePositions();
    // Scale threshold: 16 canvas-px, but also consider display scale
    const rect = state.canvas.getBoundingClientRect();
    const scale = state.canvas.width / rect.width;
    const t = Math.max(14, 16 * scale);
    for (const [name, pos] of Object.entries(hs)) {
      if (Math.abs(x - pos.x) < t && Math.abs(y - pos.y) < t) return name;
    }
    return null;
  }

  function isInsideCrop(x, y) {
    const c = state.crop;
    return x > c.x && x < c.x + c.w && y > c.y && y < c.y + c.h;
  }

  function cursorFor(handle) {
    return { nw:'nwse-resize', se:'nwse-resize',
             ne:'nesw-resize', sw:'nesw-resize',
             n:'ns-resize',    s:'ns-resize',
             w:'ew-resize',    e:'ew-resize' }[handle] || 'default';
  }

  // Canvas hover — update cursor only, no dragging
  function onCanvasHover(e) {
    if (state.dragging) return;
    const { clientX, clientY } = clientFromEvent(e);
    const pos = canvasCoords(clientX, clientY);
    const handle = getHandleAt(pos.x, pos.y);
    if (handle)                       state.canvas.style.cursor = cursorFor(handle);
    else if (isInsideCrop(pos.x, pos.y)) state.canvas.style.cursor = 'move';
    else                               state.canvas.style.cursor = 'crosshair';
  }

  function onCanvasDown(e) {
    e.preventDefault();
    const { clientX, clientY } = clientFromEvent(e);
    const pos    = canvasCoords(clientX, clientY);
    const handle = getHandleAt(pos.x, pos.y);

    if (handle) {
      startDrag('handle', handle, pos);
    } else if (isInsideCrop(pos.x, pos.y)) {
      startDrag('move', null, pos);
    } else {
      startDrag('draw', null, pos);
    }

    // Attach document-level mouse handlers so dragging outside canvas works
    if (!e.touches) {
      state._docMove = function(ev) {
        const p = canvasCoords(ev.clientX, ev.clientY);
        performDrag(p.x, p.y);
      };
      state._docUp = function() {
        finishDrag();
        detachDocHandlers();
      };
      document.addEventListener('mousemove', state._docMove);
      document.addEventListener('mouseup',   state._docUp);
    }
  }

  function onTouchMove(e) {
    e.preventDefault();
    const { clientX, clientY } = clientFromEvent(e);
    const pos = canvasCoords(clientX, clientY);
    performDrag(pos.x, pos.y);
  }

  function onTouchEnd(e) {
    e.preventDefault();
    finishDrag();
  }

  function startDrag(mode, handle, pos) {
    state.dragging = true;

    if (mode === 'handle') {
      state.dragHandle = handle;
      state.canvas.style.cursor = cursorFor(handle);

    } else if (mode === 'move') {
      state.dragHandle  = 'move';
      state.dragOffset.x = pos.x - state.crop.x;
      state.dragOffset.y = pos.y - state.crop.y;
      state.canvas.style.cursor = 'move';

    } else { // draw
      state.dragHandle  = 'draw';
      state.dragOffset.x = pos.x;
      state.dragOffset.y = pos.y;
      state.crop.x = pos.x; state.crop.y = pos.y;
      state.crop.w = 1;     state.crop.h = 1;
      state.canvas.style.cursor = 'crosshair';
    }
  }

  function performDrag(x, y) {
    if (!state.dragging) return;
    updateCrop(x, y);
    drawCanvas();
  }

  function finishDrag() {
    if (!state.dragging) return;
    const c = state.crop;
    // Normalise draw direction (dragged up/left produces negative w/h)
    if (c.w < 0) { c.x += c.w; c.w = -c.w; }
    if (c.h < 0) { c.y += c.h; c.h = -c.h; }
    if (c.w < 10) c.w = 10;
    if (c.h < 10) c.h = 10;
    state.dragging   = false;
    state.dragHandle = null;
    state.canvas.style.cursor = 'crosshair';
    drawCanvas();
  }

  function updateCrop(x, y) {
    const c    = state.crop;
    const min  = 10;
    const maxW = state.canvasDims.w;
    const maxH = state.canvasDims.h;

    // Capture fixed edges before any mutation
    const rx = c.x + c.w;
    const by = c.y + c.h;

    switch (state.dragHandle) {

      case 'move':
        c.x = Math.max(0, Math.min(x - state.dragOffset.x, maxW - c.w));
        c.y = Math.max(0, Math.min(y - state.dragOffset.y, maxH - c.h));
        return; // no clamp needed — already clamped

      case 'draw': {
        const sx = state.dragOffset.x, sy = state.dragOffset.y;
        c.x = Math.max(0, Math.min(x, sx));
        c.y = Math.max(0, Math.min(y, sy));
        c.w = Math.min(Math.abs(x - sx), maxW - c.x);
        c.h = Math.min(Math.abs(y - sy), maxH - c.y);
        return;
      }

      // Corners
      case 'nw': c.x = Math.min(x, rx-min); c.w = rx - c.x;
                 c.y = Math.min(y, by-min); c.h = by - c.y; break;
      case 'ne': c.w = Math.max(min, x - c.x);
                 c.y = Math.min(y, by-min); c.h = by - c.y; break;
      case 'sw': c.x = Math.min(x, rx-min); c.w = rx - c.x;
                 c.h = Math.max(min, y - c.y);              break;
      case 'se': c.w = Math.max(min, x - c.x);
                 c.h = Math.max(min, y - c.y);              break;

      // Edges
      case 'n':  c.y = Math.min(y, by-min); c.h = by - c.y; break;
      case 's':  c.h = Math.max(min, y - c.y);               break;
      case 'w':  c.x = Math.min(x, rx-min); c.w = rx - c.x; break;
      case 'e':  c.w = Math.max(min, x - c.x);               break;
    }

    // Clamp to canvas
    c.x = Math.max(0, Math.min(c.x, maxW - min));
    c.y = Math.max(0, Math.min(c.y, maxH - min));
    c.w = Math.max(min, Math.min(c.w, maxW - c.x));
    c.h = Math.max(min, Math.min(c.h, maxH - c.y));
  }

  // ─── Crop & upload ─────────────────────────────────────────────────────────

  function scaleCropToOriginal() {
    const sx = state.imageDims.w / state.canvasDims.w;
    const sy = state.imageDims.h / state.canvasDims.h;
    return {
      x: Math.round(state.crop.x * sx),
      y: Math.round(state.crop.y * sy),
      w: Math.round(state.crop.w * sx),
      h: Math.round(state.crop.h * sy)
    };
  }

  function processCrop() {
    const btn = document.getElementById('btn-crop');
    if (state.isProcessing || !btn) return;
    if (state.crop.w < 10 || state.crop.h < 10) {
      alert('Zuschnittfläche muss mindestens 10×10 Pixel groß sein');
      return;
    }

    state.isProcessing = true;
    btn.disabled = true;
    btn.textContent = 'Wird verarbeitet...';

    const orig     = scaleCropToOriginal();
    const outDim   = finalDimensions(orig.w, orig.h);
    const formData = new FormData();
    formData.append('action',        'crop_image_ajax');
    formData.append('image_file',    state.imageFile);
    formData.append('image_data',    state.blob, state.imageFile);
    formData.append('crop_x',        orig.x);
    formData.append('crop_y',        orig.y);
    formData.append('crop_w',        orig.w);
    formData.append('crop_h',        orig.h);
    formData.append('resize_width',  outDim.w);
    formData.append('resize_height', outDim.h);

    fetch('admin.php', { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(text => {
        let data;
        try { data = JSON.parse(text); }
        catch(e) {
          console.error('Response:', text.substring(0, 500));
          throw new Error('Ungültige Server-Antwort: ' + e.message);
        }
        if (!data.success) throw new Error(data.error || 'Unbekannter Fehler');

        const imageUrl = 'images/uploads/' + (data.filename || state.imageFile);
        window.closeCropModal();

        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
          tinyMCE.activeEditor.execCommand('mceInsertContent', false,
            '<img src="' + imageUrl + '" alt="">');
        }
      })
      .catch(err => {
        console.error('Crop error:', err);
        alert('Fehler: ' + err.message);
      })
      .finally(() => {
        state.isProcessing = false;
        if (btn) { btn.disabled = false; btn.textContent = 'Zuschneiden'; }
      });
  }

  // ─── Modal show / hide ─────────────────────────────────────────────────────

  function showModal() {
    if (state.modal) {
      state.modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  function resetState() {
    Object.assign(state, {
      modal: null, canvas: null, ctx: null,
      image: null, imageFile: null, blob: null, itemId: '',
      dragging: false, dragHandle: null,
      dragOffset: { x: 0, y: 0 },
      isProcessing: false,
      _docMove: null, _docUp: null
    });
  }

})();
