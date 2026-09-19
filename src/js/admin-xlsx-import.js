/**
 * Import einer XLSX-Datei in den Inhaltseditor.
 *
 * Der Inhalt wird auf dem Server ausgelesen und als Text in den Editor
 * bzw. in das Textfeld übernommen.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var picker = document.getElementById('xlsx_file_picker');
        var status = document.getElementById('xlsx_status');
        if (!picker || !status) {
            return;
        }

        picker.addEventListener('change', function () {
            if (!this.files.length) {
                return;
            }
            status.textContent = 'Wird importiert...';

            var data = new FormData();
            data.append('xlsx_file', this.files[0]);
            data.append('pms_token', status.getAttribute('data-token') || window.PMS_TOKEN || '');

            fetch('admin.php?action=xlsx_import_ajax', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    if (result.error) {
                        status.textContent = 'Fehler: ' + result.error;
                        return;
                    }
                    insertContent(result.content);
                    status.textContent = 'Importiert';
                })
                .catch(function (error) {
                    status.textContent = 'Fehler beim Import: ' + (error && error.message ? error.message : 'Unbekannter Fehler');
                });

            this.value = '';
        });
    });

    /** Fügt den gelesenen Text vor dem bestehenden Inhalt ein. */
    function insertContent(text) {
        var separator = '--- Bestehender Inhalt ---';

        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
            var existing = tinyMCE.activeEditor.getContent().trim();
            var html = text.replace(/\n/g, '<br>');
            tinyMCE.activeEditor.setContent(html + (existing ? '<br><br>' + separator + '<br><br>' + existing : ''));
            return;
        }

        var field = document.querySelector('textarea[name=content]');
        if (field) {
            field.value = text + (field.value ? '\n\n' + separator + '\n\n' + field.value : '');
        }
    }
})();
