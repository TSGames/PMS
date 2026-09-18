/**
 * Ereignisse, Backup-Manager, Website-Status und das Update-Modul.
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
  await page.goto('admin.php?action=events');
  await expect(page.locator('body')).toContainText('Ereignisse');
  await expectNoPhpError(page);
});

test('Backup-Manager ist erreichbar und bietet Backup-Erstellung an', async ({ page }) => {
  await page.goto('admin.php?action=backup');
  await expect(page.locator('body')).toContainText('Backup-Manager');
  await expect(page.locator('input[type="submit"]').first()).toBeVisible();
  await expectNoPhpError(page);
});

test('Website-Status zeigt Besucherinformationen', async ({ page }) => {
  await page.goto('admin.php?action=activity');
  await expect(page.locator('body')).toContainText('Website-Status');
  await expectNoPhpError(page);
});

test('Update-Modul ist erreichbar', async ({ page }) => {
  await page.goto('admin.php?modul=update');
  await expect(page.locator('body')).toContainText('Updates Suchen');
  await expectNoPhpError(page);
});
