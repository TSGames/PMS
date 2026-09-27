/**
 * Bindet Quill an ein Textfeld.
 *
 * Anders als TinyMCE ersetzt Quill die textarea nicht direkt, sondern
 * bearbeitet ein eigenes Element daneben. Die textarea bleibt verborgen
 * im Formular stehen und wird erst beim Absenden mit dem Inhalt von
 * Quill befüllt - das entspricht dem automatischen "triggerSave" von
 * TinyMCE, das es bei Quill nicht gibt.
 */
(function () {
    'use strict';

    /**
     * @param {string} match  ID der textarea
     * @param {number} height Höhe des Bearbeitungsbereichs in Pixeln
     */
    window.pmsInitEditor = function (match, height) {
        var textarea = document.getElementById(match);
        if (!textarea || typeof window.Quill === 'undefined') {
            return;
        }

        var holder = document.createElement('div');
        holder.id = match + '_quill';
        holder.style.minHeight = height + 'px';
        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(holder, textarea);

        var quill = new window.Quill(holder, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['blockquote', 'link', 'image'],
                        ['clean'],
                    ],
                    handlers: {
                        // Quills eigener Bild-Knopf würde die Datei ungefragt
                        // als Base64 in den Text einbetten - ohne Ablage unter
                        // images/uploads, ohne Zuschneiden, ohne Wiederverwendung
                        // an anderer Stelle. Stattdessen unseren Bild-Dialog
                        // öffnen (admin-image-dialog.js), der Hochladen, Auswahl
                        // vorhandener Bilder und Zuschneiden schon anbietet.
                        image: function () {
                            window.dispatchEvent(new CustomEvent('pms-open-image-dialog'));
                        },
                    },
                },
            },
        });
        quill.root.innerHTML = textarea.value;

        // Der Bild-Dialog (admin-image-dialog.js) fügt hier ein, ohne
        // den Editor selbst zu kennen.
        window.PMS_ACTIVE_EDITOR = quill;

        var form = textarea.form;
        if (form) {
            form.addEventListener('submit', function () {
                textarea.value = quill.root.innerHTML;
            });
        }
    };
})();
