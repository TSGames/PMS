/**
 * Sicherheitsverhalten des Backends.
 */

const { test, expect } = require('@playwright/test');
const { login, resetDatabase, submit } = require('../lib/admin');

// Jeder Test startet auf dem Ausgangsdatenbestand
test.beforeEach(async ({ page }) => {
  resetDatabase();
});

test('Ohne Anmeldung werden keine Eingaben verarbeitet', async ({ request }) => {
  // Früher legte diese Anfrage ohne jede Anmeldung eine Sperrung an (B9)
  const response = await request.post('admin.php', {
    form: { id: '0', ip: '203.0.113.99', reason: 'ohne Anmeldung', time: '5', bans: 'Speichern' },
  });
  expect(response.ok()).toBeTruthy();
  expect(await response.text()).toContain('PMS Back End Login');

  const check = await request.post('admin.php', {
    form: { login: 'Einloggen', login_name: 'admin', login_password: 'admin123' },
  });
  expect(check.ok()).toBeTruthy();

  const page = await request.get('admin.php?action=bans');
  expect(await page.text()).not.toContain('203.0.113.99');
});

test('Ohne Anmeldung wird kein Benutzerkonto angelegt', async ({ request }) => {
  await request.post('admin.php', {
    multipart: {
      id: '0',
      name: 'anonuser',
      password: 'geheim123',
      passwordr: 'geheim123',
      mail: 'anon@example.org',
      typ: '3',
      active: '1',
      user: 'Speichern',
    },
  });

  await request.post('admin.php', {
    form: { login: 'Einloggen', login_name: 'admin', login_password: 'admin123' },
  });
  const page = await request.get('admin.php?action=user');
  expect(await page.text()).not.toContain('anonuser');
});

test('Formulare enthalten ein Sicherheitstoken', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin.php?action=bans&new=yes');
  await expect(page.locator('input[name="pms_token"]')).toHaveCount(1);
});

test('Speichern ohne gültiges Token wird abgewiesen', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin.php?action=bans&new=yes');
  await page.fill('input[name="ip"]', '192.0.2.77');
  await page.fill('input[name="time"]', '3');
  await page.evaluate(() => {
    document.querySelector('input[name="pms_token"]').value = 'ungueltig';
  });
  await submit(page, 'input[name="bans"]');

  await expect(page.locator('body')).toContainText('Sitzung ist abgelaufen');
  await page.goto('admin.php?action=bans');
  await expect(page.locator('body')).not.toContainText('192.0.2.77');
});

test('Handler-Dateien sind nicht direkt aufrufbar', async ({ request }) => {
  for (const file of ['backend/bootstrap.php', 'backend/modules.php']) {
    const response = await request.get(file);
    expect(response.status(), file).toBe(403);
  }
});
