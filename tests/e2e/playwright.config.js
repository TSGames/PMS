const { defineConfig } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const HOST = process.env.PMS_MOCK_HOST || '127.0.0.1';
const PORT = process.env.PMS_MOCK_PORT || '8099';
const BASE_URL = `http://${HOST}:${PORT}/`;
const REPO_ROOT = path.resolve(__dirname, '../..');
const CONF_DIR = path.join(REPO_ROOT, 'tests/.runtime/conf.d');

/**
 * Chromium des Systems finden. In Umgebungen mit vorinstallierten Browsern
 * (PLAYWRIGHT_BROWSERS_PATH) passt die Revision nicht immer zur
 * Playwright-Version; dann wird der vorhandene Build direkt verwendet.
 */
function findChromium() {
  if (process.env.PMS_CHROMIUM) return process.env.PMS_CHROMIUM;
  const root = process.env.PLAYWRIGHT_BROWSERS_PATH;
  if (!root || !fs.existsSync(root)) return undefined;
  const candidates = fs
    .readdirSync(root)
    .filter((entry) => entry.startsWith('chromium-'))
    .map((entry) => path.join(root, entry, 'chrome-linux/chrome'))
    .filter((binary) => fs.existsSync(binary));
  return candidates[0];
}

const executablePath = findChromium();

module.exports = defineConfig({
  testDir: __dirname,
  timeout: 30000,
  expect: { timeout: 7000 },
  fullyParallel: false,
  workers: 1,
  reporter: process.env.CI ? [['github'], ['list']] : [['list']],
  use: {
    baseURL: BASE_URL,
    launchOptions: executablePath ? { executablePath } : {},
    // Ab 1300px zeigt admin.css die Seitenleiste dauerhaft an
    viewport: { width: 1440, height: 900 },
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  projects: [
    {
      name: 'tests',
      testMatch: /specs\/.*\.spec\.js/,
    },
    {
      name: 'screenshots',
      testMatch: /screenshots\/.*\.spec\.js/,
    },
  ],
  webServer: {
    command: `PHP_INI_SCAN_DIR=${CONF_DIR} php -S ${HOST}:${PORT} -t ${REPO_ROOT}/src`,
    url: `${BASE_URL}admin.php`,
    reuseExistingServer: true,
    timeout: 20000,
    stdout: 'ignore',
    stderr: 'pipe',
  },
});
