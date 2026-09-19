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

test('Navigation enthält alle Hauptbereiche, nach Gruppen geordnet', async ({ page }) => {
  await login(page, 'admin');
  const labels = await page.locator('.sidebar-nav .nav-label').allTextContents();
  expect(labels.map((l) => l.trim())).toEqual([
    'Home',
    // Inhalt
    'Inhalte',
    'Variablen',
    'Umfragen',
    // Struktur
    'Menü',
    'Kategorien',
    'Unterkategorien',
    // Benutzer
    'Benutzerverwaltung',
    'Bans/Sperrungen',
    // System
    'Website-Konfigurator',
    'Backup-Manager',
    'Ereignisse',
    'Website-Status',
    // Website
    'Website anzeigen',
    // Module
    'Update',
  ]);
});

test('Navigation zeigt einem Administrator nur die erlaubten Bereiche', async ({ page }) => {
  await login(page, 'redakteur');
  const labels = await page.locator('.sidebar-nav .nav-label').allTextContents();
  const texts = labels.map((l) => l.trim());
  expect(texts).toContain('Inhalte');
  expect(texts).not.toContain('Website-Konfigurator');
  expect(texts).not.toContain('Backup-Manager');
  expect(texts).not.toContain('Bans/Sperrungen');
});

test('Die Kopfleiste zeigt den Pfad zur aktuellen Seite', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/kategorien');
  await expect(page.locator('.breadcrumb-current')).toHaveText('Kategorien');
});

test('Das Benutzermenü lässt sich öffnen und schließen', async ({ page }) => {
  await login(page, 'admin');
  const panel = page.locator('.user-menu-panel');

  await expect(panel).toBeHidden();
  await page.click('.user-chip');
  await expect(panel).toBeVisible();
  await expect(panel).toContainText('Abmelden');

  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
});

test('Aktiver Navigationspunkt wird markiert', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/kategorien');
  await expect(page.locator('.nav-item.active .nav-label')).toHaveText('Kategorien');
});
