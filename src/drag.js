var dropZone;

function addDrop() {
    dropZone = document.getElementById('drop_zone');

    if (dropZone && window.File && window.FileReader && window.FileList && window.Blob) {
        dropZone.addEventListener('dragover', handleDragOver, false);
        dropZone.addEventListener('drop', handleFileSelect, false);
    } else if (dropZone) {
        dropZone.innerHTML = "Das Drag'n'Drop von Bild-Dateien ist leider<br> nur in Firefox und Google Chrome möglich.";
    }

    // Set up file picker if it exists
    var filePicker = document.getElementById('image_file_picker');
    if (filePicker) {
        filePicker.addEventListener('change', function() {
            if (!this.files.length) return;
            var fakeEvent = {
                dataTransfer: {
                    files: this.files
                },
                stopPropagation: function() {},
                preventDefault: function() {}
            };
            handleFileSelect(fakeEvent);
            this.value = '';
        });
    }
}

document.addEventListener("DOMContentLoaded", addDrop, false);

var reader = new FileReader();
var file_name = "";
var current_blob = null;

function handleFileSelect(evt) {
    evt.stopPropagation();
    evt.preventDefault();

    var files = evt.dataTransfer.files;

    if (files.length > 1) {
        alert("Es kann immer nur eine Datei hinzugefügt werden.");
        return;
    }
    if (files.length === 0) return;

    var f = files[0];
    var end = f.name.split(".").pop().toLowerCase();

    if (end !== "jpg" && end !== "jpeg" && end !== "png" && end !== "gif") {
        alert("Das Dateiformat der Datei '" + f.name + "' wird nicht unterstützt.");
        return;
    }

    file_name = f.name;
    current_blob = f;
    reader.readAsDataURL(f);
}

reader.onloadend = function(evt) {
    if (evt.target.readyState == FileReader.DONE) { // DONE == 2
        // Show crop modal instead of direct upload
        if (window.showCropModal && typeof showCropModal === 'function') {
            var img = new Image();
            img.onload = function() {
                showCropModal(img, file_name, current_blob, typeof item_id !== 'undefined' ? item_id : '');
            };
            img.onerror = function() {
                alert('Fehler beim Laden des Bildes');
            };
            img.src = evt.target.result;
        } else {
            // Fallback: direct upload if modal not available
            post_to_url("admin.php", evt.target.result, "post");
        }
    }
}

function post_to_url(path, data, method) {
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);

    document.getElementById("drag_data").value = escape(data);
    document.getElementById("add_image").value = "true";
    document.getElementById("item").value = item_id;
    document.getElementById("drag_name").value = file_name;

    var statusElement = document.getElementById("image_status");
    if (statusElement) {
        statusElement.textContent = 'Wird verarbeitet...';
    }
    dropZone.innerHTML = "Bitte Warten...<br>Upload der Datei '" + file_name + "'";

    add_image("dragdrop");
}

function handleDragOver(evt) {
    evt.stopPropagation();
    evt.preventDefault();
}