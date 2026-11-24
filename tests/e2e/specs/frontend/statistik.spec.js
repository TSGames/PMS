/**
 * Frontend: Besucherzahlen als XML (stats_xml.php).
 *
 * Der Vergleich lief über "==": War kein Passwort eingestellt, bekam jeder
 * die Zahlen, auch ohne ?pw (BEFUNDE B31).
 */

const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { resetDatabase } = require('../../lib/admin');

function setPassword(value) {
  execFileSync('php', ['-r', `
    $d = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $s = $d->prepare("UPDATE config SET visitors_password = :p");
    $s->bindValue(":p", $argv[1]);
    $s->execute();
  `, value]);
}

test.beforeEach(() => {
  resetDatabase();
});

test('B31: Mit dem richtigen Passwort kommen die Zahlen', async ({ request }) => {
  const xml = await (await request.get('stats_xml.php?pw=statistik')).text();
  expect(xml).toContain('<counter>');
  expect(xml).not.toContain('Passwort ung');
});

test('B31: Ohne oder mit falschem Passwort kommen keine Zahlen', async ({ request }) => {
  for (const adresse of ['stats_xml.php', 'stats_xml.php?pw=falsch']) {
    expect(await (await request.get(adresse)).text(), adresse).toContain('Passwort ung');
  }
});

test('B31: Ist kein Passwort eingestellt, bleibt der Zugriff zu', async ({ request }) => {
  setPassword('');
  for (const adresse of ['stats_xml.php', 'stats_xml.php?pw=']) {
    expect(await (await request.get(adresse)).text(), adresse).toContain('Passwort ung');
  }
});
