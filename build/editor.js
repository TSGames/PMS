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
import { html } from '@codemirror/lang-html';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching, indentUnit } from '@codemirror/language';

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
                // Ohne automatisches Schliessen von Tags: Wer </em> selbst
                // tippt, bekaeme es sonst doppelt.
                html({ autoCloseTags: false }),
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
