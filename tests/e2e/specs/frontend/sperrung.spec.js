/**
 * Frontend: gesperrte Adressen.
 *
 * Steht die anfragende Adresse auf der Sperrliste, bekommt der Besucher nur
 * noch die dafür vorgesehene Spezialseite zu sehen - mit Grund und Dauer.
 * Dieser Weg war bisher von keinem Test abgedeckt und brach mit einem
 * fatalen Fehler ab (BEFUNDE B17).
 */

const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { expectNoPhpError, resetDatabase } = require('../../lib/admin');

/** Setzt die Sperrliste auf die Adresse, unter der die Tests anfragen. */
function banOwnAddress(reason, time) {
  execFileSync('php', ['-r', `
    $db = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $db->exec("DELETE FROM bans");
    $stmt = $db->prepare("INSERT INTO bans (ip, reason, time) VALUES (?, ?, ?)");
    $stmt->bindValue(1, "127.0.0.1");
    $stmt->bindValue(2, ${JSON.stringify(reason)});
    $stmt->bindValue(3, ${time});
    $stmt->execute();
  `], { stdio: 'pipe' });
}

test.beforeEach(() => {
  resetDatabase();
});

test.afterAll(() => {
  resetDatabase();
});

test('Eine gesperrte Adresse sieht nur noch die Sperrseite', async ({ page }) => {
  banOwnAddress('Spam im Gästebuch', 0);

  await page.goto('index.php');

  await expect(page).toHaveTitle(/Zugriff gesperrt/);
  await expect(page.locator('body')).toContainText('Ihre IP-Adresse wurde gesperrt');
  await expectNoPhpError(page);
});

test('Auch ein angefragter Inhalt führt zur Sperrseite', async ({ page }) => {
  banOwnAddress('Spam im Gästebuch', 0);

  await page.goto('index.php?item=7');

  await expect(page).toHaveTitle(/Zugriff gesperrt/);
  await expectNoPhpError(page);
});

test('Ohne passenden Eintrag bleibt die Website erreichbar', async ({ page }) => {
  await page.goto('index.php');

  await expect(page).toHaveTitle(/Willkommen/);
  await expectNoPhpError(page);
});
