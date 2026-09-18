-- Mock-Daten für das PMS-Testsystem.
--
-- Alle Zeitstempel sind fest verdrahtet (Basis: 2024-06-01 12:00:00 UTC = 1717243200),
-- damit Screenshots und Snapshots zwischen zwei Läufen identisch bleiben.
--
-- Passwörter sind ungesalzene MD5-Hashes (so wie es die Anwendung erwartet):
--   admin / admin123        -> 0192023a7bbd73250516f069df18b500
--   redakteur / admin123
--   moderator / admin123
--   gast / admin123
--
-- Dieses Skript wird von tests/mock/setup.php nach dem Anlegen des Schemas
-- (src/.db_layout.sql) eingespielt.

DELETE FROM bans;
DELETE FROM cat;
DELETE FROM comments;
DELETE FROM config;
DELETE FROM dynamic;
DELETE FROM item;
DELETE FROM menu;
DELETE FROM poll;
DELETE FROM subcat;
DELETE FROM user;
DELETE FROM visitors;
DELETE FROM visitors_counter;

-- ---------------------------------------------------------------------------
-- Globale Konfiguration
-- ---------------------------------------------------------------------------
INSERT INTO config (
  id, name, page, title, mail, rate, comments, commentssmall, numcomments,
  mincomments, numtopuser, picquali, predownload, writtenby, menubreak, vertical,
  menu_width, menu_height, page_limit, list_rows, visitors_day, visitors,
  visitors_today, visitors_yesterday, visitors_increment, visitors_lifetime,
  register_activated, password_recovery_activated, guestbook_activated, editor,
  safemail, topusers, speciallinks, latest_comments_days, latest_comments_chars,
  language, allow_compress, menu_mode, search_list, visitors_password, smileys
) VALUES (
  1, 'PMS Mock-Website', 'http://localhost:8099', 1, 'redaktion@example.org', 1, 1, 1, 10,
  2, 5, 85, 1, 1, 10, 1,
  150, 22, 15, 2, 42, 13370,
  17, 23, 1, 15,
  1, 1, 1, 1,
  1, 1, 1, 7, 120,
  'german_formal', 0, 0, '', 'statistik', 1
);

-- ---------------------------------------------------------------------------
-- Benutzer (typ: 0=Benutzer, 1=Moderator, 2=Administrator, 3=Super-Administrator)
-- ---------------------------------------------------------------------------
INSERT INTO user (id, name, password, mail, showmail, website, signatur, typ, image, register, registerip, login, bday, top, points, mail_guestbook, mail_comments, mail_register, active) VALUES
  (1, 'admin',     '0192023a7bbd73250516f069df18b500', 'admin@example.org',     1, 'https://example.org',      'Super-Administrator der Mock-Website', 3, '', 1704067200, '10.0', 1717243200, 662688000, 1, 420, 1, 1, 1, 1),
  (2, 'redakteur', '0192023a7bbd73250516f069df18b500', 'redakteur@example.org', 1, '',                         'Redaktion',                            2, '', 1706745600, '10.0', 1717156800, 0,         0, 180, 0, 1, 0, 1),
  (3, 'moderator', '0192023a7bbd73250516f069df18b500', 'moderator@example.org', 0, '',                         'Moderation Gästebuch',                 1, '', 1709251200, '10.0', 1716984000, 0,         0,  95, 1, 0, 0, 1),
  (4, 'gast',      '0192023a7bbd73250516f069df18b500', 'gast@example.org',      0, 'https://gast.example.org', '',                                     0, '', 1711929600, '10.1', 1716897600, 0,         0,  12, 0, 0, 0, 1),
  (5, 'gesperrt',  '0192023a7bbd73250516f069df18b500', 'gesperrt@example.org',  0, '',                         'Gesperrtes Konto',                     0, '', 1714521600, '10.2', 0,          0,         0,   0, 0, 0, 0, 0);

-- ---------------------------------------------------------------------------
-- Kategorien
-- ---------------------------------------------------------------------------
INSERT INTO cat (id, name, sort, available, list) VALUES
  (1, 'Aktuelles',   10, 1, ''),
  (2, 'Dokumente',   20, 1, ''),
  (3, 'Verein',      30, 1, ''),
  (4, 'Archiv',      40, 0, '');

