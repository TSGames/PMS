var dropZone;

function addDrop() {
    dropZone = document.getElementById('drop_zone');
    
    if (dropZone && window.File && window.FileReader && window.FileList && window.Blob) {
        dropZone.addEventListener('dragover', handleDragOver, false);
        dropZone.addEventListener('drop', handleFileSelect, false);
    } else if (dropZone) {
        dropZone.innerHTML = "Das Drag'n'Drop von Bild-Dateien ist leider<br> nur in Firefox und Google Chrome möglich.";
    }
}

document.addEventListener("DOMContentLoaded", addDrop, false);

var reader = new FileReader();
var file_name = "";

function handleFileSelect(evt) {
    evt.stopPropagation();
    evt.preventDefault();

    var files = evt.dataTransfer.files;
    var output = [];
    
    if(files.length > 1){
        alert("Es kann immer nur eine Datei hinzugefügt werden.");
        return;
    }
    
    for (var i = 0, f; f = files[i]; i++) {
        var blob = f;
        file_name = f.name;
        var end = file_name.split(".");
        end = end[end.length-1].toLowerCase();
        
        if(end !== "jpg" && end !== "jpeg" && end !== "png" && end !== "gif"){
            alert("Das Dateiformat der Datei '" + file_name + "' wird nicht unterstützt.");
            return;
        }
        
        reader.readAsBinaryString(blob); 
        output.push('<li><strong>', f.name, '</strong> (', f.type || 'n/a', ') - ',
                    f.size, ' bytes, last modified: ',
                    f.lastModifiedDate.toLocaleDateString(), '</li>');
    }
    
    document.getElementById('list').innerHTML = '<ul>' + output.join('') + '</ul>';
}

reader.onloadend = function(evt) {
    if (evt.target.readyState == FileReader.DONE) { // DONE == 2
        post_to_url("admin.php", evt.target.result, "post");
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
    
    dropZone.innerHTML = "Bitte Warten...<br>Upload der Datei '" + file_name + "'";
    
    add_image("dragdrop");
}

function handleDragOver(evt) {
    evt.stopPropagation();
    evt.preventDefault();
}