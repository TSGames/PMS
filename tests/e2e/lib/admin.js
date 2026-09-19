// Hilfsfunktionen für die Admin-Tests.
const { execFileSync } = require('child_process');
const path = require('path');
const { expect } = require('@playwright/test');
const { USERS } = require('./users');

const REPO_ROOT = path.resolve(__dirname, '../../..');

/**
 * Bekannte JavaScript-Fehler des Altbestands.
 * Siehe tests/BEFUNDE.md; die Einträge entfallen, sobald die Ursachen behoben sind.
 */
/**
 * JavaScript-Fehler, die kein Testfehler sind.
 *
 * Seit der Editor der Variablen-Seite lokal ausgeliefert wird (B6), ist die
 * Liste leer. Sie bleibt als Stelle, an der ein begründeter Einzelfall
 * stehen könnte.
 */
const KNOWN_JS_ERRORS = [];

// Meldungen, die auf einen PHP-Fehler in der Ausgabe hindeuten.
const PHP_ERROR_PATTERNS = [
  'Fatal error',
  'Parse error',
  'Uncaught',
  'Call to undefined',
  'Cannot redeclare',
];

/**
 * Sammelt JavaScript-Fehler einer Seite (ohne die bekannten Altlasten).
 */
function collectJsErrors(page) {
  const errors = [];
  page.on('pageerror', (error) => {
    if (!KNOWN_JS_ERRORS.some((known) => error.message.includes(known))) {
      errors.push(error.message);
    }
  });
  return errors;
}

/**
 * Klickt ein Element an, das einen Seitenwechsel auslöst, und wartet, bis die
 * neue Seite geladen ist. Nötig, weil die Anwendung durchgängig mit
 * Formular-Postbacks arbeitet.
 */
async function submit(page, selector) {
  const target = typeof selector === 'string' ? page.locator(selector) : selector;
  await Promise.all([
    page.waitForResponse((response) => response.request().isNavigationRequest()),
    target.click(),
  ]);
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Wählt einen Filter der Werkzeugleiste aus. Die Auswahlfelder senden
 * selbst ab, deshalb wird auf den Seitenwechsel gewartet.
 */
async function selectFilter(page, name, option) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.selectOption(`select[name="${name}"]`, option),
  ]);
}

/**
 * Sucht in einer Übersicht über das Suchfeld der Werkzeugleiste.
 */
async function searchList(page, term) {
  await page.fill('input[name="q"]', term);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.press('input[name="q"]', 'Enter'),
  ]);
}

/**
 * Meldet einen Benutzer im Backend an und landet auf der Startseite.
 */
async function login(page, role = 'admin') {
  const user = USERS[role];
  if (!user) throw new Error(`Unbekannte Rolle: ${role}`);
  await page.goto('admin');
  await page.fill('input[name="login_name"]', user.name);
  await page.fill('input[name="login_password"]', user.password);
  await submit(page, 'input[name="login"]');
  return user;
}

/**
 * Meldet ab und verwirft alle Cookies, damit der nächste Aufruf
 * wieder die Login-Maske zeigt.
 */
async function logout(page) {
  await page.goto('admin/abmelden');
  await page.context().clearCookies();
}

/**
 * Prüft, dass eine Seite keine PHP-Fehlermeldung enthält.
 */
async function expectNoPhpError(page) {
  const body = await page.content();
  for (const pattern of PHP_ERROR_PATTERNS) {
    expect(body, `PHP-Fehler in der Ausgabe: ${pattern}`).not.toContain(pattern);
  }
}

/**
 * Liest eine Spalte der Admin-Tabellen. Die Kopfzeile steht im thead und
 * taucht deshalb nicht mit auf.
 */
async function tableColumn(page, columnIndex) {
  const values = await page
    .locator(`table.data-table tbody td:nth-child(${columnIndex})`)
    .allTextContents();
  return values.map((value) => value.trim());
}

/**
 * Setzt die Mock-Datenbank auf den Ausgangszustand zurück.
 * Wird von allen Specs benutzt, die Daten verändern.
 * Rührt bewusst nur die Datenbank an, nicht die Laufzeitkonfiguration.
 */
/**
 * Schreibt Text in das Feld, an dem der Code-Editor hängt.
 *
 * Das <textarea> bleibt im Formular, ist aber nicht mehr die Quelle: Der
 * Editor schreibt bei jeder Änderung hinein und beim Abschicken noch
 * einmal. Wer das Textfeld direkt füllt, verliert den Wert deshalb.
 */
async function fillEditor(page, name, text) {
  const textarea = page.locator(`textarea[name="${name}"]`);
  const editor = page.locator(`.code-editor[data-editor-for="${name}"] .cm-content`);

  // Ohne Editor (kein JavaScript, anderes Formular) das Feld direkt füllen
  if ((await editor.count()) === 0) {
    await textarea.fill(text);
    return;
  }

  await editor.click();
  await page.keyboard.press('ControlOrMeta+a');
  await page.keyboard.press('Delete');
  await page.keyboard.type(text);
}

/** Liest den Wert eines Feldes mit Code-Editor. */
async function editorValue(page, name) {
  const editor = page.locator(`.code-editor[data-editor-for="${name}"] .cm-content`);
  if ((await editor.count()) === 0) {
    return page.locator(`textarea[name="${name}"]`).inputValue();
  }
  return editor.innerText();
}

function resetDatabase() {
  waitForIdleServer();
  execFileSync('php', [path.join(REPO_ROOT, 'tests/mock/reset-db.php')], { stdio: 'pipe' });
}

/**
 * Wartet, bis der Entwicklungsserver keine Anfrage mehr bearbeitet.
 * Er ist einprozessig: Sobald diese Anfrage beantwortet ist, sind alle
 * vorherigen abgeschlossen und die Datenbank kann gefahrlos neu aufgebaut
 * werden.
 */
function waitForIdleServer() {
  const host = process.env.PMS_MOCK_HOST || '127.0.0.1';
  const port = process.env.PMS_MOCK_PORT || '8099';
  try {
    execFileSync('curl', ['-s', '-o', '/dev/null', '--max-time', '10', `http://${host}:${port}/robots.txt`]);
  } catch (error) {
    // Server läuft noch nicht - dann gibt es auch nichts abzuwarten
  }
}

module.exports = {
  collectJsErrors,
  expectNoPhpError,
  KNOWN_JS_ERRORS,
  login,
  logout,
  editorValue,
  fillEditor,
  resetDatabase,
  searchList,
  selectFilter,
  submit,
  tableColumn,
  USERS,
};
