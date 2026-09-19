/**
 * Frontend: Suche.
 */

const { test, expect } = require('@playwright/test');
const { expectNoPhpError, resetDatabase } = require('../../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test('Die Suche findet einen Inhalt über seinen Titel', async ({ page }) => {
  await page.goto('index.php?action=search&query=Sommer');

  await expect(page.locator('body')).toContainText('Sommerfest 2024');
  await expectNoPhpError(page);
});

test('Die Suche findet einen Inhalt über seinen Text', async ({ page }) => {
  await page.goto('index.php?action=search&query=Beitragsordnung');

  await expect(page.locator('body')).toContainText('Beitragsordnung');
  await expectNoPhpError(page);
});

test('Ein zu kurzer Suchbegriff wird abgewiesen', async ({ page }) => {
  await page.goto('index.php?action=search&query=ab');

  await expect(page.locator('body')).toContainText('mindestens 3 Zeichen');
  await expectNoPhpError(page);
});

test('Das Suchfeld steht auf jeder Seite', async ({ page }) => {
  await page.goto('index.php');

  await expect(page.locator('input[name="search_query"]')).toBeVisible();
});

test('Die Suche über das Feld führt zu einem Ergebnis', async ({ page }) => {
  await page.goto('index.php');
  await page.fill('input[name="search_query"]', 'Satzung');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.press('input[name="search_query"]', 'Enter'),
  ]);

  await expect(page.locator('body')).toContainText('Satzung');
  await expectNoPhpError(page);
});
