/**
 * Bild-Dialog des Inhaltseditors.
 *
 * Früher war die Bildauswahl eine Folge eigener Seiten: Editor verlassen,
 * Datei hochladen, Größe festlegen, zurück in den Editor. Wer den Text
 * zwischendurch geändert hatte, verlor die Änderung. Der Dialog erledigt
 * alles über /admin/api/bilder, ohne die Seite zu verlassen.
 */
document.addEventListener('alpine:init', function () {
    window.Alpine.data('imageDialog', function (config) {
        return {
            url: config.url,
            open: false,
            loading: false,
            uploading: false,
            error: '',
            images: [],
            selected: null,
            width: 0,
            height: 0,
            keepRatio: true,

            /**
             * Der Zuschneide-Dialog (crop_modal.js) fügt sein Ergebnis über
             * diesen Haken ein - er kennt den Alpine-Zustand nicht.
             */
            init() {
                const dialog = this;
                window.PMS_INSERT_IMAGE = function (url) {
                    dialog.insertMarkup('<img src="' + url + '" alt="">');
                    dialog.close();
                };
            },

            async show() {
                this.open = true;
                this.error = '';
                await this.load();
            },

            close() {
                this.open = false;
                this.selected = null;
            },

            /** Alle hochgeladenen Bilder holen. */
            async load() {
                this.loading = true;
                try {
                    const data = await this.request({ do: 'list' }, 'GET');
                    this.images = data.images || [];
                } finally {
                    this.loading = false;
                }
            },

            select(image) {
                this.selected = image;
                this.width = image.width;
                this.height = image.height;
            },

            /** Seitenverhältnis des gewählten Bildes halten. */
            widthChanged() {
                if (this.keepRatio && this.selected && this.selected.width > 0) {
                    const ratio = this.selected.height / this.selected.width;
                    this.height = Math.max(1, Math.round(this.width * ratio));
                }
            },

            heightChanged() {
                if (this.keepRatio && this.selected && this.selected.height > 0) {
                    const ratio = this.selected.width / this.selected.height;
                    this.width = Math.max(1, Math.round(this.height * ratio));
                }
            },

            async upload(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }

                const body = new FormData();
                body.append('do', 'upload');
                body.append('image', file);

                this.uploading = true;
                this.error = '';
                try {
                    const data = await this.request(body, 'POST');
                    if (data.image) {
                        this.images.unshift(data.image);
                        this.select(data.image);
                    }
                } finally {
                    this.uploading = false;
                    event.target.value = '';
                }
            },

            async remove(image) {
                if (!window.confirm('Soll "' + image.name + '" gelöscht werden?')) {
                    return;
                }

                const body = new FormData();
                body.append('do', 'delete');
                body.append('name', image.name);

                await this.request(body, 'POST');
                this.images = this.images.filter(function (entry) {
                    return entry.name !== image.name;
                });
                if (this.selected && this.selected.name === image.name) {
                    this.selected = null;
                }
            },

            /** Größe übernehmen, falls geändert, und das Bild einfügen. */
            async insert() {
                if (!this.selected) {
                    return;
                }

                let image = this.selected;
                if (this.width !== image.width || this.height !== image.height) {
                    const body = new FormData();
                    body.append('do', 'scale');
                    body.append('name', image.name);
                    body.append('width', this.width);
                    body.append('height', this.height);

                    const data = await this.request(body, 'POST');
                    if (data.image) {
                        image = data.image;
                    }
                }

                this.insertMarkup('<img src="' + image.url.split('?')[0] + '" alt="">');
                this.close();
            },

            /** In den grafischen Editor, sonst an die Schreibmarke im Textfeld. */
            insertMarkup(markup) {
                if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.activeEditor) {
                    window.tinyMCE.activeEditor.execCommand('mceInsertContent', false, markup);
                    return;
                }

                const field = document.getElementById('content');
                if (!field) {
                    return;
                }

                const start = field.selectionStart || 0;
                const end = field.selectionEnd || 0;
                field.value = field.value.substring(0, start) + markup + field.value.substring(end);
                field.selectionStart = field.selectionEnd = start + markup.length;
                field.focus();
            },

            /** Eine Anfrage an die Schnittstelle; Fehler landen im Dialog. */
            async request(payload, method) {
                this.error = '';
                try {
                    let target = this.url;
                    let options = { method: method, credentials: 'same-origin' };

                    if (method === 'GET') {
                        target += '?' + new URLSearchParams(payload).toString();
                    } else {
                        payload.append('pms_token', window.PMS_TOKEN || '');
                        options.body = payload;
                    }

                    const response = await fetch(target, options);
                    const data = await response.json();
                    if (!response.ok || data.error) {
                        this.error = data.error || ('Fehler ' + response.status);
                        return {};
                    }
                    return data;
                } catch (error) {
                    this.error = 'Die Anfrage ist fehlgeschlagen.';
                    return {};
                }
            },
        };
    });
});
