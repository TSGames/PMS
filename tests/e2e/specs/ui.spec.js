/**
 * Oberfläche: Farbschema-Umschalter, mobile Navigation, Assets.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase } = require('../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test.describe('Farbschema', () => {
  // Der Umschalter sitzt im Fuß der Seitenleiste und braucht ein hohes Fenster
  test.use({ viewport: { width: 1440, height: 1200 } });

  test('Farbschema lässt sich umschalten und wird gespeichert', async ({ page }) => {
    await login(page, 'admin');

    await page.click('#theme-toggle');
    await expect(page.locator('html')).toHaveClass(/dark/);
    expect(await page.evaluate(() => localStorage.getItem('adminTheme'))).toBe('dark');

    await page.reload();
    await expect(page.locator('html')).toHaveClass(/dark/);

    await page.click('#theme-toggle');
    await expect(page.locator('html')).toHaveClass(/light/);
  });
});

test('Seitenleiste ist auf schmalen Geräten ausklappbar', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page, 'admin');

  const sidebar = page.locator('#admin-sidebar');
  await expect(page.locator('#sidebar-toggle')).toBeVisible();

  await page.click('#sidebar-toggle');
  await expect(sidebar).toHaveClass(/open/);

  await page.click('#sidebar-close');
  await expect(sidebar).not.toHaveClass(/open/);
});

test('Stylesheets und Skripte werden ausgeliefert', async ({ page }) => {
  const failed = [];
  page.on('response', (response) => {
    if (response.status() >= 400 && new URL(response.url()).hostname === '127.0.0.1') {
      failed.push(`${response.status()} ${response.url()}`);
    }
  });

  await login(page, 'admin');
  await page.goto('admin/inhalte');
  expect(failed, `Fehlende Ressourcen: ${failed.join(', ')}`).toEqual([]);
});

test('Tabellen sind in einen scrollbaren Container gehüllt', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/kategorien');
  await expect(page.locator('.table-responsive table.items')).toBeVisible();
});
