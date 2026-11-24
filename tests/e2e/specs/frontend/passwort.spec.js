/**
 * Frontend: Passwort-Zurücksetzen und Konto-Freischaltung.
 *
 * Beide Links benutzten früher einen aus öffentlichen Daten ableitbaren
 * Schlüssel: md5(Benutzername) beim Zurücksetzen (B28), den
 * Registrierungs-Zeitstempel bei der Freischaltung (B29). Ein Gast konnte
 * damit jedes Konto zurücksetzen bzw. das eigene ohne Mail freischalten.
 */

const { test, expect } = require('@playwright/test');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const { resetDatabase } = require('../../lib/admin');


function sql(query) {
  return execFileSync('php', ['-r', `
    $d = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $r = $d->querySingle($argv[1]);
    echo $r === null ? "" : $r;
  `, query], { encoding: 'utf8' });
}

// website_key ist im Mock leer (src/config.php); HMAC wie in functions_user.php
function hmac(message) {
  return crypto.createHmac('sha256', '').update(message).digest('hex');
}

test.beforeEach(() => {
  resetDatabase();
});

test('B28: Der md5(Name)-Link setzt kein Passwort mehr zurück', async ({ request }) => {
  const name = sql('SELECT name FROM user WHERE id = 1');
  const before = sql('SELECT password FROM user WHERE id = 1');
  const md5 = crypto.createHash('md5').update(name).digest('hex');

  await request.get(`index.php?action=password_recover&user=1&id=${md5}`);
  expect(sql('SELECT password FROM user WHERE id = 1')).toBe(before);

  // Auch der Type-Juggling-Versuch id=0 darf nichts bewirken
  await request.get('index.php?action=password_recover&user=1&id=0');
  expect(sql('SELECT password FROM user WHERE id = 1')).toBe(before);
});

test('B28: Nur das echte Token setzt das Passwort zurück', async ({ request }) => {
  const before = sql('SELECT password FROM user WHERE id = 1');
  const token = hmac(`recover|1|${before}`);

  await request.get(`index.php?action=password_recover&user=1&id=${token}`);
  expect(sql('SELECT password FROM user WHERE id = 1')).not.toBe(before);
});

test('B29: Der Registrierungs-Zeitstempel schaltet kein Konto frei', async ({ request }) => {
  execFileSync('php', ['-r', `
    $d = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $d->exec("INSERT INTO user (name,password,mail,typ,register,active) VALUES ('neu','x','neu@example.org',0,1700000000,0)");
  `]);
  const id = sql("SELECT id FROM user WHERE name = 'neu'");

  await request.get(`index.php?action=register_finish&id=${id}&key=1700000000`);
  expect(sql(`SELECT active FROM user WHERE id = ${id}`)).toBe('0');

  const token = hmac(`activate|${id}|1700000000|x`);
  await request.get(`index.php?action=register_finish&id=${id}&key=${token}`);
  expect(sql(`SELECT active FROM user WHERE id = ${id}`)).toBe('1');
});
