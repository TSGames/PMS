/**
 * Bekannte Defekte des Admin-Backends (Stand vor dem Refactoring).
 *
 * Die Tests sind mit test.fail() als "erwarteter Fehlschlag" markiert:
 * Sie halten das heutige Fehlverhalten fest und schlagen fehl, sobald der
 * Defekt behoben ist. Dann wird der Test entschärft (test.fail entfernen)
 * und der Eintrag in tests/BEFUNDE.md abgehakt.
 */

const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const { login, resetDatabase, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test('B1: Benutzer lässt sich über das Formular anlegen', async ({ page }) => {
  test.fail(true, 'Fatal error in admin_actions_admin.php:140 ("" * 1) beim leeren id-Feld');

  await page.goto('admin.php?action=user&new=yes');
  await page.fill('input[name="name"]', 'neuerbenutzer');
  await page.fill('input[name="password"]', 'geheim123');
  await page.fill('input[name="passwordr"]', 'geheim123');
  await page.fill('input[name="mail"]', 'neuerbenutzer@example.org');
  await page.check('input[name="active"]');
  await submit(page, 'input[name="user"]');

  await page.goto('admin.php?action=user');
  await expect(page.locator('table.items')).toContainText('neuerbenutzer');
});

test('B2: Ban ohne Dauer lässt sich speichern', async ({ page }) => {
  test.fail(true, 'Fatal error in admin_actions_admin.php:25 ("" * 60) bei leerem Dauer-Feld');

  await page.goto('admin.php?action=bans&new=yes');
  await page.fill('input[name="ip"]', '192.0.2.99');
  await page.fill('textarea[name="reason"]', 'Ban ohne Ablaufdatum');
  await submit(page, 'input[name="bans"]');

  await page.goto('admin.php?action=bans');
  await expect(page.locator('table.items')).toContainText('192.0.2.99');
});

test('B3: Menüeintrag lässt sich speichern', async ({ page }) => {
  test.fail(true, '$post wird für das Menü-Formular nie auf 2 gesetzt (admin_actions_menu.php:40)');

  await page.goto('admin.php?action=menu&edit=3');
  await page.fill('input[name="name"]', 'Veranstaltungen');
  await submit(page, 'input[name="menu"]');

  await page.goto('admin.php?action=menu');
  await expect(page.locator('table.items')).toContainText('Veranstaltungen');
});

test('B4a: Benutzer werden erst nach Rückfrage gelöscht', async ({ page }) => {
  test.fail(true, 'admin.php?action=user&delete=… löscht sofort per GET, ohne Bestätigung');

  await page.goto('admin.php?action=user&delete=4');
  await expect(page.locator('body')).not.toContainText('erfolgreich entfernt');
  await page.goto('admin.php?action=user');
  await expect(page.locator('table.items')).toContainText('gast');
});

test('B4b: Inhalte werden erst nach Rückfrage gelöscht', async ({ page }) => {
  test.fail(true, 'admin.php?action=item&delete=… löscht sofort per GET, ohne Bestätigung');

  await page.goto('admin.php?action=item&delete=4');
  await expect(page.locator('body')).not.toContainText('erfolgreich entfernt');
  await page.goto('admin.php?action=item');
  await expect(page.locator('table.items')).toContainText('Jahreshauptversammlung');
});

test('B5: Login-Maske erzeugt keine JavaScript-Fehler', async ({ page, context }) => {
  test.fail(true, 'admin.php gibt den Seitenleisten-Code auch ohne Seitenleiste aus');

  await context.clearCookies();
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.goto('admin.php');
  await page.waitForTimeout(300);
  expect(errors).toEqual([]);
});

test('B8: Menü-Formular erzeugt keine SQL-Syntaxfehler', async ({ page }) => {
  test.fail(true, 'make_sql("subcat","cat = ") erzeugt "WHERE cat =  ORDER BY ..."');

  const log = path.resolve(__dirname, '../../.runtime/logs/php-error.log');
  fs.writeFileSync(log, '');
  await page.goto('admin.php?action=menu&new=yes');
  expect(fs.readFileSync(log, 'utf8')).not.toContain('syntax error');
});
