/**
 * Frontend: Anmeldung und Registrierung.
 */

const { test, expect } = require('@playwright/test');
const { expectNoPhpError, resetDatabase, USERS } = require('../../lib/admin');

test.beforeEach(async ({ context }) => {
  resetDatabase();
  await context.clearCookies();
});

test('Die Anmeldemaske steht in der Seitenleiste', async ({ page }) => {
  await page.goto('index.php');

  await expect(page.locator('input[name="name"]').first()).toBeVisible();
  await expect(page.locator('input[name="password"]').first()).toBeVisible();
});

test('Anmeldung mit gültigen Daten', async ({ page }) => {
  await page.goto('index.php');
  await page.fill('input[name="name"]', USERS.gast.name);
  await page.fill('input[name="password"]', USERS.gast.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('input[name="user_login"]'),
  ]);

  await expect(page.locator('body')).toContainText(USERS.gast.name);
  await expectNoPhpError(page);
});

test('Anmeldung mit falschem Passwort wird abgewiesen', async ({ page }) => {
  await page.goto('index.php');
  await page.fill('input[name="name"]', USERS.gast.name);
  await page.fill('input[name="password"]', 'falsch');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('input[name="user_login"]'),
  ]);

  await expect(page.locator('body')).not.toContainText('Logout');
  await expectNoPhpError(page);
});

test('Die Registrierung ist erreichbar', async ({ page }) => {
  await page.goto('action/register.html');

  await expect(page.locator('body')).toContainText('Registrier');
  await expectNoPhpError(page);
});

test('Das Zurücksetzen des Passworts ist erreichbar', async ({ page }) => {
  await page.goto('action/password_recover.html');

  await expect(page.locator('body')).toContainText('Passwort');
  await expectNoPhpError(page);
});
