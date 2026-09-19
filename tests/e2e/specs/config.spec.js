/**
 * Website-Konfigurator.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
  await page.goto('admin/einstellungen');
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

test('Reiter zeigen jeweils nur ihre Abschnitte', async ({ page }) => {
  await expect(page.locator('.form-section:has-text("Allgemeines")')).toBeVisible();
  await expect(page.locator('.form-section:has-text("Modul: Kommentare")')).toBeHidden();

  await page.click('.tab:has-text("Mitmachen")');
  await expect(page.locator('.form-section:has-text("Modul: Kommentare")')).toBeVisible();
  await expect(page.locator('.form-section:has-text("Allgemeines")')).toBeHidden();
});

test('Der gewählte Reiter steht in der Adresse', async ({ page }) => {
  await page.click('.tab:has-text("Betrieb")');
  expect(new URL(page.url()).hash).toBe('#betrieb');

  await page.reload();
  await expect(page.locator('.form-section:has-text("Modul: Downloads")')).toBeVisible();
});

test('Die Suche findet eine Einstellung über alle Reiter hinweg', async ({ page }) => {
  await page.fill('#config-search', 'gästebuch');

  await expect(page.locator('.form-section:has-text("Modul: Gästebuch")')).toBeVisible();
  await expect(page.locator('.form-section:has-text("Modul: Downloads")')).toBeHidden();
  await expect(page.locator('.tabs')).toBeHidden();
});

test('Änderung wird gespeichert', async ({ page }) => {
  await page.fill('input[name="name"]', 'PMS Testsystem');
  await submit(page, 'input[name="config"]');
  await expectNoPhpError(page);

  await page.goto('admin/einstellungen');
  await expect(page.locator('input[name="name"]')).toHaveValue('PMS Testsystem');
  await expect(page.locator('.sidebar-header')).toContainText('PMS Testsystem');
});

test('Benachrichtigungen lassen sich je Benutzer setzen und speichern', async ({ page }) => {
  await page.click('.tab:has-text("Benachrichtigungen")');
  const guestbookForRedakteur = page.locator('input[name="user_guestbook[]"][value="2"]');
  await expect(guestbookForRedakteur).not.toBeChecked();

  await guestbookForRedakteur.check();
  await submit(page, 'input[name="config"]');

  await page.goto('admin/einstellungen');
  await expect(page.locator('input[name="user_guestbook[]"][value="2"]')).toBeChecked();
});

test('Spalten der Benachrichtigungstabelle sind beschriftet', async ({ page }) => {
  await page.click('.tab:has-text("Benachrichtigungen")');
  const headers = await page.locator('.confirm_head').allTextContents();
  expect(headers).toEqual(['Benutzer', 'Gästebuch', 'Kommentare', 'Registration']);
});

test('Eine Einstellung erklärt sich auf Wunsch selbst', async ({ page }) => {

  await page.click('.tab:has-text("Betrieb")');
  const zeile = page.locator('.field-row:has(#visitors_lifetime)');
  const erklaerung = zeile.locator('.field-help');

  // Der Text steht im Markup, damit ihn die Suche des Browsers findet und
  // er ohne JavaScript lesbar bleibt - sichtbar wird er erst auf Klick.
  await expect(erklaerung).toHaveCount(1);
  await expect(erklaerung).toBeHidden();

  await zeile.locator('.field-help-toggle').click();

  await expect(erklaerung).toBeVisible();
  await expect(erklaerung).toContainText('Online-Besucherzählung');
  await expect(zeile.locator('.field-help-toggle')).toHaveAttribute('aria-expanded', 'true');
});

test('Jede Einstellung des Konfigurators hat eine Erklärung', async ({ page }) => {

  // Jedes Feld mit Namen gehört zu einer Einstellung; keines soll ohne
  // Erklärung dastehen.
  const zeilen = page.locator('.field-row');
  const ohne = await zeilen.evaluateAll((rows) =>
    rows
      .filter((r) => r.querySelector('input[name], select[name], textarea[name]'))
      // Die Benachrichtigungstabelle trägt Felder mit name="…[]" - das sind
      // Zeilen einer Tabelle, keine einzelnen Einstellungen.
      .filter((r) => !r.querySelector('[name$="[]"]'))
      .filter((r) => !r.querySelector('.field-help'))
      .map((r) => r.querySelector('input[name], select[name], textarea[name]').name)
  );

  expect(ohne, `Einstellungen ohne Erklärung: ${ohne.join(', ')}`).toEqual([]);
});

test('Die Menü-Größe ist aus dem Konfigurator verschwunden', async ({ page }) => {
  // vertical, menu_width und menu_height steuerten Pixelmaße und
  // Ausrichtung des Menüs. Seit Frontend\View\Menu das Markup baut, liest
  // sie niemand mehr.
  for (const feld of ['menu_width', 'menu_height', 'vertical']) {
    await expect(page.locator(`[name="${feld}"]`)).toHaveCount(0);
  }
});
