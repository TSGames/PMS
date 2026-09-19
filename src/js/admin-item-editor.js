/**
 * Oberfläche der Inhaltsverwaltung.
 *
 * Bündelt die Skripte, die früher als Inline-Blöcke im PHP-Code standen:
 * Formular-Schalter, Bildvorschau der Bildauswahl und die Größenanpassung
 * vor dem Einfügen eines Bildes.
 */
(function () {
    'use strict';

    /** Schalter beim Absenden ausblenden und Hinweis einblenden. */
    function wireSubmitFeedback() {
        var form = document.querySelector('form[data-busy-hint]');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function () {
            var hint = document.getElementById(form.getAttribute('data-busy-hint'));
            if (hint) {
                hint.style.display = '';
            }
            form.querySelectorAll('[data-hide-on-submit]').forEach(function (element) {
                element.style.display = 'none';
            });
        });
    }

    /** Weitere Bildoptionen ein-/ausklappen. */
    function wireImageOptions() {
        var toggle = document.getElementById('pic_extended_toggle');
        var panel = document.getElementById('pic_extended');
        if (!toggle || !panel) {
            return;
        }
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            panel.style.display = panel.style.display === 'none' ? '' : 'none';
        });
    }

    /** Vorschau in der Liste der vorhandenen Bilder. */
    function wireImagePreview() {
        var preview = document.getElementById('image_preview_div');
        var image = document.getElementById('image_preview');
        if (!preview || !image) {
            return;
        }

        document.querySelectorAll('[data-preview-image]').forEach(function (link) {
            link.addEventListener('mouseover', function (event) {
                image.src = 'images/uploads/' + link.getAttribute('data-preview-image');
                image.width = link.getAttribute('data-preview-width');
                image.height = link.getAttribute('data-preview-height');
                preview.style.display = '';
                preview.style.left = (event.pageX + 18) + 'px';
                preview.style.top = (event.pageY + 18) + 'px';
            });
            link.addEventListener('mousemove', function (event) {
                preview.style.left = (event.pageX + 18) + 'px';
                preview.style.top = (event.pageY + 18) + 'px';
            });
            link.addEventListener('mouseout', function () {
                preview.style.display = 'none';
            });
        });
    }

    /** Rückfrage vor dem Löschen einer hochgeladenen Bilddatei. */
    function wireImageDelete() {
        document.querySelectorAll('[data-delete-image]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var name = link.getAttribute('data-delete-image');
                var message = 'Möchten Sie das Bild "' + name + '" wirklich löschen?\n'
                    + 'Hinweis: Diese Aktion kann nicht rückgängig gemacht werden!';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    /** Breite und Höhe beim Einfügen eines Bildes aufeinander abstimmen. */
    function wireImageScale() {
        var width = document.getElementById('image_width');
        var height = document.getElementById('image_height');
        var keepRatio = document.getElementById('image_pro');
        var scaled = document.getElementById('image_scale');
        if (!width || !height || !scaled) {
            return;
        }

        var ratio = parseFloat(scaled.getAttribute('data-ratio')) || 1;

        function apply() {
            scaled.width = width.value;
            scaled.height = height.value;
        }

        function ensureMinimum() {
            if (parseInt(width.value, 10) < 1 || isNaN(parseInt(width.value, 10))) {
                window.alert('Es muss eine gültige Bildbreite eingegeben werden!');
                width.value = 1;
            }
            if (parseInt(height.value, 10) < 1 || isNaN(parseInt(height.value, 10))) {
                window.alert('Es muss eine gültige Bildhöhe eingegeben werden!');
                height.value = 1;
            }
        }

        width.addEventListener('keyup', function () {
            if (keepRatio && keepRatio.checked) {
                height.value = parseInt(width.value / ratio, 10) || 1;
            }
            apply();
        });
        height.addEventListener('keyup', function () {
            if (keepRatio && keepRatio.checked) {
                width.value = parseInt(height.value * ratio, 10) || 1;
            }
            apply();
        });
        width.addEventListener('blur', function () {
            ensureMinimum();
            apply();
        });
        height.addEventListener('blur', function () {
            ensureMinimum();
            apply();
        });
        apply();
    }

    document.addEventListener('DOMContentLoaded', function () {
        wireSubmitFeedback();
        wireImageOptions();
        wireImagePreview();
        wireImageDelete();
        wireImageScale();
    });
})();