-- ---------------------------------------------------------------------------
-- Unterkategorien
-- ---------------------------------------------------------------------------
INSERT INTO subcat (id, cat, name, description, image, sort, available, jump, list) VALUES
  (1, 1, 'Neuigkeiten',   'Meldungen aus dem laufenden Betrieb',  '', 10, 1, 0, ''),
  (2, 1, 'Termine',       'Anstehende Veranstaltungen',           '', 20, 1, 0, ''),
  (3, 2, 'Formulare',     'Formulare zum Herunterladen',          '', 10, 1, 0, ''),
  (4, 2, 'Satzung',       'Satzung und Ordnungen',                '', 20, 1, 1, ''),
  (5, 3, 'Vorstand',      'Der Vorstand stellt sich vor',         '', 10, 1, 0, ''),
  (6, 3, 'Alte Beiträge', 'Nicht mehr öffentlich sichtbar',       '', 20, 0, 0, '');

-- ---------------------------------------------------------------------------
-- Inhalte
-- typ:     0=Standard, 1=News, 2=Download, 3=Spezialseite
-- special: 1=Startseite, 2=Download-Seite, 3=Gesperrter Content, 4=Gästebuch, 5=IP gebannt
-- ---------------------------------------------------------------------------
INSERT INTO item (id, cat, subcat, name, typ, special, showuser, rate, rating, numratings, comments, description, content, image, sort, user, time, time_changed, link, available, visible) VALUES
  (1, 1, 1, 'Willkommen',              3, 1, 1, 0, 0,  0, 0, 'Startseite der Mock-Website', '<h1>Willkommen</h1><p>Dies ist die Startseite des Mock-Systems.</p>',                    '', 10, 1, 1717243200, 1717243200, '', 1, 1),
  (2, 1, 1, 'Sommerfest 2024',         1, 0, 1, 1, 8,  2, 1, 'Das Sommerfest findet statt',  '<p>Am 21. Juni feiern wir das Sommerfest.</p><p>Beginn ist um 18 Uhr.</p>',              '', 20, 2, 1717156800, 1717200000, '', 1, 1),
  (3, 1, 1, 'Neue Öffnungszeiten',     1, 0, 1, 1, 6,  3, 1, 'Ab Juli gelten neue Zeiten',   '<p>Ab dem 1. Juli gelten geänderte Öffnungszeiten.</p>',                                  '', 30, 2, 1717070400, 1717070400, '', 1, 1),
  (4, 1, 2, 'Jahreshauptversammlung',  0, 0, 1, 0, 0,  0, 0, 'Termin der Versammlung',       '<p>Die Jahreshauptversammlung findet am 15. September statt.</p>',                        '', 10, 1, 1716984000, 1716984000, '', 1, 1),
  (5, 2, 3, 'Aufnahmeantrag',          2, 0, 0, 0, 0,  0, 0, 'Antrag als PDF',               '<p>Bitte vollständig ausgefüllt einreichen.</p>',                                         '', 10, 1, 1716897600, 1716897600, 'uploads/aufnahmeantrag.pdf', 1, 1),
  (6, 2, 3, 'Beitragsordnung',         2, 0, 0, 0, 0,  0, 0, 'Beitragsordnung als PDF',      '<p>Gültig ab dem 1. Januar 2024.</p>',                                                    '', 20, 1, 1716811200, 1716811200, 'uploads/beitragsordnung.pdf', 1, 1),
  (7, 2, 4, 'Satzung',                 0, 0, 0, 0, 0,  0, 0, 'Die aktuelle Satzung',         '<p>Die Satzung in der Fassung vom 3. März 2023.</p>',                                     '', 10, 1, 1716724800, 1716724800, '', 1, 1),
  (8, 3, 5, 'Der Vorstand',            0, 0, 1, 0, 0,  0, 1, 'Mitglieder des Vorstands',     '<p>Erster Vorsitzender, zweite Vorsitzende, Kassenwart.</p>',                              '', 10, 1, 1716638400, 1716638400, '', 1, 1),
  (9, 3, 6, 'Rückblick 2019',          0, 0, 1, 0, 0,  0, 0, 'Nicht mehr sichtbar',          '<p>Ein alter Beitrag, der nicht mehr angezeigt wird.</p>',                                 '', 10, 2, 1716552000, 1716552000, '', 0, 0),
  (10, 1, 1, 'Gästebuch',              3, 4, 0, 0, 0,  0, 0, 'Gästebuch der Website',        '<p>Hier können Besucher Einträge hinterlassen.</p>',                                      '', 40, 1, 1716465600, 1716465600, '', 1, 1),
  (11, 1, 1, 'Seite nicht verfügbar',  3, 3, 0, 0, 0,  0, 0, 'Fehlerseite',                  '<p>Dieser Inhalt ist derzeit nicht verfügbar.</p>',                                       '', 50, 1, 1716379200, 1716379200, '', 1, 1),
  (12, 1, 1, 'Zugriff gesperrt',       3, 5, 0, 0, 0,  0, 0, 'Seite für gebannte IPs',       '<p>Ihre IP-Adresse wurde gesperrt.</p>',                                                  '', 60, 1, 1716292800, 1716292800, '', 1, 1);

