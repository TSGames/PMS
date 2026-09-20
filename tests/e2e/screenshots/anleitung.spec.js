/**
 * Nimmt die Bilder für die Anleitung "Inhalte pflegen" auf.
 *
 * Aufruf:  npx playwright test --project=screenshots screenshots/anleitung.spec.js
 * Ergebnis: docs/anleitung/bilder/*.png
 *
 * Die Bilder zeigen das laufende Backend, nicht nachgebaute Zeichnungen -
 * sonst veralten sie bei der ersten Änderung an der Oberfläche, ohne dass
 * es jemand merkt.
 *
 * Wo die Anleitung Schritte durchnummeriert, setzt markiere() dieselben
 * Ziffern als Punkte ins Bild. Sie hängen an echten Elementen, damit sie
 * beim nächsten Umbau der Oberfläche mitwandern.
 */

const fs = require('fs');
const path = require('path');
const { test } = require('@playwright/test');
const { login, resetDatabase } = require('../lib/admin');

const OUT = path.resolve(__dirname, '../../../docs/anleitung/bilder');
const FIXTURES = path.resolve(__dirname, '../fixtures');

test.use({ viewport: { width: 1280, height: 860 } });

test.beforeAll(() => {
  fs.mkdirSync(OUT, { recursive: true });
  // Die Bilder sollen die Ausgangsdaten zeigen und nicht das, was ein
  // frueherer Lauf hinterlassen hat.
  resetDatabase();
});

/** Setzt eine nummerierte Sprechblase an die linke obere Ecke eines Elements. */
async function markiere(page, marken) {
  await page.evaluate((liste) => {
    const stil = document.createElement('style');
    stil.textContent = `
      .doku-marke {
        position: absolute;
        z-index: 99999;
        width: 30px;
        height: 30px;
        margin: -15px 0 0 -15px;
        border-radius: 50%;
        background: #d92b2b;
        color: #fff;
        font: 700 17px/30px "Helvetica Neue", Arial, sans-serif;
        text-align: center;
        box-shadow: 0 0 0 3px #fff;
      }
      .doku-rahmen {
        position: absolute;
        z-index: 99998;
        border: 3px solid #d92b2b;
        border-radius: 8px;
        pointer-events: none;
      }`;
    document.head.appendChild(stil);

    liste.forEach(({ selector, nummer, rahmen, versatz }) => {
      const ziel = document.querySelector(selector);
      if (!ziel) {
        return;
      }
      const box = ziel.getBoundingClientRect();
      const oben = box.top + window.scrollY;
      const links = box.left + window.scrollX;

      if (rahmen) {
        const kasten = document.createElement('div');
        kasten.className = 'doku-rahmen';
        kasten.style.top = oben - 5 + 'px';
        kasten.style.left = links - 5 + 'px';
        kasten.style.width = box.width + 10 + 'px';
        kasten.style.height = box.height + 10 + 'px';
        document.body.appendChild(kasten);
      }

      const marke = document.createElement('div');
      marke.className = 'doku-marke';
      marke.textContent = String(nummer);
      const [dy, dx] = versatz || [0, 0];
      marke.style.top = oben + dy + 'px';
      marke.style.left = links + dx + 'px';
      document.body.appendChild(marke);
    });
  }, marken);
}

/** Speichert einen Ausschnitt oder die ganze Seite. */
async function bild(page, name, selector) {
  await page.waitForLoadState('networkidle').catch(() => {});
  const ziel = { path: path.join(OUT, name + '.png') };
  if (selector) {
    await page.locator(selector).first().screenshot(ziel);
  } else {
    await page.screenshot(ziel);
  }
}

test('01 Anmeldung', async ({ page }) => {
  await page.goto('admin');
  await bild(page, '01-anmelden', '.login-box');
});

test('02 Startseite', async ({ page }) => {
  await login(page, 'admin');
  await markiere(page, [
    { selector: '.admin-sidebar', nummer: 1, versatz: [40, 40] },
    { selector: '.app-search', nummer: 2, rahmen: true },
    { selector: '.user-chip', nummer: 3, rahmen: true },
  ]);
  await bild(page, '02-startseite');
});

test('03 Liste der Inhalte', async ({ page }) => {
  // Breiter als sonst, damit die Spalte "Aktionen" mit aufs Bild kommt
  await page.setViewportSize({ width: 1600, height: 860 });
  await login(page, 'admin');
  await page.goto('admin/inhalte');
  await markiere(page, [
    { selector: '#listing-search', nummer: 1, rahmen: true },
    { selector: 'table.data-table tbody tr:first-child .cell-title', nummer: 2, versatz: [14, 0] },
    { selector: 'table.data-table tbody tr:first-child .sort-cell', nummer: 3, rahmen: true },
    { selector: 'table.data-table tbody tr:first-child .cell-actions', nummer: 4, rahmen: true },
  ]);
  await bild(page, '03-liste');
});

