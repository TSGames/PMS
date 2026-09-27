/**
 * Der grafische Editor der Inhaltsbearbeitung (ersetzt TinyMCE).
 *
 * TinyMCE lud ~5 MB nach und lief in einem eigenen iframe. Quill kommt
 * mit rund 200 KB minifiziert (~60 KB gzip) deutlich schlanker daher und
 * ist touch-tauglich, weshalb es hier - wie schon der Code-Editor der
 * Variablen-Seite - lokal gebaut wird statt von einem CDN geladen.
 *
 * Gebaut wird es mit `npm run vendor:editor` nach src/js/vendor/quill.js.
 */

import Quill from 'quill';

window.Quill = Quill;
