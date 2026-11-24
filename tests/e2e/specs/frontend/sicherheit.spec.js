/**
 * Frontend: Schutz vor fremdausgelösten Anfragen.
 *
 * Bisher erkannte index.php ein abgeschicktes Formular an der Beschriftung
 * seines Schalters und prüfte kein Token. Jede verändernde Aktion ließ sich
 * damit von einer fremden Seite aus auslösen - beim Löschen eines
 * Kommentars genügte ein Verweis.
 */

const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { expectNoPhpError, resetDatabase, USERS } = require('../../lib/admin');

/** Zählt die Kommentare in der Datenbank. */
function commentCount() {
  return Number(execFileSync('php', ['-r', `
    $db = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    echo $db->querySingle("SELECT COUNT(*) FROM comments");
  `], { encoding: 'utf8' }).trim());
}

/** Kennung des jüngsten Kommentars. */
function latestCommentId() {
  return Number(execFileSync('php', ['-r', `
    $db = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    echo $db->querySingle("SELECT id FROM comments ORDER BY id DESC LIMIT 1");
  `], { encoding: 'utf8' }).trim());
}

/** Meldet einen Benutzer über das Anmeldeformular der Website an. */
async function login(page, user) {
  await page.goto('index.php');
  await page.fill('input[name="name"]', user.name);
  await page.fill('input[name="password"]', user.password);
  await page.click('input[name="user_login"]');
}

test.beforeEach(() => {
  resetDatabase();
});

test('Jedes verändernde Formular trägt ein Token', async ({ page }) => {
  await page.goto('index.php');

  const felder = page.locator('form[method="post"] input[name="pms_token"]');
  const formulare = page.locator('form[method="post"]');

  expect(await formulare.count()).toBeGreaterThan(0);
  expect(await felder.count()).toBe(await formulare.count());
});

test('Die Suche braucht kein Token, weil sie nichts verändert', async ({ request }) => {
  const html = await (await request.get('index.php')).text();

  // Im DOM lässt sich das nicht prüfen: Das Suchformular öffnet vor seiner
  // Tabelle und schließt darin, der Browser verschiebt die Grenze deshalb
  // (BEFUNDE B20). Also auf die Auslieferung schauen.
  const suchformular = html.slice(html.indexOf('method="get"'));
  const naechstesEnde = suchformular.indexOf('</form>');

  expect(naechstesEnde).toBeGreaterThan(-1);
  expect(suchformular.slice(0, naechstesEnde)).not.toContain('pms_token');
});

test('Eine Abstimmung ohne Token wird abgewiesen', async ({ page, request }) => {
  const antwort = await request.post('index.php', {
    form: { poll_vote: 'Abstimmen', poll_id: '1', answer: '2' },
  });

  expect(await antwort.text()).toContain('info_error');
});

test('Eine Abstimmung mit Token wird gezählt', async ({ page }) => {
  await page.goto('index.php');
  await page.check('input[name="answer"][value="2"]');
  await page.click('input[name="poll_vote"]');

  // Nach der Stimme zeigt die Umfrage das Ergebnis statt der Auswahl
  await expect(page.locator('[class^="poll_bar"]').first()).toBeVisible();
  await expectNoPhpError(page);
});

test('Ein Kommentar lässt sich nicht über einen fremden Verweis löschen', async ({ page }) => {
  await login(page, USERS.admin);

  const id = latestCommentId();
  const vorher = commentCount();

  // Genau so sah der Verweis bisher aus - ohne Token
  await page.goto(`index.php?item=2&comment=delete&id=${id}`);

  expect(commentCount(), 'der Kommentar darf nicht gelöscht sein').toBe(vorher);
  await expect(page.locator('.info_error')).toBeVisible();
});

