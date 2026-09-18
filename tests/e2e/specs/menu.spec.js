/**
 * Menüverwaltung: Liste, Formulare, Anlegen, Ändern, Löschen.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, expectNoPhpError, submit, tableColumn } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test('Liste zeigt die Menüeinträge in Sortierreihenfolge', async ({ page }) => {
  await page.goto('admin.php?action=menu');
  expect(await tableColumn(page, 1, 'Name')).toEqual([
    'Startseite',
    'Aktuelles',
    'Termine',
    'Satzung',
    'Gästebuch',
    'Registrieren',
    'Partnerseite',
    'Administration',
    'Intern',
  ]);
});

test('Liste benennt die Art der Verlinkung', async ({ page }) => {
  await page.goto('admin.php?action=menu');
  const row = page.locator('table.items tr', { hasText: 'Partnerseite' });
  await expect(row).toContainText('Link');
});

test('Unsichtbare Einträge werden gekennzeichnet', async ({ page }) => {
  await page.goto('admin.php?action=menu');
  const row = page.locator('table.items tr', { hasText: 'Intern' });
  await expect(row.locator('td').nth(3)).toHaveText('Nein');
});

test('Menüeintrag bearbeiten zeigt die gespeicherten Werte', async ({ page }) => {
  await page.goto('admin.php?action=menu&edit=2');
  await expect(page.locator('input[name="name"]')).toHaveValue('Aktuelles');
  await expect(page.locator('input[name="sort"]')).toHaveValue('20');
  await expect(page.locator('input[name="typ"][value="0"]')).toBeChecked();
  await expectNoPhpError(page);
});

test('Plugin-Eintrag bearbeiten wählt den Plugin-Typ', async ({ page }) => {
  await page.goto('admin.php?action=menu&edit=5');
  await expect(page.locator('input[name="name"]')).toHaveValue('Gästebuch');
  await expect(page.locator('input[name="typ"][value="1"]')).toBeChecked();
});

test('Link-Eintrag bearbeiten zeigt den Link-Code', async ({ page }) => {
  await page.goto('admin.php?action=menu&edit=7');
  await expect(page.locator('input[name="typ"][value="2"]')).toBeChecked();
  await expect(page.locator('textarea[name="extern"]')).toContainText('example.org');
});

test('Unsichtbarer Menüeintrag ist im Formular nicht angehakt', async ({ page }) => {
  await page.goto('admin.php?action=menu&edit=9');
  await expect(page.locator('input[name="visible"]')).not.toBeChecked();
});

test('Neues Menü-Formular ist vorbelegt', async ({ page }) => {
  await page.goto('admin.php?action=menu&new=yes');
  await expect(page.locator('input[name="sort"]')).toHaveValue('1000');
  await expect(page.locator('input[name="visible"]')).toBeChecked();
  await expect(page.locator('input[name="typ"][value="0"]')).toBeChecked();
});

test('Neuen Menüeintrag als Link anlegen', async ({ page }) => {
  await page.goto('admin.php?action=menu&new=yes');
  await page.fill('input[name="name"]', 'Testlink');
  await page.fill('input[name="sort"]', '95');
  await page.check('input[name="typ"][value="2"]');
  await page.fill('textarea[name="extern"]', '<a href="https://test.example.org">Test</a>');
  await page.check('input[name="visible"]');
  await submit(page, 'input[name="menu"]');

  await expectNoPhpError(page);
  await page.goto('admin.php?action=menu');
  await expect(page.locator('table.items')).toContainText('Testlink');
});

test('Menüeintrag umbenennen', async ({ page }) => {
  await page.goto('admin.php?action=menu&edit=3');
  await page.fill('input[name="name"]', 'Veranstaltungen');
  await submit(page, 'input[name="menu"]');

  await page.goto('admin.php?action=menu');
  await expect(page.locator('table.items')).toContainText('Veranstaltungen');
});

test('Menüeintrag löschen fragt nach und entfernt ihn', async ({ page }) => {
  await page.goto('admin.php?action=menu&delete=9');
  await expect(page.locator('body')).toContainText('Intern');
  await submit(page, 'input[name="confirm_delete"]');

  await page.goto('admin.php?action=menu');
  expect(await tableColumn(page, 1, 'Name')).not.toContain('Intern');
});

test('Kategorieauswahl aktualisiert die Unterkategorien', async ({ page }) => {
  await page.goto('admin.php?action=menu&new=yes');
  await page.selectOption('select[name="cat"]', { label: 'Dokumente' });
  await submit(page, 'input[name="menu_refresh"]');

  const options = await page.locator('select[name="subcat"] option').allTextContents();
  expect(options).toContain('Formulare');
  expect(options).not.toContain('Neuigkeiten');
});
