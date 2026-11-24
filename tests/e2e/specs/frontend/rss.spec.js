/**
 * Frontend: RSS-Feed (rss.php).
 *
 * rss.php rief mysql_query()/mysql_fetch_object() - Funktionen, die es
 * unter PHP 8 nicht mehr gibt. Jeder Feed-Aufruf endete deshalb in einem
 * fatalen Fehler (BEFUNDE B27).
 */

const { test, expect } = require('@playwright/test');
const { resetDatabase } = require('../../lib/admin');

test.beforeAll(() => {
  resetDatabase();
});

test('B27: Der Feed einer Kategorie liefert gültiges RSS', async ({ request }) => {
  const antwort = await request.get('rss.php?cat=1');
  expect(antwort.status()).toBe(200);
  const xml = await antwort.text();
  expect(xml).toContain('<rss version="2.0">');
  expect(xml).toContain('<item>');
  expect(xml).not.toContain('mysql_query');
  expect(xml).not.toContain('Fatal error');
});

test('B27: Der Feed einer Unterkategorie und der sprechende Pfad funktionieren', async ({ request }) => {
  for (const url of ['rss.php?subcat=1', 'rss.php?follow=Verein-1c']) {
    const antwort = await request.get(url);
    expect(antwort.status(), url).toBe(200);
    expect(await antwort.text(), url).toContain('<rss version="2.0">');
  }
});
