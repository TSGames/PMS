#!/usr/bin/env node
/**
 * Erzeugt tests/screenshots/README.md als Übersicht über alle aufgenommenen
 * Screenshots. Wird automatisch von `npm run screenshots` aufgerufen.
 */

const fs = require('fs');
const path = require('path');
const { SCREENS } = require('../lib/screens');

const ROOT = path.resolve(__dirname, '../../screenshots');
const VARIANTS = ['desktop', 'desktop-dark', 'mobile'];

function exists(variant, id) {
  return fs.existsSync(path.join(ROOT, variant, `${id}.png`));
}

const groups = new Map();
for (const screen of SCREENS) {
  if (!groups.has(screen.group)) groups.set(screen.group, []);
  groups.get(screen.group).push(screen);
}

let md = `# Screenshots des Admin-Backends

Aufgenommen mit \`npm run screenshots\` (siehe [tests/README.md](../README.md)).
Sie zeigen den aktuellen Stand der Oberfläche. Der Stand vor dem Refactoring
des Admin-Backends liegt im Commit, der das Testsystem eingeführt hat.

| Variante | Auflösung | Farbschema |
| --- | --- | --- |
| \`desktop\` | 1440x900 | hell |
| \`desktop-dark\` | 1440x900 | dunkel |
| \`mobile\` | 390x844 | hell |

`;

for (const [group, screens] of groups) {
  md += `## ${group}\n\n`;
  md += '| Seite | Adresse | Desktop | Dunkel | Mobil |\n| --- | --- | --- | --- | --- |\n';
  for (const screen of screens) {
    const cells = VARIANTS.map((variant) =>
      exists(variant, screen.id) ? `[PNG](${variant}/${screen.id}.png)` : '–'
    );
    const url = screen.url ? `\`${screen.url}\`` : '–';
    md += `| ${screen.title} | ${url} | ${cells[0]} | ${cells[1]} | ${cells[2]} |\n`;
  }
  md += '\n';
}

const total = VARIANTS.reduce(
  (sum, variant) => sum + SCREENS.filter((s) => exists(variant, s.id)).length,
  0
);
md += `---\n\nInsgesamt ${total} Screenshots zu ${SCREENS.length} Bildschirmen.\n`;

fs.mkdirSync(ROOT, { recursive: true });
fs.writeFileSync(path.join(ROOT, 'README.md'), md);
console.log(`Übersicht geschrieben: ${path.join(ROOT, 'README.md')} (${total} Screenshots)`);
