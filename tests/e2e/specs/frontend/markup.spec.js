/**
 * Frontend: Das ausgelieferte Markup.
 *
 * Das Kunden-Template musste bisher im Stylesheet ausgleichen, was hier
 * ausgeliefert wurde: Menütabellen auflösen, feste Pixelbreiten
 * überschreiben, Überschriften für die Nebenspalte erfinden. Diese Tests
 * halten fest, dass es das nicht mehr braucht (siehe BACKLOG des
 * Kunden-Templates).
 */

const { test, expect } = require('@playwright/test');
const { expectNoPhpError, resetDatabase } = require('../../lib/admin');

/** Seiten, die stellvertretend für alle Bereiche geprüft werden. */
const SEITEN = [
  ['Startseite', 'index.php'],
  ['Kategorie', 'index.php?cat=1'],
  ['Inhalt mit Kommentaren', 'index.php?item=2&comments=all'],
  ['Gästebuch', 'action/guestbook.html'],
  ['Registrierung', 'action/register.html'],
  ['Suchergebnis', 'index.php?search_query=Konzert'],
];

test.beforeAll(() => {
  resetDatabase();
});

test('Das Menü ist eine Navigationsliste', async ({ page }) => {
  await page.goto('index.php');

  const menu = page.locator('nav.menu');
  await expect(menu).toHaveCount(1);
  await expect(menu.locator('ul.menu_list > li.menu_item').first()).toBeVisible();
  await expect(menu.locator('table')).toHaveCount(0);
});

test('Das Menü markiert die aktuelle Seite', async ({ page }) => {
  await page.goto('index.php?cat=1');

  const aktiv = page.locator('nav.menu a.menu_active');
  await expect(aktiv).toHaveCount(1);
  await expect(aktiv).toHaveAttribute('aria-current', 'page');
  await expect(aktiv).toHaveText('Aktuelles');
});

test('Ein externer Menüpunkt ist ein gültiger Verweis', async ({ page }) => {
  await page.goto('index.php');

  const extern = page.locator('nav.menu a.menu_extern');
  await expect(extern).toHaveCount(1);
  await expect(extern).toHaveAttribute('href', 'https://example.org');
  await expect(extern).toHaveAttribute('rel', 'noopener noreferrer');
  await expect(extern).toHaveText('Partnerseite');
});

test('Die Blöcke der Nebenspalte haben eine Überschrift', async ({ page }) => {
  await page.goto('index.php');

  for (const klasse of ['search_plugin', 'user_counter', 'user_panel']) {
    await expect(page.locator(`section.sidebar_block.${klasse} > h2.sidebar_heading`)).toHaveCount(1);
  }
});

test('Die Seite sagt Telefonen, wie breit sie ist', async ({ page }) => {
  await page.goto('index.php');

  await expect(page.locator('head meta[name="viewport"]')).toHaveAttribute(
    'content',
    /width=device-width/
  );
});

for (const [name, pfad] of SEITEN) {
  test(`${name}: kein <center> und keine festen Pixelbreiten`, async ({ page, request }) => {
    const html = await (await request.get(pfad)).text();

    // Das Mock-Template selbst enthält beides - das sind Kundendaten.
    // Geprüft wird, was PHP hinzufügt, also der Inhalt der Platzhalter.
    await page.goto(pfad);
    const zusammen = await page.evaluate(() =>
      Array.from(document.querySelectorAll('.content_table, .sidebar_block, nav.menu'))
        .map((el) => el.outerHTML)
        .join('')
    );

    expect(zusammen, 'kein <center> mehr').not.toContain('<center>');
    expect(zusammen, 'keine festen Pixelbreiten mehr').not.toMatch(/width="\d+px"/);
    expect(html).toBeTruthy();
    await expectNoPhpError(page);
  });
}
