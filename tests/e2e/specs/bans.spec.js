/**
 * Sperrungen (Bans).
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test('Liste zeigt die gesperrten Adressen', async ({ page }) => {
  await page.goto('admin.php?action=bans');
  await expect(page.locator('body')).toContainText('203.0.113.7');
  await expect(page.locator('body')).toContainText('Spam im Gästebuch');
});

test('Ban bearbeiten zeigt IP und Grund', async ({ page }) => {
  await page.goto('admin.php?action=bans&edit=1');
  await expect(page.locator('input[name="ip"]')).toHaveValue('203.0.113.7');
  await expect(page.locator('textarea[name="reason"]')).toHaveValue('Spam im Gästebuch');
});

test('Neuen Ban anlegen', async ({ page }) => {
  await page.goto('admin.php?action=bans&new=yes');
  await page.fill('input[name="ip"]', '192.0.2.44');
  await page.fill('textarea[name="reason"]', 'Testsperrung');
  // Ohne Dauer bricht das Speichern derzeit ab, siehe known-defects (B2)
  await page.fill('input[name="time"]', '7');
  await submit(page, 'input[name="bans"]');

  await expectNoPhpError(page);
  await page.goto('admin.php?action=bans');
  await expect(page.locator('body')).toContainText('192.0.2.44');
});

test('Ban ändern', async ({ page }) => {
  await page.goto('admin.php?action=bans&edit=2');
  await page.fill('textarea[name="reason"]', 'Grund angepasst');
  await page.fill('input[name="time"]', '30');
  await submit(page, 'input[name="bans"]');

  await page.goto('admin.php?action=bans&edit=2');
  await expect(page.locator('textarea[name="reason"]')).toHaveValue('Grund angepasst');
});