-- ---------------------------------------------------------------------------
-- Menü (typ: 0=Kategorie/Inhalt, 1=Plugin, 2=Link-Code, 3=Platzhalter)
-- ---------------------------------------------------------------------------
INSERT INTO menu (id, name, sort, typ, cat, subcat, item, usertyp, plugin, extern, visible, popup) VALUES
  (1, 'Startseite',   10, 1, 0, 0, 0,  0, '6', '',                                                      1, 0),
  (2, 'Aktuelles',    20, 0, 1, 0, 0,  0, '0', '',                                                      1, 0),
  (3, 'Termine',      30, 0, 1, 2, 0,  0, '0', '',                                                      1, 0),
  (4, 'Satzung',      40, 0, 2, 4, 7,  0, '0', '',                                                      1, 0),
  (5, 'Gästebuch',    50, 1, 0, 0, 0,  0, '5', '',                                                      1, 0),
  (6, 'Registrieren', 60, 1, 0, 0, 0,  0, '0', '',                                                      1, 0),
  (7, 'Partnerseite', 70, 2, 0, 0, 0,  0, '0', '<a href="https://example.org" target="_blank">Partner</a>', 1, 0),
  (8, 'Administration', 80, 1, 0, 0, 0, 2, '7', '',                                                     1, 0),
  (9, 'Intern',       90, 0, 3, 0, 0,  1, '0', '',                                                      0, 0);

-- ---------------------------------------------------------------------------
-- Variablen (dynamische Platzhalter)
-- ---------------------------------------------------------------------------
INSERT INTO dynamic (id, searcher, replacer, makebr) VALUES
  (1, '#verein',   'Mustermann e.V.',                             0),
  (2, '#kontakt',  'Telefon: 0123 / 456789' || char(10) || 'E-Mail: info@example.org', 1),
  (3, '#hinweis',  '<strong>Bitte beachten Sie unsere Hinweise.</strong>', 0);

-- ---------------------------------------------------------------------------
-- Umfragen
-- ---------------------------------------------------------------------------
INSERT INTO poll (id, question, sort, available, answer1, answers1, answer2, answers2, answer3, answers3, answer4, answers4, answer5, answers5, answer6, answers6, answer7, answers7, answer8, answers8, answer9, answers9, answer10, answers10) VALUES
  (1, 'Wie gefällt Ihnen die neue Website?', 10, 1, 'Sehr gut', 42, 'Gut', 17, 'Geht so', 5, 'Gar nicht', 2, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
  (2, 'Welches Thema wünschen Sie sich?',    20, 0, 'Termine',  12, 'Berichte', 8, 'Bildergalerie', 21, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0);

-- ---------------------------------------------------------------------------
-- Sperrungen (time 0 = dauerhaft, sonst Ablaufzeitpunkt)
-- ---------------------------------------------------------------------------
INSERT INTO bans (id, ip, reason, time) VALUES
  (1, '203.0.113.7',  'Spam im Gästebuch',      0),
  (2, '198.51.100.24','Wiederholte Beleidigung', 4102444800);

-- ---------------------------------------------------------------------------
-- Kommentare
-- ---------------------------------------------------------------------------
INSERT INTO comments (id, item, title, comment, name, user, date, mail, ip) VALUES
  (1, 2, 'Freue mich',     'Ich bin auf jeden Fall dabei!',            'gast',      4, 1717200000, 'gast@example.org', '10.1'),
  (2, 2, 'Rückfrage',      'Gibt es auch etwas für Kinder?',           'moderator', 3, 1717203600, 'moderator@example.org', '10.0'),
  (3, 3, 'Danke',          'Danke für die Information.',               'gast',      4, 1717120000, 'gast@example.org', '10.1'),
  (4, 10,'Schöne Seite',   'Weiter so!',                               'Besucher',  0, 1717050000, 'besucher@example.org', '203.0.113.9');

-- ---------------------------------------------------------------------------
-- Besucher / Zähler (für "Website-Status")
-- ---------------------------------------------------------------------------
INSERT INTO visitors (id, ip) VALUES
  (1, '10.0'),
  (2, '10.1');

INSERT INTO visitors_counter (id, browser, user, typ, content, time) VALUES
  ('mock-session-0001', 'Mozilla/5.0 (X11; Linux x86_64) Firefox/126.0', 1, 1, 1, 1717243100),
  ('mock-session-0002', 'Mozilla/5.0 (Windows NT 10.0) Chrome/125.0',    4, 0, 2, 1717243000),
  ('mock-session-0003', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5)',      0, 0, 3, 1717242900);
