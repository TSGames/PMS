/**
 * Frontend: Jeder Bildschirm des Katalogs wird ohne Fehler ausgeliefert.
 *
 * Gegenstück zu specs/screens.spec.js für das Backend.
 */

const { test, expect } = require('@playwright/test');
const { FRONTEND_SCREENS } = require('../../lib/frontend-screens');
const { collectJsErrors, expectNoPhpError, resetDatabase } = require('../../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

for (const screen of FRONTEND_SCREENS) {
  test(`${screen.title} wird angezeigt`, async ({ page }) => {
    const jsErrors = collectJsErrors(page);

    const response = await page.goto(screen.url);

    expect(response.ok(), `Antwort ${response.status()} für ${screen.url}`).toBeTruthy();
    await expect(page.locator('body')).toContainText(screen.heading);
    await expectNoPhpError(page);
    expect(jsErrors, `JavaScript-Fehler: ${jsErrors.join(' | ')}`).toEqual([]);
  });
}
