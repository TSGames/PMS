/**
 * Nimmt von jedem Bildschirm des Admin-Backends einen Screenshot auf.
 *
 * Aufruf:  npm run screenshots   (im Verzeichnis tests/e2e)
 *
 * Ergebnis: tests/screenshots/<variante>/<screen-id>.png
 *   desktop       1440x900, helles Farbschema
 *   desktop-dark  1440x900, dunkles Farbschema
 *   mobile        390x844, helles Farbschema (nur für Seiten mit wide: true)
 *
 * Die Screenshots dokumentieren den Stand der Oberfläche und werden nach
 * jeder größeren Änderung neu aufgenommen.
 */

const fs = require('fs');
const path = require('path');
const { test } = require('@playwright/test');
const { SCREENS } = require('../lib/screens');
const { login } = require('../lib/admin');

const OUT_ROOT = path.resolve(__dirname, '../../screenshots');

const VARIANTS = [
  // Ab 1300px Breite zeigt admin.css die Seitenleiste dauerhaft an,
  // darunter klappt sie über den Inhalt.
  { name: 'desktop', viewport: { width: 1440, height: 900 }, theme: 'light' },
  { name: 'desktop-dark', viewport: { width: 1440, height: 900 }, theme: 'dark' },
  { name: 'mobile', viewport: { width: 390, height: 844 }, theme: 'light', onlyWide: true },
];

for (const variant of VARIANTS) {
  test.describe(`Screenshots: ${variant.name}`, () => {
    test.use({ viewport: variant.viewport });

    const screens = SCREENS.filter((screen) => !variant.onlyWide || screen.wide);

    for (const screen of screens) {
      test(`${screen.group} – ${screen.title}`, async ({ page }) => {
        const dir = path.join(OUT_ROOT, variant.name);
        fs.mkdirSync(dir, { recursive: true });

        await page.addInitScript((theme) => {
          try {
            window.localStorage.setItem('adminTheme', theme);
          } catch (e) {
            /* Speicher nicht verfügbar - Standardfarbschema */
          }
        }, variant.theme);

        if (screen.role) {
          await login(page, screen.role);
        }
        await page.goto(screen.url);
        if (screen.prepare) {
          await screen.prepare(page);
        }
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
