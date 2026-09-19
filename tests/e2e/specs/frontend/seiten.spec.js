/**
 * Frontend: Startseite, Navigation, Kategorien und Inhalte.
 *
 * Das Frontend (index.php) ist nicht Teil des Backend-Refactorings. Diese
 * Tests halten fest, wie es sich heute verhält, damit ein späterer Umbau
 * abgesichert ist.
 */

const { test, expect } = require('@playwright/test');
const { expectNoPhpError, resetDatabase } = require('../../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test('Die Startseite zeigt den Spezialinhalt "Startseite"', async ({ page }) => {
  await page.goto('index.php');

  await expect(page).toHaveTitle(/PMS Mock-Website/);
  await expect(page.locator('body')).toContainText('Willkommen');
  await expectNoPhpError(page);
});

test('Das Menü führt die sichtbaren Einträge', async ({ page }) => {
  await page.goto('index.php');
  const body = page.locator('body');

  for (const entry of ['Startseite', 'Aktuelles', 'Termine', 'Satzung', 'Gästebuch', 'Registrieren']) {
    await expect(body).toContainText(entry);
  }
  // "Intern" ist im Mock-Bestand unsichtbar geschaltet
  await expect(body).not.toContainText('Intern');
});

test('Eine Kategorie listet ihre Unterkategorien', async ({ page }) => {
  await page.goto('index.php?cat=1');

  await expect(page).toHaveTitle(/Aktuelles/);
  await expect(page.locator('body')).toContainText('Neuigkeiten');
  await expect(page.locator('body')).toContainText('Termine');
  await expectNoPhpError(page);
});

test('Eine Unterkategorie listet ihre Inhalte', async ({ page }) => {
  await page.goto('index.php?subcat=1');

  await expect(page).toHaveTitle(/Neuigkeiten/);
  await expect(page.locator('body')).toContainText('Sommerfest 2024');
  await expectNoPhpError(page);
});

test('Ein Inhalt zeigt Titel, Autor und Text', async ({ page }) => {
  await page.goto('index.php?item=2');

  await expect(page).toHaveTitle(/Sommerfest 2024/);
  const body = page.locator('body');
  await expect(body).toContainText('Geschrieben von redakteur');
  await expect(body).toContainText('Am 21. Juni feiern wir das Sommerfest.');
  await expectNoPhpError(page);
});

test('Ein Inhalt zeigt seine Kommentare', async ({ page }) => {
  await page.goto('index.php?item=2');

  await expect(page.locator('body')).toContainText('Freue mich');
  await expect(page.locator('body')).toContainText('Rückfrage');
});

test('Ein Inhalt zeigt seine Bewertung', async ({ page }) => {
  await page.goto('index.php?item=2');

  await expect(page.locator('body')).toContainText('Bewertungen');
});

test('Ein nicht verfügbarer Inhalt führt zur Fehlerseite', async ({ page }) => {
  await page.goto('index.php?item=9');

  await expect(page.locator('body')).toContainText('nicht verfügbar');
  await expectNoPhpError(page);
});

test('Ein unbekannter Inhalt führt zur Fehlerseite', async ({ page }) => {
  await page.goto('index.php?item=9999');

  await expect(page.locator('body')).toContainText('nicht verfügbar');
  await expectNoPhpError(page);
});

test('Die Brotkrume nennt den Weg zur Seite', async ({ page }) => {
  await page.goto('index.php?cat=1');

  await expect(page.locator('body')).toContainText('Sie sind hier: Home -> Aktuelles');
});

test('Sprechende Adressen führen zum selben Inhalt', async ({ page }) => {
  await page.goto('content/Satzung-7.html');

  await expect(page).toHaveTitle(/Satzung/);
  await expectNoPhpError(page);
});

test('Die Umfrage steht mit ihren Antworten in der Seitenleiste', async ({ page }) => {
  await page.goto('index.php');
  const body = page.locator('body');

  await expect(body).toContainText('Wie gefällt Ihnen die neue Website?');
  await expect(body).toContainText('Sehr gut');
  await expect(page.locator('input[name="answer"]').first()).toBeVisible();
});

test('Die Besucherzahlen stehen in der Seitenleiste', async ({ page }) => {
  await page.goto('index.php');

  await expect(page.locator('body')).toContainText('Besucher Gesamt');
  await expect(page.locator('body')).toContainText('Anzahl Artikel: 12');
});
