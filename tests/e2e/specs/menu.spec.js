/**
 * Menüverwaltung: Liste, Formulare, Anlegen, Ändern, Löschen.
 */

const { test, expect } = require('@playwright/test');
const {
  login,
  resetDatabase,
  expectNoPhpError,
  searchList,
  selectFilter,
  submit,
  tableColumn,
} = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
  await login(page, 'admin');
});

test('Liste zeigt die Menüeinträge in Sortierreihenfolge', async ({ page }) => {
  await page.goto('admin/menue');
  expect(await tableColumn(page, 1)).toEqual([
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
  await page.goto('admin/menue');
  const row = page.locator('table.data-table tbody tr', { hasText: 'Partnerseite' });
  await expect(row).toContainText('Link');
});

test('Unsichtbare Einträge werden gekennzeichnet', async ({ page }) => {
  await page.goto('admin/menue');
  const row = page.locator('table.data-table tbody tr', { hasText: 'Intern' });
  await expect(row.locator('td').nth(3)).toHaveText('Verborgen');
});

test('Filter nach Verweistyp schränkt die Liste ein', async ({ page }) => {
  await page.goto('admin/menue');
  await selectFilter(page, 'typ', { label: 'Link-Code' });
  expect(await tableColumn(page, 1)).toEqual(['Partnerseite']);
});

test('Suche findet einen Menüeintrag', async ({ page }) => {
  await page.goto('admin/menue');
  await searchList(page, 'Satzung');
  expect(await tableColumn(page, 1)).toEqual(['Satzung']);
});

test('Menüeintrag bearbeiten zeigt die gespeicherten Werte', async ({ page }) => {
  await page.goto('admin/menue?edit=2');
  await expect(page.locator('input[name="name"]')).toHaveValue('Aktuelles');
  await expect(page.locator('input[name="sort"]')).toHaveValue('20');
  await expect(page.locator('input[name="typ"][value="0"]')).toBeChecked();
  await expectNoPhpError(page);
});

test('Plugin-Eintrag bearbeiten wählt den Plugin-Typ', async ({ page }) => {
  await page.goto('admin/menue?edit=5');
  await expect(page.locator('input[name="name"]')).toHaveValue('Gästebuch');
  await expect(page.locator('input[name="typ"][value="1"]')).toBeChecked();
});

test('Link-Eintrag bearbeiten zeigt den Link-Code', async ({ page }) => {
  await page.goto('admin/menue?edit=7');
  await expect(page.locator('input[name="typ"][value="2"]')).toBeChecked();
  await expect(page.locator('textarea[name="extern"]')).toContainText('example.org');
});

test('Unsichtbarer Menüeintrag ist im Formular nicht angehakt', async ({ page }) => {
  await page.goto('admin/menue?edit=9');
  await expect(page.locator('input[name="visible"]')).not.toBeChecked();
});

test('Neues Menü-Formular ist vorbelegt', async ({ page }) => {
  await page.goto('admin/menue?new=yes');
  await expect(page.locator('input[name="sort"]')).toHaveValue('1000');
  await expect(page.locator('input[name="visible"]')).toBeChecked();
  await expect(page.locator('input[name="typ"][value="0"]')).toBeChecked();
});

test('Neuen Menüeintrag als Link anlegen', async ({ page }) => {
  await page.goto('admin/menue?new=yes');
  await page.fill('input[name="name"]', 'Testlink');
  await page.fill('input[name="sort"]', '95');
  await page.click('label[for="typ-2"]');
  await page.fill('textarea[name="extern"]', '<a href="https://test.example.org">Test</a>');
  await page.check('input[name="visible"]');
  await submit(page, 'input[name="menu"]');

  await expectNoPhpError(page);
  await page.goto('admin/menue');
  await expect(page.locator('table.data-table')).toContainText('Testlink');
});

test('Menüeintrag umbenennen', async ({ page }) => {
  await page.goto('admin/menue?edit=3');
  await page.fill('input[name="name"]', 'Veranstaltungen');
  await submit(page, 'input[name="menu"]');

  await page.goto('admin/menue');
  await expect(page.locator('table.data-table')).toContainText('Veranstaltungen');
});

test('Menüeintrag löschen fragt nach und entfernt ihn', async ({ page }) => {
  await page.goto('admin/menue?delete=9');
  await expect(page.locator('body')).toContainText('Intern');
  await submit(page, 'input[name="confirm_delete"]');

  await page.goto('admin/menue');
  expect(await tableColumn(page, 1)).not.toContain('Intern');
});

test('Kategorieauswahl lädt die Unterkategorien nach', async ({ page }) => {
  await page.goto('admin/menue?new=yes');
  await page.selectOption('select[name="cat"]', { label: 'Dokumente' });

  const subcats = page.locator('select[name="subcat"] option');
  await expect(subcats.filter({ hasText: 'Formulare' })).toHaveCount(1);
  await expect(subcats.filter({ hasText: 'Neuigkeiten' })).toHaveCount(0);
});

test('Unterkategorie lädt die Inhalte nach', async ({ page }) => {
  await page.goto('admin/menue?new=yes');
  await page.selectOption('select[name="cat"]', { label: 'Dokumente' });
  await page.selectOption('select[name="subcat"]', { label: 'Formulare' });

  const items = page.locator('select[name="item"] option');
  await expect(items.filter({ hasText: 'Aufnahmeantrag' })).toHaveCount(1);
});

test('Nur die Felder des gewählten Verweistyps sind sichtbar', async ({ page }) => {
  await page.goto('admin/menue?new=yes');
  await expect(page.locator('select[name="cat"]')).toBeVisible();
  await expect(page.locator('textarea[name="extern"]')).toBeHidden();

  await page.click('label[for="typ-2"]');
  await expect(page.locator('textarea[name="extern"]')).toBeVisible();
  await expect(page.locator('select[name="cat"]')).toBeHidden();
});
