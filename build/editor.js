/**
 * Der Editor der Variablen-Seite.
 *
 * Bisher lud die Seite den Monaco-Editor von einem CDN nach - rund fuenf
 * Megabyte, bei jeder Bearbeitung eine Anfrage an einen Dritt-Server, und
 * ohne Internetzugang blieb das Feld leer. Hier steht eine schlanke
 * Zusammenstellung von CodeMirror, die das Projekt selbst ausliefert.
 *
 * Gebaut wird sie mit `npm run vendor:editor` nach src/js/vendor/editor.js.
 */

import { EditorView, lineNumbers, keymap, highlightActiveLine, drawSelection } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { php } from '@codemirror/lang-php';
import { htmlLanguage } from '@codemirror/lang-html';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching, indentUnit } from '@codemirror/language';
import { Decoration, ViewPlugin } from '@codemirror/view';
import { RangeSetBuilder } from '@codemirror/state';

/**
 * Die Sondermarken von PMS hervorheben.
 *
 * [php]…[/php] und [code]…[/code] sind kein HTML und keine PHP-Syntax,
 * sondern eine Eigenheit von PMS: Was zwischen [php] steht, führt
 * make_dynamic() per eval() aus. CodeMirror kennt diese Marken nicht,
 * deshalb werden sie hier eigens ausgezeichnet - und der Inhalt eines
 * [php]-Blocks bekommt einen Hintergrund, damit man sieht, wo
 * ausgeführter Code anfängt und aufhört.
 */
const SPECIAL_TAGS = /\[(\/?)(php|code)\]/gi;

const tagMark = Decoration.mark({ class: 'cm-pms-tag' });
const phpMark = Decoration.mark({ class: 'cm-pms-php' });

function specialTagDecorations(view) {
    const builder = new RangeSetBuilder();
    const text = view.state.doc.toString();
    const stellen = [];

    for (const treffer of text.matchAll(SPECIAL_TAGS)) {
        stellen.push({
            von: treffer.index,
            bis: treffer.index + treffer[0].length,
            schliessend: treffer[1] === '/',
            art: treffer[2].toLowerCase(),
        });
    }

    // Erst den Inhalt der php-Blöcke, dann die Marken selbst: Ein
    // RangeSetBuilder verlangt aufsteigende Positionen.
    const bereiche = [];
    let offen = null;
    for (const stelle of stellen) {
        bereiche.push({ von: stelle.von, bis: stelle.bis, deko: tagMark });
        if (stelle.art !== 'php') {
            continue;
        }
        if (!stelle.schliessend) {
            offen = stelle;
        } else if (offen && stelle.von > offen.bis) {
            bereiche.push({ von: offen.bis, bis: stelle.von, deko: phpMark });
            offen = null;
        }
    }

    bereiche.sort((a, b) => a.von - b.von || a.bis - b.bis);
    for (const bereich of bereiche) {
        builder.add(bereich.von, bereich.bis, bereich.deko);
    }
    return builder.finish();
}

const specialTags = ViewPlugin.fromClass(
    class {
        constructor(view) {
            this.decorations = specialTagDecorations(view);
        }

        update(update) {
            if (update.docChanged || update.viewportChanged) {
                this.decorations = specialTagDecorations(update.view);
            }
        }
    },
    { decorations: (plugin) => plugin.decorations }
);

/**
 * Haengt an jedes Textfeld mit data-editor einen Editor.
 *
 * Das Textfeld selbst bleibt bestehen und wird bei jeder Aenderung
 * mitgeschrieben - so verhaelt sich das Formular beim Abschicken wie
 * vorher, und ohne JavaScript ist es immer noch benutzbar.
 */
function attach(textarea) {
    if (textarea.dataset.editorReady) {
        return;
    }
    textarea.dataset.editorReady = '1';

    const host = document.createElement('div');
    host.className = 'code-editor';
    // Der Feldname am Editor macht ihn auffindbar - fuer Tests und fuer
    // alles, was das Feld sonst ansprechen will.
    if (textarea.name) {
        host.dataset.editorFor = textarea.name;
    }
    textarea.parentNode.insertBefore(host, textarea);
    textarea.classList.add('code-editor-source');

    const view = new EditorView({
        parent: host,
        state: EditorState.create({
            doc: textarea.value,
            extensions: [
                lineNumbers(),
                highlightActiveLine(),
                drawSelection(),
                history(),
                bracketMatching(),
                indentUnit.of('    '),
                syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
                // PHP in HTML: Die Felder enthalten HTML-Schnipsel, können
                // aber auch <?php oder [php] tragen - make_dynamic() führt
                // beides aus.
                //
                // Als Basis steht hier die HTML-*Sprache*, nicht html():
                // Letzteres brächte das automatische Schließen von Tags mit,
                // und wer </div> selbst tippt, bekäme es doppelt.
                php({ baseLanguage: htmlLanguage }),
                specialTags,
                keymap.of([...defaultKeymap, ...historyKeymap, indentWithTab]),
                EditorView.lineWrapping,
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        textarea.value = update.state.doc.toString();
                    }
                }),
            ],
        }),
    });

    // Beim Abschicken den letzten Stand sichern, auch ohne Aenderungsereignis
    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = view.state.doc.toString();
        });
    }
}

function init() {
    document.querySelectorAll('textarea[data-editor]').forEach(attach);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
