/**
 * Abhängige Auswahlfelder: Kategorie - Unterkategorie - Inhalt.
 *
 * Bisher musste nach jeder Auswahl ein Schalter "Aktualisieren" gedrückt
 * werden, der die ganze Seite neu lud. Die Einträge werden jetzt über
 * /admin/api/auswahl nachgeladen.
 *
 * Ohne JavaScript bleibt das Formular bedienbar: Die Auswahlfelder zeigen
 * dann die Einträge, die der Server beim Aufbau der Seite mitgegeben hat.
 */
document.addEventListener('alpine:init', function () {
    window.Alpine.data('linkedSelects', function (config) {
        return {
            url: config.url,
            typ: config.typ || 0,
            cat: config.cat || 0,
            subcat: config.subcat || 0,
            item: config.item || 0,
            subcats: config.subcats || [],
            items: config.items || [],
            loading: false,

            /** Holt die Einträge einer Ebene; bei einem Fehler bleibt sie leer. */
            async fetchOptions(kind, parent) {
                if (!parent) {
                    return [];
                }
                this.loading = true;
                try {
                    const response = await fetch(
                        this.url + '?typ=' + encodeURIComponent(kind) + '&parent=' + encodeURIComponent(parent),
                        { credentials: 'same-origin', headers: { Accept: 'application/json' } }
                    );
                    if (!response.ok) {
                        return [];
                    }
                    const data = await response.json();
                    return data.options || [];
                } catch (error) {
                    return [];
                } finally {
                    this.loading = false;
                }
            },

            async catChanged() {
                this.subcats = await this.fetchOptions('subcat', this.cat);
                this.subcat = 0;
                this.items = [];
                this.item = 0;
            },

            async subcatChanged() {
                this.items = await this.fetchOptions('item', this.subcat);
                this.item = 0;
            },
        };
    });
});