test('Über den Verweis der Seite lässt sich ein Kommentar löschen', async ({ page }) => {
  await login(page, USERS.admin);

  const vorher = commentCount();
  await page.goto('index.php?item=2&comments=all');

  // Das Symbolbild fehlt in der Auslieferung, der Verweis hat deshalb keine
  // Ausdehnung (BEFUNDE B21). Geprüft wird, dass er da ist und trägt.
  const loeschen = page.locator('a[href*="comment=delete"]').first();
  await expect(loeschen).toHaveCount(1);

  // Sprechende Adressen entstehen absolut aus config.page (localhost), die
  // Tests laufen gegen 127.0.0.1 - über die Domaingrenze ginge die Sitzung
  // verloren. Deshalb nur Pfad und Abfrage übernehmen.
  const ziel = new URL(await loeschen.getAttribute('href'));
  await page.goto(ziel.pathname.replace(/^\//, '') + ziel.search);

  expect(commentCount()).toBe(vorher - 1);
  await expectNoPhpError(page);
});

test('Die Anmeldung ohne Token wird abgewiesen', async ({ request }) => {
  const antwort = await request.post('index.php', {
    form: { user_login: 'Einloggen', name: USERS.admin.name, password: USERS.admin.password },
  });

  const text = await antwort.text();
  expect(text).toContain('info_error');
  expect(text).not.toContain('Logout');
});

test('Die Anmeldung über das Formular funktioniert weiterhin', async ({ page }) => {
  await login(page, USERS.admin);

  await expect(page.locator('body')).toContainText(/Logout|Abmelden/);
  await expectNoPhpError(page);
});

// B25: index.php wertet den gesamten Inhaltsbereich mit make_dynamic() aus,
// das [php]-Blöcke per eval() ausführt. Besuchereingaben landeten dort
// ungefiltert - ein Suchbegriff genügte, um ohne Anmeldung Code auszuführen.
const PAYLOAD = "[php]echo 'PW'.'NED';[/php]";

/** Setzt die Signatur eines Benutzers direkt in der Datenbank. */
function setSignature(id, text) {
  execFileSync('php', ['-r', `
    $db = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $s = $db->prepare("UPDATE user SET signatur = :s WHERE id = :id");
    $s->bindValue(":s", $argv[1]);
    $s->bindValue(":id", (int)$argv[2]);
    $s->execute();
  `, text, String(id)], { encoding: 'utf8' });
}

test('B25: Ein [php]-Block im Suchbegriff wird nicht ausgeführt', async ({ request }) => {
  const adressen = [
    `index.php?search_query=${encodeURIComponent(PAYLOAD)}`,
    `index.php?action=search&query=${encodeURIComponent(PAYLOAD)}`,
    `content/${encodeURIComponent(PAYLOAD)}.html`,
  ];
  for (const adresse of adressen) {
    const html = await (await request.get(adresse)).text();
    expect(html, adresse).not.toContain('PWNED');
  }
});

test('B25: Der Suchbegriff erscheint unverändert', async ({ page }) => {
  await page.goto('index.php?action=search&query=%5Btest%5D');
  await expect(page.locator('.item_heading').first()).toContainText('[test]');
  await expect(page.locator('input[name="search_query"]')).toHaveValue('[test]');
});

test('B25: Ein [php]-Block in einer gespeicherten Signatur wird nicht ausgeführt', async ({ request }) => {
  setSignature(4, PAYLOAD);
  const html = await (await request.get('index.php?action=user&id=4')).text();
  expect(html).not.toContain('PWNED');
  expect(html).toContain('&#91;php]');
});

test('B25: Ein Redakteur kann weiterhin [php]-Blöcke speichern', async ({ page }) => {
  await login(page, USERS.redakteur);
  await page.goto('index.php?item=1&edit=true');
  const token = await page.locator('input[name="pms_token"]').first().inputValue();

  await page.request.post('index.php?item=1', {
    form: {
      item_edit: '1',
      item_id: '1',
      pms_token: token,
      edit_name: 'Mit Code',
      edit_description: '',
      edit_content: PAYLOAD,
    },
  });

  await page.goto('index.php?item=1');
  await expect(page.locator('body')).toContainText('PWNED');
});

test('B26: Ein [php]-freier poll_id-Wert kann keine SQL-Injection auslösen', async ({ page }) => {
  // poll_id landete roh in einer UPDATE-Abfrage. Eine gültige Abstimmung
  // muss weiter funktionieren, der Angriff darf keine Wirkung haben.
  await page.goto('index.php');
  const token = await page.locator('input[name="pms_token"]').first().inputValue();

  const antwort = await page.request.post('index.php', {
    form: {
      poll_vote: 'Abstimmen',
      poll_id: "1 OR 1=1",
      answer: '2',
      pms_token: token,
    },
  });
  const html = await antwort.text();
  expect(html).not.toContain('SQL');
  expect(html).not.toContain('syntax error');
  // Die Website antwortet weiterhin normal
  expect(html).toContain('</html>');
});

test('B30: Profilfelder und Gastnamen werden als Text ausgegeben, nicht als HTML', async ({ request }) => {
  execFileSync('php', ['-r', `
    $d = new SQLite3(getenv("PMS_MOCK_DB") ?: "/var/db/default.sqlite");
    $s = $d->prepare("UPDATE user SET name = :n, website = :w, signatur = :s WHERE id = 4");
    $s->bindValue(":n", "<img src=x onerror=alert\`1\`>");
    $s->bindValue(":w", "x\\" onmouseover=\\"alert(2)");
    $s->bindValue(":s", "<svg onload=alert(3)>");
    $s->execute();
    $s = $d->prepare("INSERT INTO comments (item,title,comment,name,user,date,mail,ip) VALUES (2,'t','c',:n,0,:d,'','')");
    $s->bindValue(":n", "<svg onload=alert(4)>");
    $s->bindValue(":d", time());
    $s->execute();
  `]);

  for (const adresse of ['index.php?action=user&id=4', 'index.php?item=2&comments=all', 'index.php']) {
    const html = await (await request.get(adresse)).text();
    expect(html, adresse).not.toContain('<img src=x onerror');
    expect(html, adresse).not.toContain('<svg onload');
    expect(html, adresse).not.toContain('" onmouseover="alert');
  }
});
