/**
 * Katalog der Bildschirme des Frontends (index.php).
 *
 * Das Frontend ist nicht Teil des Refactorings des Admin-Backends. Der
 * Katalog hält seinen heutigen Stand fest, damit ein späterer Umbau eine
 * Vergleichsgrundlage hat.
 *
 * Felder wie in lib/screens.js; role bezieht sich auf die Anmeldung im
 * Frontend, nicht auf das Backend.
 */

const FRONTEND_SCREENS = [
  {
    id: 'start',
    group: 'Frontend',
    title: 'Startseite',
    url: 'index.php',
    heading: 'Willkommen',
  },
  {
    id: 'kategorie',
    group: 'Frontend',
    title: 'Kategorie mit Unterkategorien',
    url: 'index.php?cat=1',
    heading: 'Neuigkeiten',
  },
  {
    id: 'unterkategorie',
    group: 'Frontend',
    title: 'Unterkategorie mit Inhalten',
    url: 'index.php?subcat=1',
    heading: 'Sommerfest 2024',
  },
  {
    id: 'inhalt',
    group: 'Frontend',
    title: 'Inhalt mit Kommentaren und Bewertung',
    url: 'index.php?item=2',
    heading: 'Sommerfest 2024',
  },
  {
    id: 'download',
    group: 'Frontend',
    title: 'Download-Inhalt',
    url: 'index.php?item=5',
    heading: 'Aufnahmeantrag',
  },
  {
    id: 'gaestebuch',
    group: 'Frontend',
    title: 'Gästebuch',
    url: 'index.php?action=guestbook',
    heading: 'Gästebuch',
  },
  {
    id: 'suche',
    group: 'Frontend',
    title: 'Suchergebnis',
    url: 'index.php?action=search&query=Sommer',
    heading: 'Sommerfest',
  },
  {
    id: 'suche-zu-kurz',
    group: 'Frontend',
    title: 'Suche mit zu kurzem Begriff',
    url: 'index.php?action=search&query=ab',
    heading: 'mindestens 3 Zeichen',
  },
  {
    id: 'registrieren',
    group: 'Frontend',
    title: 'Registrierung',
    url: 'action/register.html',
    heading: 'Registrier',
  },
  {
    id: 'passwort-vergessen',
    group: 'Frontend',
    title: 'Passwort zurücksetzen',
    url: 'action/password_recover.html',
    heading: 'Passwort',
  },
  {
    id: 'fehlerseite',
    group: 'Frontend',
    title: 'Seite nicht verfügbar',
    url: 'index.php?item=9999',
    heading: 'nicht verfügbar',
  },
];

module.exports = { FRONTEND_SCREENS };
