/**
 * Website-Konfigurator: Reiter und Suche über alle Einstellungen.
 *
 * Der Konfigurator hatte bisher über 35 Einstellungen in einem einzigen
 * Formular. Die Abschnitte liegen jetzt auf Reitern; wer eine bestimmte
 * Einstellung sucht, findet sie über das Suchfeld quer über alle Reiter.
 *
 * Der gewählte Reiter steht in der Adresse, damit er sich verweisen und
 * nach dem Speichern wiederfinden lässt.
 */
document.addEventListener('alpine:init', function () {
    window.Alpine.data('configForm', function (initialTab) {
        return {
            tab: (window.location.hash || '').replace('#', '') || initialTab,
            search: '',

            select(tab) {
                this.tab = tab;
                // Der Pfad muss mit: Die Seite trägt ein <base href="/">, und
                // eine relative Adresse würde sonst dagegen aufgelöst
                window.history.replaceState(
                    null,
                    '',
                    window.location.pathname + window.location.search + '#' + tab
                );
            },

            /** Passt eine einzelne Einstellung zur Suche? */
            matches(element) {
                if (this.search === '') {
                    return true;
                }
                return (element.dataset.search || '').indexOf(this.search.toLowerCase()) !== -1;
            },

            /**
             * Ohne Suche zeigt der gewählte Reiter seine Abschnitte.
             * Mit Suche erscheint jeder Abschnitt, der einen Treffer enthält.
             */
            sectionVisible(element) {
                if (this.search === '') {
                    return element.dataset.tab === this.tab;
                }
                return Array.prototype.some.call(
                    element.querySelectorAll('[data-search]'),
                    this.matches.bind(this)
                );
            },
        };
    });
});
