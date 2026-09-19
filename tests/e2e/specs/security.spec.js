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
  const response = await request.post('admin', {
    form: { id: '0', ip: '203.0.113.99', reason: 'ohne Anmeldung', time: '5', bans: 'Speichern' },
  });
  expect(response.ok()).toBeTruthy();
  expect(await response.text()).toContain('PMS Back End Login');

  const check = await request.post('admin', {
    form: { login: 'Einloggen', login_name: 'admin', login_password: 'admin123' },
  });
  expect(check.ok()).toBeTruthy();

  const page = await request.get('admin/sperrungen');
  expect(await page.text()).not.toContain('203.0.113.99');
});

test('Ohne Anmeldung wird kein Benutzerkonto angelegt', async ({ request }) => {
  await request.post('admin', {
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

  await request.post('admin', {
    form: { login: 'Einloggen', login_name: 'admin', login_password: 'admin123' },
  });
  const page = await request.get('admin/benutzer');
  expect(await page.text()).not.toContain('anonuser');
});

test('Formulare enthalten ein Sicherheitstoken', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/sperrungen?new=yes');
  await expect(page.locator('input[name="pms_token"]')).toHaveCount(1);
});

test('Speichern ohne gültiges Token wird abgewiesen', async ({ page }) => {
  await login(page, 'admin');
  await page.goto('admin/sperrungen?new=yes');
  await page.fill('input[name="ip"]', '192.0.2.77');
  await page.fill('input[name="time"]', '3');
  await page.evaluate(() => {
    document.querySelector('input[name="pms_token"]').value = 'ungueltig';
  });
  await submit(page, 'input[name="bans"]');

  await expect(page.locator('body')).toContainText('Sitzung ist abgelaufen');
  await page.goto('admin/sperrungen');
  await expect(page.locator('body')).not.toContainText('192.0.2.77');
});

test('Handler-Dateien sind nicht direkt aufrufbar', async ({ request }) => {
  for (const file of ['backend/bootstrap.php', 'backend/modules.php']) {
    const response = await request.get(file);
    expect(response.status(), file).toBe(403);
  }
});

test('Frühere Adressen leiten auf die neuen Pfade um', async ({ request }) => {
  const cases = [
    ['admin.php?action=cat', '/admin/kategorien'],
    ['admin.php?action=item&edit=2', '/admin/inhalte?edit=2'],
    ['admin.php', '/admin'],
  ];

  for (const [from, to] of cases) {
    const response = await request.get(from, { maxRedirects: 0 });
    expect(response.status(), from).toBe(301);
    expect(response.headers()['location'], from).toBe(to);
  }
});

test('Die JSON-Schnittstellen antworten mit JSON, nicht mit der Startseite', async ({ page }) => {
  await login(page, 'admin');

  // Auch über die frühere Adresse admin.php?action=... - dort meldet sich
  // der Zuschneide-Dialog (crop_modal.js) an
  const result = await page.evaluate(async () => {
    const body = new FormData();
    body.append('action', 'crop_image_ajax');
    body.append('pms_token', window.PMS_TOKEN || '');
    const response = await fetch('admin.php', { method: 'POST', body, credentials: 'same-origin' });
    return { type: response.headers.get('content-type'), text: (await response.text()).slice(0, 200) };
  });

  expect(result.type).toContain('application/json');
  expect(result.text).not.toContain('<!DOCTYPE');
});

test('Die Bild-Schnittstelle weist eine Anfrage ohne Anmeldung ab', async ({ page, context }) => {
  await context.clearCookies();
  const response = await page.request.get('admin/api/bilder?do=list');
  expect(response.status()).toBe(401);
});

test('Die Bild-Schnittstelle weist Schreibzugriffe ohne Token ab', async ({ page }) => {
  await login(page, 'admin');
  const result = await page.evaluate(async () => {
    const body = new FormData();
    body.append('do', 'delete');
    body.append('name', 'beliebig.png');
    const response = await fetch('admin/api/bilder', { method: 'POST', body, credentials: 'same-origin' });
    return response.status;
  });
  expect(result).toBe(403);
});
