/**
 * Startseite des Backends.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError } = require('../lib/admin');

test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test('Kennzahlen nennen die Zahlen der Website', async ({ page }) => {
  const cards = page.locator('.stat');
  await expect(cards).toHaveCount(4);

  // Die Zahlen kommen aus den Testdaten und ändern sich, wenn dort etwas
  // dazukommt. Geprüft wird die Form, nicht der Stand.
  const inhalte = page.locator('.stat', { hasText: 'Inhalte' });
  await expect(inhalte.locator('.stat-value')).toHaveText(/^\d+$/);
  await expect(inhalte).toContainText(/davon \d+ verfügbar/);

  const benutzer = page.locator('.stat', { hasText: 'Benutzer' });
  await expect(benutzer.locator('.stat-value')).toHaveText('5');
});

test('Eine Kennzahl führt in ihren Bereich', async ({ page }) => {
  await page.locator('.stat', { hasText: 'Kategorien' }).click();
  await expect(page.locator('.breadcrumb-current')).toHaveText('Kategorien');
});

test('Die jüngsten Ereignisse stehen auf der Startseite', async ({ page }) => {
  const feed = page.locator('.card', { hasText: 'Zuletzt passiert' });
  await expect(feed.locator('.timeline li')).toHaveCount(5);
  await expect(feed).toContainText('Sommerfest 2024');
});

test('Schnellaktionen führen zu den häufigen Aufgaben', async ({ page }) => {
  await expect(page.locator('a:has-text("Inhalt anlegen")')).toBeVisible();
  await expect(page.locator('a:has-text("Sicherung erstellen")')).toBeVisible();

  await page.click('a:has-text("Inhalt anlegen")');
  await expect(page.locator('body')).toContainText('Inhalt erstellen');
});

test('Ohne Sicherung erscheint ein Hinweis', async ({ page }) => {
  await expect(page.locator('.notice-warn')).toContainText('keine Sicherung');
  await expectNoPhpError(page);
});

test('Ein Administrator sieht keine Aktion für Sicherungen', async ({ page, context }) => {
  await context.clearCookies();
  await login(page, 'redakteur');
  await expect(page.locator('a:has-text("Inhalt anlegen")')).toBeVisible();
  await expect(page.locator('a:has-text("Sicherung erstellen")')).toHaveCount(0);
});

test('Die Systemkarte nennt Version und angemeldeten Benutzer', async ({ page }) => {
  const card = page.locator('.card', { hasText: 'PMS-Version' });
  await expect(card).toContainText('PMS-Version');
  await expect(card).toContainText('admin');
});
