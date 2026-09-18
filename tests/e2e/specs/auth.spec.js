/**
 * Anmeldung, Abmeldung und Rechteprüfung.
 */

const { test, expect } = require('@playwright/test');
const { login, logout, resetDatabase, USERS } = require('../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test.beforeEach(async ({ context }) => {
  await context.clearCookies();
});

test('Ohne Anmeldung erscheint die Login-Maske', async ({ page }) => {
  await page.goto('admin.php');
  await expect(page.locator('body')).toContainText('PMS Back End Login');
  await expect(page.locator('input[name="login_name"]')).toBeVisible();
  await expect(page.locator('input[name="login_password"]')).toBeVisible();
  await expect(page.locator('.admin-sidebar')).toHaveCount(0);
});

test('Anmeldung als Super-Administrator führt ins Backend', async ({ page }) => {
  await login(page, 'admin');
  await expect(page.locator('.admin-sidebar')).toBeVisible();
  await expect(page.locator('.sidebar-user')).toContainText('Hallo, admin');
  await expect(page.locator('body')).toContainText('Willkommen im Admin Center!');
});

test('Anmeldung als Administrator führt ins Backend', async ({ page }) => {
  await login(page, 'redakteur');
  await expect(page.locator('.sidebar-user')).toContainText('Hallo, redakteur');
});

test('Falsches Passwort wird abgewiesen', async ({ page }) => {
  await page.goto('admin.php');
  await page.fill('input[name="login_name"]', 'admin');
  await page.fill('input[name="login_password"]', 'falsch');
  await page.click('input[name="login"]');
  await expect(page.locator('body')).toContainText('Passwort ist ungültig!');
  await expect(page.locator('.admin-sidebar')).toHaveCount(0);
});

test('Unbekannter Benutzer wird abgewiesen', async ({ page }) => {
  await page.goto('admin.php');
  await page.fill('input[name="login_name"]', 'gibtesnicht');
  await page.fill('input[name="login_password"]', 'egal');
  await page.click('input[name="login"]');
  await expect(page.locator('body')).toContainText('Benutzer existiert nicht!');
});

test('Gesperrter Benutzer wird abgewiesen', async ({ page }) => {
  await page.goto('admin.php');
  await page.fill('input[name="login_name"]', 'gesperrt');
  await page.fill('input[name="login_password"]', 'admin123');
  await page.click('input[name="login"]');
  await expect(page.locator('body')).toContainText('Der Benutzer ist gesperrt!');
});

test('Moderator darf das Backend nicht betreten', async ({ page }) => {
  await page.goto('admin.php');
  await page.fill('input[name="login_name"]', USERS.moderator.name);
  await page.fill('input[name="login_password"]', USERS.moderator.password);
  await page.click('input[name="login"]');
  await expect(page.locator('body')).toContainText('Ihre Berechtigungen sind zu niedrig!');
  await expect(page.locator('.admin-sidebar')).toHaveCount(0);
});

test('Abmeldung beendet die Sitzung', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin.php?action=logout');
  await expect(page.locator('body')).toContainText('Logout erfolgreich!');

  await page.context().clearCookies();
  await page.goto('admin.php?action=cat');
  await expect(page.locator('body')).toContainText('PMS Back End Login');
});

test('Administrator ohne Super-Admin-Rechte sieht den Konfigurator nicht', async ({ page }) => {
  await login(page, 'redakteur');
  await page.goto('admin.php?action=config');
  await expect(page.locator('body')).toContainText('Ihre Berechtigungen sind zu niedrig');
  await expect(page.locator('input[name="config"]')).toHaveCount(0);
});

test('Administrator ohne Super-Admin-Rechte darf keine Kategorie löschen', async ({ page }) => {
  await login(page, 'redakteur');
  await page.goto('admin.php?action=cat&delete=4');
  await expect(page.locator('body')).toContainText('nicht genügend Rechte');
  await expect(page.locator('input[name="cat_delete"]')).toHaveCount(0);
});

test('Direkter Aufruf eines Handler-Moduls ist verboten', async ({ page }) => {
  const response = await page.goto('admin_actions_content.php');
  expect(response.status()).toBe(403);
  await expect(page.locator('body')).toContainText('Direct access not allowed');
});

test.afterEach(async ({ page }) => {
  await logout(page).catch(() => {});
});
