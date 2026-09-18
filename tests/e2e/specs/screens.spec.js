/**
 * Smoke-Test über alle Bildschirme des Admin-Backends.
 *
 * Jeder Screen aus dem Katalog muss sich öffnen lassen, die erwartete
 * Überschrift zeigen und darf keine PHP-Fehlermeldung ausgeben.
 */

const { test, expect } = require('@playwright/test');
const { SCREENS } = require('../lib/screens');
const { login, expectNoPhpError, resetDatabase, collectJsErrors } = require('../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

for (const screen of SCREENS) {
  test(`${screen.group} – ${screen.title} wird angezeigt`, async ({ page }) => {
    const jsErrors = collectJsErrors(page);

    if (screen.role) {
      await login(page, screen.role);
    }
    await page.goto(screen.url);
    if (screen.prepare) {
      await screen.prepare(page);
    }

    await expect(page.locator('body')).toContainText(screen.heading);
    await expectNoPhpError(page);
    expect(jsErrors, `JavaScript-Fehler: ${jsErrors.join(' | ')}`).toEqual([]);
  });
}

test('Seitentitel enthält den Website-Namen', async ({ page }) => {
  await login(page, 'admin');
  await expect(page).toHaveTitle(/PMS Administration \(BackEnd\) - PMS Mock-Website/);
});

test('Navigation enthält alle Hauptbereiche', async ({ page }) => {
  await login(page, 'admin');
  const labels = await page.locator('.sidebar-nav .nav-label').allTextContents();
  expect(labels.map((l) => l.trim())).toEqual([
    'Home',
    'Website-Konfigurator',
    'Menü',
    'Benutzerverwaltung',
    'Kategorien',
    'Unterkategorien',
    'Inhalte',
    'Variablen',
    'Umfragen',
    'Bans/Sperrungen',
    'Ereignisse',
    'Backup-Manager',
    'Website-Status',
    'Website anzeigen',
    'Update',
  ]);
});

test('Aktiver Navigationspunkt wird markiert', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin.php?action=cat');
  await expect(page.locator('.nav-item.active .nav-label')).toHaveText('Kategorien');
});
