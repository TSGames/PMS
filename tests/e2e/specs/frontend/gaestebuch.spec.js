/**
 * Frontend: Gästebuch und Kommentare.
 */

const { test, expect } = require('@playwright/test');
const { expectNoPhpError, resetDatabase } = require('../../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test('Das Gästebuch zeigt die vorhandenen Einträge', async ({ page }) => {
  await page.goto('index.php?action=guestbook');

  await expect(page).toHaveTitle(/Gästebuch/);
  const body = page.locator('body');
  await expect(body).toContainText('Schöne Seite');
  await expect(body).toContainText('Weiter so!');
  await expectNoPhpError(page);
});

test('Das Gästebuch bietet ein Formular für neue Einträge', async ({ page }) => {
  await page.goto('index.php?action=guestbook');
  const body = page.locator('body');

  await expect(body).toContainText('Neuen Gästebucheintrag schreiben');
  await expect(body).toContainText('Prüf-Code');
});

test('Das Bild des Prüf-Codes wird ausgeliefert', async ({ page }) => {
  await page.goto('index.php?action=guestbook');

  const image = page.locator('img[src*="image.php"]').first();
  await expect(image).toHaveCount(1);

  const source = await image.getAttribute('src');
  const response = await page.request.get(new URL(source, page.url()).toString());

  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('image/jpeg');
});

test('Die sprechende Adresse führt zum Gästebuch', async ({ page }) => {
  await page.goto('action/guestbook.html');

  await expect(page).toHaveTitle(/Gästebuch/);
  await expectNoPhpError(page);
});
