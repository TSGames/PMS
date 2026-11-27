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
     * Prüft, ob ein Textfeld Skript-Blöcke enthält, und setzt in dem Fall
     * einen Hinweis davor. Auch der TinyMCE-Start fragt hier nach.
     *
     * @param {HTMLTextAreaElement} textarea
     * @returns {boolean} true, wenn der grafische Editor aus bleiben muss
     */
    window.pmsEditorBlocked = function (textarea) {
        if (!textarea || !/\[(php|code)\]/i.test(textarea.value)) {
            return false;
        }
        if (!textarea.previousElementSibling || !textarea.previousElementSibling.classList.contains('editor-blocked')) {
            var note = document.createElement('div');
            note.className = 'notice notice-warn editor-blocked';
            note.textContent = 'Dieser Inhalt enthält [php]- oder [code]-Blöcke. '
                + 'Der grafische Editor würde sie beim Speichern zerstören und bleibt deshalb aus.';
            textarea.parentNode.insertBefore(note, textarea);
        }
        return true;
    };

    /**
     * @param {string} match  ID der textarea
     * @param {number} height Höhe des Bearbeitungsbereichs in Pixeln
     */
    window.pmsInitEditor = function (match, height) {
        var textarea = document.getElementById(match);
        if (!textarea || typeof window.Quill === 'undefined') {
            return;
        }

        // Inhalte mit [php]- oder [code]-Blöcken bleiben im reinen Textfeld.
        // Der grafische Editor liest den Text als HTML ein: Aus "$i<count($a)"
        // wird dabei ein unbekanntes Element, und alles dahinter - der Rest
        // des Blocks und der Rest der Seite - geht beim Speichern verloren
        // (BEFUNDE B33).
        if (window.pmsEditorBlocked(textarea)) {
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
