/**
 * Kategorien und Unterkategorien: Liste, Anlegen, Bearbeiten, Sortieren, Löschen.
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

test.describe('Kategorien', () => {
  test('Liste zeigt die Mock-Kategorien in Sortierreihenfolge', async ({ page }) => {
    await page.goto('admin/kategorien');
    expect(await tableColumn(page, 2)).toEqual([
      'Aktuelles',
      'Dokumente',
      'Verein',
      'Archiv',
    ]);
  });

  test('Verfügbarkeit wird als Markierung ausgegeben', async ({ page }) => {
    await page.goto('admin/kategorien');
    const row = page.locator('table.data-table tbody tr', { hasText: 'Archiv' });
    await expect(row.locator('td').nth(3)).toHaveText('Versteckt');
  });

  test('Suche schränkt die Liste ein', async ({ page }) => {
    await page.goto('admin/kategorien');
    await searchList(page, 'Verein');
    expect(await tableColumn(page, 2)).toEqual(['Verein']);
  });

  test('Filter zeigt nur versteckte Kategorien', async ({ page }) => {
    await page.goto('admin/kategorien');
    await selectFilter(page, 'available', { label: 'Nur versteckte' });
    expect(await tableColumn(page, 2)).toEqual(['Archiv']);
  });

  test('Spaltenkopf sortiert die Liste', async ({ page }) => {
    await page.goto('admin/kategorien');
    await submit(page, 'th a:has-text("Name")');
    expect(await tableColumn(page, 2)).toEqual(['Aktuelles', 'Archiv', 'Dokumente', 'Verein']);

    await submit(page, 'th a:has-text("Name")');
    expect(await tableColumn(page, 2)).toEqual(['Verein', 'Dokumente', 'Archiv', 'Aktuelles']);
  });

  test('Neue Kategorie anlegen', async ({ page }) => {
    await page.goto('admin/kategorien?new=yes');
    await page.fill('input[name="name"]', 'Testkategorie');
    await page.fill('input[name="sort"]', '15');
    await page.check('input[name="available"]');
    await submit(page, 'input[name="cat"]');

    await expectNoPhpError(page);
    await page.goto('admin/kategorien');
    await expect(page.locator('table.data-table')).toContainText('Testkategorie');
  });

  test('Kategorie bearbeiten', async ({ page }) => {
    await page.goto('admin/kategorien?edit=2');
    await expect(page.locator('input[name="name"]')).toHaveValue('Dokumente');
    await page.fill('input[name="name"]', 'Dokumente (geändert)');
    await submit(page, 'input[name="cat"]');

    await page.goto('admin/kategorien');
    await expect(page.locator('table.data-table')).toContainText('Dokumente (geändert)');
  });

  test('Kategorie löschen erfordert Bestätigung', async ({ page }) => {
    await page.goto('admin/kategorien?delete=3');
    await expect(page.locator('body')).toContainText('Löschen von Kategorie bestätigen');
    await expect(page.locator('body')).toContainText('ALLE EINTRÄGE UND UNTERKATEGORIEN ENTFERNT');

    // Ohne Bestätigung bleibt die Kategorie erhalten
    await page.goto('admin/kategorien');
    await expect(page.locator('table.data-table')).toContainText('Verein');
  });

  test('Bestätigtes Löschen entfernt die Kategorie', async ({ page }) => {
    await page.goto('admin/kategorien?delete=3');
    await submit(page, 'input[name="confirm_delete"]');

    await page.goto('admin/kategorien');
    await expect(page.locator('table.data-table')).not.toContainText('Verein');
  });

  test('Sortierung lässt sich über die Pfeile ändern', async ({ page }) => {
    await page.goto('admin/kategorien');
    const before = await tableColumn(page, 2);
    expect(before[0]).toBe('Aktuelles');

    await submit(
      page,
      page.locator('table.data-table tbody tr', { hasText: 'Dokumente' }).locator('a[title="Nach oben"]')
    );

    const after = await tableColumn(page, 2);
    expect(after).not.toEqual(before);
    expect(after[0]).toBe('Dokumente');
  });
});

test.describe('Unterkategorien', () => {
  test('Liste zeigt die Mock-Unterkategorien', async ({ page }) => {
    await page.goto('admin/unterkategorien');
    await expect(page.locator('body')).toContainText('Neuigkeiten');
    await expect(page.locator('body')).toContainText('Formulare');
  });

  test('Filter nach Kategorie schränkt die Liste ein', async ({ page }) => {
    await page.goto('admin/unterkategorien');
    await selectFilter(page, 'cat', { label: 'Dokumente' });

    await expect(page.locator('body')).toContainText('Formulare');
    await expect(page.locator('table.data-table')).not.toContainText('Neuigkeiten');
  });

  test('Neue Unterkategorie anlegen', async ({ page }) => {
    await page.goto('admin/unterkategorien?new=yes');
    await page.fill('input[name="name"]', 'Testunterkategorie');
    await page.fill('textarea[name="description"]', 'Beschreibung aus dem Test');
    await page.fill('input[name="sort"]', '99');
    await page.check('input[name="available"]');
    await submit(page, 'input[name="subcat"]');

    await expectNoPhpError(page);
    await page.goto('admin/unterkategorien');
    await expect(page.locator('body')).toContainText('Testunterkategorie');
  });

  test('Unterkategorie bearbeiten', async ({ page }) => {
    await page.goto('admin/unterkategorien?edit=2');
    await expect(page.locator('input[name="name"]')).toHaveValue('Termine');
    await page.fill('input[name="name"]', 'Termine 2025');
    await submit(page, 'input[name="subcat"]');

    await page.goto('admin/unterkategorien');
    await expect(page.locator('body')).toContainText('Termine 2025');
  });
});
