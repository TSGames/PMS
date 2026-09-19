/**
 * Benutzerverwaltung: Liste, Bearbeiten, Validierung, Löschen.
 *
 * Das Anlegen neuer Benutzer ist derzeit defekt und in
 * specs/known-defects.spec.js (B1) festgehalten.
 */

const { test, expect } = require('@playwright/test');
const {
  login,
  resetDatabase,
  expectNoPhpError,
  searchList,
  selectFilter,
  submit,
  tableColumn,
} = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
  await page.goto('admin/benutzer');
});

test('Liste zeigt alle Mock-Benutzer', async ({ page }) => {
  expect(await tableColumn(page, 2)).toEqual([
    'admin',
    'redakteur',
    'moderator',
    'gast',
    'gesperrt',
  ]);
});

test('Benutzertypen werden im Klartext angezeigt', async ({ page }) => {
  await expect(page.locator('body')).toContainText('Super-Administrator');
  await expect(page.locator('body')).toContainText('Moderator');
});

test('Werkzeugleiste nennt die Zahl der Treffer', async ({ page }) => {
  await expect(page.locator('.toolbar-count')).toHaveText('5 Einträge');
});

test('Suche findet einen Benutzer über die Mailadresse', async ({ page }) => {
  await searchList(page, 'redakteur@example.org');
  expect(await tableColumn(page, 2)).toEqual(['redakteur']);
});

test('Filter nach Benutzertyp schränkt die Liste ein', async ({ page }) => {
  await selectFilter(page, 'typ', { label: 'Moderator' });
  expect(await tableColumn(page, 2)).toEqual(['moderator']);
});

test('Bearbeiten zeigt die gespeicherten Werte', async ({ page }) => {
  await page.goto('admin/benutzer?edit=2');
  await expect(page.locator('input[name="name"]')).toHaveValue('redakteur');
  await expect(page.locator('input[name="mail"]')).toHaveValue('redakteur@example.org');
  await expect(page.locator('select[name="typ"]')).toHaveValue('2');
  await expect(page.locator('input[name="active"]')).toBeChecked();
});

test('Mailadresse eines Benutzers ändern', async ({ page }) => {
  await page.goto('admin/benutzer?edit=4');
  await page.fill('input[name="mail"]', 'gast-neu@example.org');
  await submit(page, 'input[name="user"]');

  await expectNoPhpError(page);
  await page.goto('admin/benutzer?edit=4');
  await expect(page.locator('input[name="mail"]')).toHaveValue('gast-neu@example.org');
});

test('Abweichende Passwortwiederholung wird abgelehnt', async ({ page }) => {
  await page.goto('admin/benutzer?edit=4');
  await page.fill('input[name="password"]', 'geheim123');
  await page.fill('input[name="passwordr"]', 'anders123');
  await submit(page, 'input[name="user"]');

  await expect(page.locator('body')).toContainText('Passw');
});

test('Ungültige Mailadresse wird abgelehnt', async ({ page }) => {
  await page.goto('admin/benutzer?edit=4');
  await page.fill('input[name="mail"]', 'keine-mail');
  await submit(page, 'input[name="user"]');

  await expect(page.locator('body')).toContainText('Mail');
  await page.goto('admin/benutzer?edit=4');
  await expect(page.locator('input[name="mail"]')).toHaveValue('gast@example.org');
});

test('Zu kurzer Benutzername wird abgelehnt', async ({ page }) => {
  await page.goto('admin/benutzer?edit=4');
  await page.fill('input[name="name"]', 'ab');
  await submit(page, 'input[name="user"]');

  await page.goto('admin/benutzer');
  expect(await tableColumn(page, 2)).toContain('gast');
});

test('Bereits vergebener Benutzername wird abgelehnt', async ({ page }) => {
  await page.goto('admin/benutzer?edit=4');
  await page.fill('input[name="name"]', 'admin');
  await submit(page, 'input[name="user"]');

  await expect(page.locator('body')).toContainText('bereits vorhanden');
});

test('Eigener Account kann nicht gesperrt werden', async ({ page }) => {
  await page.goto('admin/benutzer?edit=1');
  await page.uncheck('input[name="active"]');
  await submit(page, 'input[name="user"]');

  await expect(page.locator('body')).toContainText('nicht Ihren aktuellen Account sperren');
});

test('Benutzer löschen fragt nach und entfernt ihn', async ({ page }) => {
  await page.goto('admin/benutzer?delete=5');
  await expect(page.locator('body')).toContainText('gesperrt');
  await submit(page, 'input[name="confirm_delete"]');

  await expect(page.locator('body')).toContainText('erfolgreich entfernt');
  await page.goto('admin/benutzer');
  expect(await tableColumn(page, 2)).not.toContain('gesperrt');
});

test('Eigenes Konto lässt sich nicht löschen', async ({ page }) => {
  await page.goto('admin/benutzer?delete=1');
  await expect(page.locator('body')).toContainText('nicht selbst löschen');
  await page.goto('admin/benutzer');
  expect(await tableColumn(page, 2)).toContain('admin');
});
