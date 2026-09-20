/**
 * Inhalte: Liste, Filter, Editor, Kopie, Löschen, Wiederherstellung.
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

/** Ein gültiges 1x1-PNG für den Bild-Upload. */
const PNG_PIXEL = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64'
);

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

test('Bearbeiten öffnet direkt den Editor mit den gespeicherten Werten', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=0');

  await expect(page.locator('input[name="name"]')).toHaveValue('Sommerfest 2024');
  await expect(page.locator('textarea[name="description"]')).toHaveValue('Das Sommerfest findet statt');
  await expect(page.locator('textarea[name="content"]')).toContainText('Sommerfest');
});

test('Die Einordnung steht im Kopf des Editors und ist änderbar', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=0');

  await expect(page.locator('input[name="typ"][value="0"]')).toBeChecked();
  await expect(page.locator('select[name="cat"]')).toHaveValue('1');
  await expect(page.locator('select[name="subcat"]')).toHaveValue('1');
});

test('Kategoriewechsel im Editor lädt die Unterkategorien nach', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=0');
  await page.selectOption('select[name="cat"]', { label: 'Dokumente' });

  const subcats = page.locator('select[name="subcat"] option');
  await expect(subcats.filter({ hasText: 'Formulare' })).toHaveCount(1);
});

test('Inhalt speichern übernimmt die Änderung', async ({ page }) => {
  await page.goto('admin/inhalte?edit=3&editor=0');

  await page.fill('input[name="name"]', 'Neue Öffnungszeiten ab Juli');
  await submit(page, page.locator('input[name="item_step2"]').first());

  await expectNoPhpError(page);
  await page.goto('admin/inhalte');
  await expect(page.locator('body')).toContainText('Neue Öffnungszeiten ab Juli');
});

test('Neuen Inhalt anlegen', async ({ page }) => {
  await page.goto('admin/inhalte?new=yes&editor=0');
  await page.selectOption('select[name="cat"]', { label: 'Aktuelles' });
  await page.selectOption('select[name="subcat"]', { label: 'Neuigkeiten' });

  await page.fill('input[name="name"]', 'Testartikel');
  await page.fill('textarea[name="description"]', 'Kurzbeschreibung');
  await page.fill('textarea[name="content"]', '<p>Testinhalt aus dem automatisierten Test.</p>');
  await submit(page, page.locator('input[name="item_step2"]').first());

  await expectNoPhpError(page);
  await page.goto('admin/inhalte');
  await expect(page.locator('body')).toContainText('Testartikel');
});

test('TinyMCE wird geladen, wenn der Editor eingeschaltet ist', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=1');
  await expect(page.locator('.tox-tinymce')).toBeVisible({ timeout: 15000 });
});

test('Der grafische Editor lässt sich im Kopf umschalten', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=1');
  await submit(page, 'a:has-text("Grafischen Editor ausschalten")');

  await expect(page.locator('.tox-tinymce')).toHaveCount(0);
  await expect(page.locator('textarea[name="content"]')).toBeVisible();
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
  await expect(page.locator('body')).toContainText('Frühere Fassung einspielen');
  await expectNoPhpError(page);
});

test('Der Bild-Dialog öffnet sich über dem Editor', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=0');
  const dialog = page.locator('.dialog-backdrop');
  await expect(dialog).toBeHidden();

  await page.click('button:has-text("Bild in den Text einfügen")');
  await expect(dialog).toBeVisible();
  await expect(page.locator('.dialog-header')).toContainText('Bild einfügen');

  // Der Editor bleibt stehen, die Seite wird nicht gewechselt
  await expect(page.locator('textarea[name="content"]')).toHaveValue(/Sommerfest/);

  await page.keyboard.press('Escape');
  await expect(dialog).toBeHidden();
});

test('Bild hochladen, einfügen und wieder entfernen', async ({ page }) => {
  await page.goto('admin/inhalte?edit=2&editor=0');
  await page.click('button:has-text("Bild in den Text einfügen")');

  // Ein winziges gültiges PNG genügt, um den Weg zu prüfen
  await page.setInputFiles('#image_upload_picker', {
    name: 'testbild.png',
    mimeType: 'image/png',
    buffer: PNG_PIXEL,
  });

  const tile = page.locator('.image-tile', { hasText: 'testbild' });
  await expect(tile).toHaveCount(1);
  await expect(tile).toHaveClass(/selected/);

  await page.click('.dialog-footer button:has-text("Einfügen")');
  await expect(page.locator('.dialog-backdrop')).toBeHidden();
  await expect(page.locator('textarea[name="content"]')).toHaveValue(/<img src="images\/uploads\/testbild/);

  // Aufräumen, damit der nächste Lauf denselben Ausgangszustand vorfindet
  page.on('dialog', (dialog) => dialog.accept());
  await page.click('button:has-text("Bild in den Text einfügen")');
  await tile.locator('.image-tile-delete').click();
  await expect(tile).toHaveCount(0);
});

test('Spezialseiten zeigen die Art statt Kategorie und Unterkategorie', async ({ page }) => {
  await page.goto('admin/inhalte?new=yes&editor=0');
  await expect(page.locator('select[name="typ2"]')).toBeHidden();

  await page.click('label[for="typ-3"]');

  await expect(page.locator('select[name="typ2"]')).toBeVisible();
  await expect(page.locator('select[name="cat"]')).toBeHidden();
  const options = await page.locator('select[name="typ2"] option').allTextContents();
  expect(options).toContain('Startseite');
  expect(options).toContain('Gästebuch');
});

test('Die Art einer Spezialseite bleibt beim Oeffnen und Speichern erhalten', async ({ page }) => {
  // Alpine schreibt seinen Zustand ins Auswahlfeld, nicht umgekehrt.
  // Fehlt typ2 dort, springt das Feld auf "Bitte wählen" und die Art
  // geht beim Speichern verloren.
  await page.goto('admin/inhalte?edit=11&editor=0');
  await expect(page.locator('select[name="typ2"]')).toHaveValue('3');

  await submit(page, page.locator('input[name="item_step2"]').first());

  await page.goto('admin/inhalte?edit=11&editor=0');
  await expect(page.locator('select[name="typ2"]')).toHaveValue('3');
});

test('Ohne Unterkategorie meldet der Editor den Fehler am Feld', async ({ page }) => {
  await page.goto('admin/inhalte?new=yes&editor=0');
  await page.fill('input[name="name"]', 'Inhalt ohne Einordnung');
  await submit(page, page.locator('input[name="item_step2"]').first());

  await expect(page.locator('.field-error')).toContainText('Unterkategorie');
  await expect(page.locator('input[name="name"]')).toHaveValue('Inhalt ohne Einordnung');
});

test('Editor zeigt alle Felder eines Standardinhalts', async ({ page }) => {
  await page.goto('admin/inhalte?edit=4&editor=0');

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
  await page.goto('admin/inhalte?edit=5&editor=0');
  await expect(page.locator('input[name="link"]')).toHaveValue('uploads/aufnahmeantrag.pdf');
});

test('Editor einer Spezialseite blendet die Sichtbarkeit aus', async ({ page }) => {
  await page.goto('admin/inhalte?edit=1&editor=0');

  await expect(page.locator('input[name="available"]')).toBeVisible();
  await expect(page.locator('input[name="visible"]')).toBeHidden();
});

test('Übernehmen und Schließen kehrt zur Liste zurück', async ({ page }) => {
  await page.goto('admin/inhalte?edit=4&editor=0');

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
