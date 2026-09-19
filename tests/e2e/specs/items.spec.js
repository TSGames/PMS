/**
 * Inhalte: Liste, Filter, zweistufiges Bearbeiten-Formular, Kopie, Löschen,
 * Wiederherstellung.
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

test('Liste zeigt die Inhalte der gewählten Kategorie', async ({ page }) => {
  await page.goto('admin/inhalte');
  await expect(page.locator('body')).toContainText('Inhalte');
  await expect(page.locator('body')).toContainText('Sommerfest 2024');
});

test('Filter nach Kategorie und Unterkategorie', async ({ page }) => {
  await page.goto('admin/inhalte');
  await selectFilter(page, 'cat', { label: 'Dokumente' });

  await expect(page.locator('body')).toContainText('Aufnahmeantrag');
  await expect(page.locator('body')).not.toContainText('Sommerfest 2024');
});

test('Bearbeiten öffnet zuerst die Vorauswahl', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2');
  await expect(page.locator('body')).toContainText('Inhalt bearbeiten - Vorauswahl');
  await expect(page.locator('select[name="typ"]')).toHaveValue('1');
  await expect(page.locator('select[name="cat"]')).toHaveValue('1');
  await expect(page.locator('select[name="subcat"]')).toHaveValue('1');
});

test('Vorauswahl führt zum Editor mit den gespeicherten Werten', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await expect(page.locator('input[name="name"]')).toHaveValue('Sommerfest 2024');
  await expect(page.locator('textarea[name="description"]')).toHaveValue('Das Sommerfest findet statt');
  await expect(page.locator('textarea[name="content"]')).toContainText('Sommerfest');
});

test('Inhalt speichern übernimmt die Änderung', async ({ page }) => {
  await page.goto('admin/inhalte?edit=3');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await page.fill('input[name="name"]', 'Neue Öffnungszeiten ab Juli');
  await submit(page, page.locator('input[name="item_step2"]').first());

  await expectNoPhpError(page);
  await page.goto('admin/inhalte');
  await expect(page.locator('body')).toContainText('Neue Öffnungszeiten ab Juli');
});

test('Neuen Inhalt anlegen', async ({ page }) => {
  await page.goto('admin/inhalte?new=yes');
  await page.selectOption('select[name="cat"]', { label: 'Aktuelles' });
  await submit(page, 'input[name="item_refresh"]');
  await page.selectOption('select[name="subcat"]', { label: 'Neuigkeiten' });
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await page.fill('input[name="name"]', 'Testartikel');
  await page.fill('textarea[name="description"]', 'Kurzbeschreibung');
  await page.fill('textarea[name="content"]', '<p>Testinhalt aus dem automatisierten Test.</p>');
  await submit(page, page.locator('input[name="item_step2"]').first());

  await expectNoPhpError(page);
  await page.goto('admin/inhalte');
  await expect(page.locator('body')).toContainText('Testartikel');
});

test('TinyMCE wird geladen, wenn der Editor gewählt ist', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2');
  await page.check('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await expect(page.locator('.tox-tinymce')).toBeVisible({ timeout: 15000 });
});

test('Kopie eines Inhalts erstellen', async ({ page }) => {
  await page.goto('admin/inhalte?do_copy=2');
  await expectNoPhpError(page);
  await page.goto('admin/inhalte');
  const rows = await page.locator('table.data-table').textContent();
  expect(rows.match(/Sommerfest 2024/g).length).toBeGreaterThanOrEqual(2);
});

test('Löschen fragt nach und entfernt den Inhalt', async ({ page }) => {
  await page.goto('admin/inhalte?delete=4');
  await expect(page.locator('body')).toContainText('Jahreshauptversammlung');

  await submit(page, 'input[name="confirm_delete"]');
  await expect(page.locator('body')).toContainText('erfolgreich entfernt');
  await expect(page.locator('table.data-table')).not.toContainText('Jahreshauptversammlung');
});

test('Wiederherstellungsseite ist erreichbar', async ({ page }) => {
  await page.goto('admin/inhalte/wiederherstellen');
  await expect(page.locator('body')).toContainText('Gelöschten Inhalt wiederherstellen');
  await expectNoPhpError(page);
});

test('Versionsverwaltung eines Inhalts ist erreichbar', async ({ page }) => {
  await page.goto('admin/inhalte/versionen?item=2');
  await expect(page.locator('body')).toContainText('Inhalt wiederherstellen');
  await expectNoPhpError(page);
});

test('Vorauswahl bietet für Spezialseiten die Art des Inhalts an', async ({ page }) => {
  await page.goto('admin/inhalte?new=yes');
  await page.selectOption('select[name="typ"]', { label: 'Spezialseite' });
  await submit(page, 'input[name="item_refresh"]');

  await expect(page.locator('body')).toContainText('Art des Spezialinhalts');
  const options = await page.locator('select[name="typ2"] option').allTextContents();
  expect(options).toContain('Startseite');
  expect(options).toContain('Gästebuch');
});

test('Editor zeigt alle Felder eines Standardinhalts', async ({ page }) => {
  await page.goto('admin/inhalte?edit=4');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await expect(page.locator('input[name="name"]')).toBeVisible();
  await expect(page.locator('textarea[name="description"]')).toBeVisible();
  await expect(page.locator('input[name="sort"]')).toBeVisible();
  await expect(page.locator('select[name="user"]')).toBeVisible();
  await expect(page.locator('input[name="available"]')).toBeVisible();
  await expect(page.locator('input[name="visible"]')).toBeVisible();
  await expect(page.locator('input[name="showuser"]')).toBeVisible();
  await expect(page.locator('input[name="rate"]')).toBeVisible();
  await expect(page.locator('input[name="comments"]')).toBeVisible();
  await expect(page.locator('input[name="create_at_use"]')).toBeChecked();
});

test('Editor eines Downloads zeigt das Link-Feld', async ({ page }) => {
  await page.goto('admin/inhalte?edit=5');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await expect(page.locator('input[name="link"]')).toHaveValue('uploads/aufnahmeantrag.pdf');
});

test('Editor einer Spezialseite blendet die Sichtbarkeit aus', async ({ page }) => {
  await page.goto('admin/inhalte?edit=1');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await expect(page.locator('input[name="available"]')).toBeVisible();
  await expect(page.locator('input[name="visible"]')).toHaveCount(0);
});

test('Übernehmen und Schließen kehrt zur Liste zurück', async ({ page }) => {
  await page.goto('admin/inhalte?edit=4');
  await page.uncheck('input[name="tinymce"]');
  await submit(page, 'input[name="item_step1"]');

  await page.fill('input[name="name"]', 'Jahreshauptversammlung 2025');
  await submit(page, page.locator('input[value="Übernehmen & Schließen"]'));

  await expect(page.locator('table.data-table')).toContainText('Jahreshauptversammlung 2025');
});

test('Liste lässt sich nach Unterkategorie filtern', async ({ page }) => {
  await page.goto('admin/inhalte');
  await selectFilter(page, 'cat', { label: 'Aktuelles' });
  await selectFilter(page, 'subcat', { label: 'Termine' });

  await expect(page.locator('table.data-table')).toContainText('Jahreshauptversammlung');
  await expect(page.locator('table.data-table')).not.toContainText('Sommerfest 2024');
});

test('Suche findet einen Inhalt über den Titel', async ({ page }) => {
  await page.goto('admin/inhalte');
  await searchList(page, 'Sommerfest');
  expect(await tableColumn(page, 2)).toEqual(['Sommerfest 2024']);
});

test('Spaltenkopf sortiert die Inhalte nach Namen', async ({ page }) => {
  await page.goto('admin/inhalte');
  await submit(page, 'th a:has-text("Name")');
  const names = await tableColumn(page, 2);
  expect(names).toEqual([...names].sort((a, b) => a.localeCompare(b, 'de')));
});

test('Sortierung der Inhalte lässt sich ändern', async ({ page }) => {
  await page.goto('admin/inhalte');
  const before = await tableColumn(page, 2);
  const row = page.locator('table.data-table tbody tr', { hasText: 'Beitragsordnung' });
  await submit(page, row.locator('a[title="Nach oben"]'));

  const after = await tableColumn(page, 2);
  expect(after).not.toEqual(before);
});

test('Spezialseiten lassen sich nicht kopieren', async ({ page }) => {
  await page.goto('admin/inhalte');
  const row = page.locator('table.data-table tbody tr', { hasText: 'Willkommen' });
  await expect(row.locator('a[title="Kopie erstellen"]')).toHaveCount(0);
});
