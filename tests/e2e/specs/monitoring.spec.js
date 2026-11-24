/**
 * Ereignisse, Backup-Manager und Website-Status.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError } = require('../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test.beforeEach(async ({ page }) => {
  await login(page, 'admin');
});

test('Ereignisseite listet Kommentare und Registrierungen', async ({ page }) => {
  await page.goto('admin/ereignisse');
  await expect(page.locator('body')).toContainText('Ereignisse');
  await expectNoPhpError(page);
});

test('Backup-Manager ist erreichbar und bietet Backup-Erstellung an', async ({ page }) => {
  await page.goto('admin/sicherungen');
  await expect(page.locator('body')).toContainText('Backup-Manager');
  await expect(page.locator('input[type="submit"]').first()).toBeVisible();
  await expectNoPhpError(page);
});

test('Website-Status zeigt Besucherinformationen', async ({ page }) => {
  await page.goto('admin/status');
  await expect(page.locator('body')).toContainText('Website-Status');
  await expectNoPhpError(page);
});

test('Website-Status benennt die zuletzt besuchte Backend-Seite', async ({ page }) => {
  await page.goto('admin/benutzer');
  await page.goto('admin/status');

  const row = page.locator('table.data-table tr', { hasText: 'admin' }).first();
  await expect(row).toContainText('PMS Administration');
  // Der Verweis traegt den sprechenden Pfad, nicht mehr action=...
  await expect(row.locator('a[href*="/admin/"]').last()).toHaveText(/Benutzerverwaltung|Website-Status/);
});

test('Es gibt kein Update-Modul mehr', async ({ page }) => {
  await page.goto('admin');
  const labels = await page.locator('.sidebar-nav .nav-label').allTextContents();
  expect(labels.map((l) => l.trim())).not.toContain('Update');
});