test('04 Suche in der Liste', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?q=Sommerfest');
  await bild(page, '04-suche', '.table-wrap');
});

test('05 Editor oben', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  await bild(page, '05-editor-einordnung', '.form-section');
});

test('06 Titel und Text', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  await markiere(page, [
    { selector: 'input[name="name"]', nummer: 1, rahmen: true },
    { selector: 'textarea[name="description"]', nummer: 2, rahmen: true },
    { selector: 'textarea[name="content"]', nummer: 3, rahmen: true },
  ]);
  await bild(page, '06-text', '.form-section:nth-of-type(2)');
});

test('07 Editor mit Formatierung', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=1');
  await page.waitForSelector('.tox-tinymce', { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(1500);
  await bild(page, '07-editor', '.tox-tinymce');
});

test('08 Bild', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  const abschnitt = page.locator('.form-section').filter({ hasText: 'Bild hochladen' }).last();
  await abschnitt.scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);
  await abschnitt.screenshot({ path: path.join(OUT, '08-bild.png') });
});

test('08b Bild einfuegen: der Dialog', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');

  // Zwei Bilder in den Bestand legen, damit das Raster nicht leer ist
  for (const datei of ['chorprobe.png', 'konzert.png']) {
    await page.setInputFiles('#image_upload_picker', path.join(FIXTURES, datei));
    await page.waitForTimeout(800);
  }

  await page.locator('button:has-text("Bild in den Text einfügen")').click();
  await page.waitForTimeout(400);
  await page.locator('.image-tile-button').first().click();
  await page.waitForTimeout(200);

  await markiere(page, [
    { selector: '.dialog-upload button:nth-of-type(1)', nummer: 1, rahmen: true },
    { selector: '.dialog-upload button:nth-of-type(2)', nummer: 2, rahmen: true },
    { selector: '#drop_zone', nummer: 3, rahmen: true },
    { selector: '.image-grid', nummer: 4, versatz: [18, 18] },
    { selector: '.dialog-size', nummer: 5, rahmen: true },
  ]);
  await bild(page, '16-dialog', '.dialog');
});

test('08c Zuschneiden', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  await page.locator('button:has-text("Bild in den Text einfügen")').click();
  await page.waitForTimeout(400);

  await page.setInputFiles('#image_file_picker', path.join(FIXTURES, 'chorprobe.png'));
  await page.waitForSelector('.crop-size-presets', { timeout: 15000 });
  await page.waitForTimeout(600);

  await markiere(page, [
    { selector: '.crop-canvas-container', nummer: 1, versatz: [22, 22] },
    { selector: '.crop-size-presets', nummer: 2, versatz: [0, 14] },
    { selector: '.crop-info', nummer: 3, versatz: [0, 14] },
  ]);
  await bild(page, '17-zuschneiden', '.crop-modal-container');
});

test('09 Veröffentlichung', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  const veroeffentlichung = page.locator('.form-section').filter({ hasText: 'Sortierung' }).last();
  await veroeffentlichung.scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);
  await veroeffentlichung.screenshot({ path: path.join(OUT, '09-veroeffentlichung.png') });
});

test('10 Speichern', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/inhalte?edit=2&editor=0');
  await page.locator('input[name="item_step2"]').first().scrollIntoViewIfNeeded();
  await markiere(page, [
    { selector: '#item_button1', nummer: 1, rahmen: true },
    { selector: '#item_button2', nummer: 2, rahmen: true },
  ]);
  await page.waitForTimeout(300);
  await bild(page, '10-speichern', '.form-actions');
});

test('12 Hilfe am Feld', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/einstellungen');
  const schalter = page.locator('.field-help-toggle').first();
  if (await schalter.count()) {
    await schalter.click();
    await page.waitForTimeout(300);
  }
  await bild(page, '12-hilfe', '.form-section');
});

test('13 Frühere Fassungen', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/sicherungen');
  await page.click('input[name="backup"]');
  await page.waitForLoadState('domcontentloaded');

  await page.goto('admin/inhalte?edit=2&editor=0');
  await page.fill('#name', 'Sommerfest 2024 (überarbeitet)');
  await page.locator('input[name="item_step2"]').first().click();
  await page.waitForLoadState('domcontentloaded');

  await page.goto('admin/inhalte/versionen?item=2');
  const auf = page.locator('.version-diff summary').first();
  if (await auf.count()) {
    await auf.click();
    await page.waitForTimeout(200);
  }
  await bild(page, '13-fassungen', '.timeline');
});

test('15 Auf der Website bearbeiten', async ({ page }) => {
  await page.goto('index.php');
  await page.fill('#login_user', 'admin');
  await page.fill('#login_pass', 'admin123');
  await page.click('input[name="user_login"]');
  await page.waitForLoadState('domcontentloaded');
  await page.goto('index.php?item=2&edit=true');
  await bild(page, '15-website');
});
