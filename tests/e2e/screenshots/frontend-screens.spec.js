/**
 * Nimmt von jedem Bildschirm des Frontends einen Screenshot auf.
 *
 * Aufruf:  npm run screenshots   (im Verzeichnis tests/e2e)
 * Ergebnis: tests/screenshots/frontend/<variante>/<screen-id>.png
 *
 * Das Frontend kennt kein Farbschema-Umschalten, deshalb gibt es nur die
 * beiden Auflösungen.
 */

const fs = require('fs');
const path = require('path');
const { test } = require('@playwright/test');
const { FRONTEND_SCREENS } = require('../lib/frontend-screens');

const OUT_ROOT = path.resolve(__dirname, '../../screenshots/frontend');

const VARIANTS = [
  { name: 'desktop', viewport: { width: 1440, height: 900 } },
  { name: 'mobile', viewport: { width: 390, height: 844 } },
];

for (const variant of VARIANTS) {
  test.describe(`Frontend-Screenshots: ${variant.name}`, () => {
    test.use({ viewport: variant.viewport });

    for (const screen of FRONTEND_SCREENS) {
      test(`${screen.group} – ${screen.title}`, async ({ page }) => {
        const dir = path.join(OUT_ROOT, variant.name);
        fs.mkdirSync(dir, { recursive: true });

        await page.goto(screen.url);
        await page.waitForLoadState('networkidle').catch(() => {});

        await page.screenshot({
          path: path.join(dir, `${screen.id}.png`),
          fullPage: true,
          animations: 'disabled',
        });
      });
    }
  });
}
