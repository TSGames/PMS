/**
 * Erzeugt aus anleitung.html das PDF.
 *
 * Aufruf:  node docs/anleitung/build.js
 * Ergebnis: docs/anleitung/Inhalte-pflegen.pdf
 *
 * Die Bilder daneben stammen aus dem laufenden Backend; aufgenommen
 * werden sie mit
 *
 *   cd tests/e2e && npx playwright test --project=screenshots \
 *       screenshots/anleitung.spec.js
 *
 * Gedruckt wird mit demselben Chromium, den auch die Tests benutzen -
 * deshalb liegt hier keine zweite Browser-Abhaengigkeit herum.
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require(path.resolve(__dirname, '../../tests/e2e/node_modules/playwright'));

const HIER = __dirname;
const QUELLE = path.join(HIER, 'anleitung.html');
const ZIEL = path.join(HIER, 'Inhalte-pflegen.pdf');

/** Chromium finden - wie in tests/e2e/playwright.config.js. */
function findeChromium() {
    if (process.env.PMS_CHROMIUM) {
        return process.env.PMS_CHROMIUM;
    }
    const wurzel = process.env.PLAYWRIGHT_BROWSERS_PATH;
    if (!wurzel || !fs.existsSync(wurzel)) {
        return undefined;
    }
    return fs
        .readdirSync(wurzel)
        .filter((eintrag) => eintrag.startsWith('chromium-'))
        .map((eintrag) => path.join(wurzel, eintrag, 'chrome-linux/chrome'))
        .find((datei) => fs.existsSync(datei));
}

(async () => {
    const browser = await chromium.launch({ executablePath: findeChromium() });
    const seite = await browser.newPage();
    await seite.goto('file://' + QUELLE, { waitUntil: 'networkidle' });

    const titel = path.join(HIER, '.titel.pdf');
    const rumpf = path.join(HIER, '.rumpf.pdf');

    // Die Titelseite randlos und ohne Seitenzahl
    await seite.evaluate(() => {
        document.documentElement.classList.add('nur-titel');
        document.body.classList.add('nur-titel');
    });
    await seite.pdf({
        path: titel,
        format: 'A4',
        printBackground: true,
        margin: { top: '0', bottom: '0', left: '0', right: '0' },
    });

    // Der Rumpf mit Seitenzahlen, beginnend bei 1
    await seite.evaluate(() => {
        document.documentElement.classList.remove('nur-titel');
        document.body.classList.remove('nur-titel');
        document.body.classList.add('ohne-titel');
    });
    await seite.pdf({
        path: rumpf,
        format: 'A4',
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: '<div></div>',
        footerTemplate: `
            <div style="width:100%;font-family:Arial,sans-serif;font-size:9pt;
                        color:#43514a;text-align:center;padding-bottom:6mm;">
                <span class="pageNumber"></span>
            </div>`,
        margin: { top: '18mm', bottom: '16mm', left: '18mm', right: '18mm' },
    });

    await browser.close();
    zusammenlegen(titel, rumpf, ZIEL);

    const groesse = (fs.statSync(ZIEL).size / 1024 / 1024).toFixed(2);
    process.stdout.write(`${ZIEL} (${groesse} MB)\n`);
})();

/**
 * Legt Titelseite und Rumpf zu einer Datei zusammen.
 *
 * Gemacht wird das von pymupdf (pip install pymupdf). Fehlt es, bleiben
 * die beiden Teile liegen und das Skript sagt, was zu tun ist.
 */
function zusammenlegen(titel, rumpf, ziel) {
    const { execFileSync } = require('child_process');
    const skript = `
import sys, pymupdf
# Nur die erste Seite der Titeldatei: Rundet Chromium die Hoehe um einen
# Bildpunkt auf, entsteht dahinter eine leere zweite Seite.
ziel = pymupdf.open(sys.argv[1])
ziel.select([0])
ziel.insert_pdf(pymupdf.open(sys.argv[2]))
ziel.set_metadata({
    "title": "Inhalte pflegen auf der Chorseite",
    "subject": "Anleitung fuer die Inhaltspflege",
    "creator": "PMS",
})
ziel.save(sys.argv[3])
`;
    try {
        execFileSync('python3', ['-c', skript, titel, rumpf, ziel], { stdio: 'pipe' });
        fs.unlinkSync(titel);
        fs.unlinkSync(rumpf);
    } catch (fehler) {
        process.stderr.write(
            'Zusammenlegen fehlgeschlagen. Fehlt pymupdf?  pip install pymupdf\n'
            + `Die Teile liegen als ${titel} und ${rumpf}.\n`
        );
        throw fehler;
    }
}
