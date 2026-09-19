/**
 * Variablen (Regeln) und Umfragen.
 */

const { test, expect } = require('@playwright/test');
const { editorValue, expectNoPhpError, fillEditor, login, resetDatabase, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test.describe('Variablen', () => {
  test('Liste zeigt die Mock-Regeln', async ({ page }) => {
    await page.goto('admin/variablen');
    await expect(page.locator('body')).toContainText('#verein');
    await expect(page.locator('body')).toContainText('Mustermann e.V.');
  });

  test('Regel bearbeiten zeigt Suchmuster und Ersetzung', async ({ page }) => {
    await page.goto('admin/variablen?edit=1');
    expect(await editorValue(page, 'search')).toBe('#verein');
    expect(await editorValue(page, 'replace')).toBe('Mustermann e.V.');
  });

  test('Neue Regel anlegen', async ({ page }) => {
    await page.goto('admin/variablen?new=yes');
    await fillEditor(page, 'search', '#testvariable');
    await fillEditor(page, 'replace', 'Ersetzter Text');
    await submit(page, 'input[name="var"]');

    await expectNoPhpError(page);
    await page.goto('admin/variablen');
    await expect(page.locator('body')).toContainText('#testvariable');
  });

  test('Regel mit Zeilenumbruch-Option bearbeiten', async ({ page }) => {
    await page.goto('admin/variablen?edit=2');
    await expect(page.locator('input[name="makebr"]')).toBeChecked();
    await page.uncheck('input[name="makebr"]');
    await submit(page, 'input[name="var"]');

    await page.goto('admin/variablen?edit=2');
    await expect(page.locator('input[name="makebr"]')).not.toBeChecked();
  });
});

test.describe('Umfragen', () => {
  test('Liste zeigt die Mock-Umfragen', async ({ page }) => {
    await page.goto('admin/umfragen');
    await expect(page.locator('body')).toContainText('Wie gefällt Ihnen die neue Website?');
    await expect(page.locator('body')).toContainText('Welches Thema wünschen Sie sich?');
  });

  test('Umfrage bearbeiten zeigt Frage und Antworten', async ({ page }) => {
    await page.goto('admin/umfragen?edit=1');
    await expect(page.locator('input[name="question"]')).toHaveValue('Wie gefällt Ihnen die neue Website?');
    await expect(page.locator('input[name="answer1"]')).toHaveValue('Sehr gut');
    await expect(page.locator('input[name="answer4"]')).toHaveValue('Gar nicht');
    await expect(page.locator('input[name="available"]')).toBeChecked();
  });

  test('Neue Umfrage anlegen', async ({ page }) => {
    await page.goto('admin/umfragen?new=yes');
    await page.fill('input[name="question"]', 'Testfrage?');
    await page.fill('input[name="answer1"]', 'Ja');
    await page.fill('input[name="answer2"]', 'Nein');
    await page.check('input[name="available"]');
    await submit(page, 'input[name="poll"]');

    await expectNoPhpError(page);
    await page.goto('admin/umfragen');
    await expect(page.locator('body')).toContainText('Testfrage?');
  });

  test('Inaktive Umfrage ist nicht als verfügbar markiert', async ({ page }) => {
    await page.goto('admin/umfragen?edit=2');
    await expect(page.locator('input[name="available"]')).not.toBeChecked();
  });
});
