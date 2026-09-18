/**
 * Website-Konfigurator.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
  await page.goto('admin.php?action=config');
});

test('Formular zeigt die gespeicherten Werte', async ({ page }) => {
  await expect(page.locator('input[name="name"]')).toHaveValue('PMS Mock-Website');
  await expect(page.locator('input[name="mail"]')).toHaveValue('redaktion@example.org');
  await expect(page.locator('input[name="picquali"]')).toHaveValue('85');
  await expect(page.locator('input[name="page_limit"]')).toHaveValue('15');
});

test('Alle Konfigurationsabschnitte sind vorhanden', async ({ page }) => {
  const body = page.locator('body');
  for (const section of [
    'Allgemeines',
    'Modul: Sprachen',
    'Modul: E-Mail Benachrichtigungen',
    'Modul: Menü',
    'Modul: Listenansicht',
    'Modul: Inhaltsansicht',
    'Modul: Kommentare',
    'Modul: Bewertungen',
    'Modul: Besucherzähler',
    'Modul: Downloads',
    'Modul: User-System',
    'Modul: Top-Users',
    'Modul: Gästebuch',
    'Modul: Am Meisten diskutiert',
    'Modul: Aktuelle Kommentare',
  ]) {
    await expect(body).toContainText(section);
  }
});

test('Benachrichtigungstabelle listet Benutzer ab Moderator', async ({ page }) => {
  const body = await page.locator('body').textContent();
  expect(body).toContain('admin (admin@example.org)');
  expect(body).toContain('moderator (moderator@example.org)');
  expect(body).not.toContain('gast (gast@example.org)');
});

test('Sprachauswahl enthält die mitgelieferten Sprachdateien', async ({ page }) => {
  const options = await page.locator('select[name="language"] option').allTextContents();
  expect(options).toContain('german_formal');
  expect(options).toContain('german_informal');
  expect(options).toContain('english');
  expect(options).not.toContain('custom');
});

test('Änderung wird gespeichert', async ({ page }) => {
  await page.fill('input[name="name"]', 'PMS Testsystem');
  await submit(page, 'input[name="config"]');
  await expectNoPhpError(page);

  await page.goto('admin.php?action=config');
  await expect(page.locator('input[name="name"]')).toHaveValue('PMS Testsystem');
  await expect(page.locator('.sidebar-header')).toContainText('PMS Testsystem');
});
