<?php
/**
 * Gemeinsame Pfade und Einstellungen des Mock-Systems.
 *
 * Über Umgebungsvariablen anpassbar:
 *   PMS_MOCK_HOST  (Standard 127.0.0.1)
 *   PMS_MOCK_PORT  (Standard 8099)
 *   PMS_DB_DIR     (Standard /var/db       - im Anwendungscode fest verdrahtet)
 *   PMS_TEMPLATE_DIR (Standard /var/template - im Anwendungscode fest verdrahtet)
 */

define('PMS_REPO_ROOT', dirname(__DIR__, 2));
define('PMS_SRC_DIR', PMS_REPO_ROOT . '/src');
define('PMS_RUNTIME_DIR', PMS_REPO_ROOT . '/tests/.runtime');
define('PMS_DB_DIR', getenv('PMS_DB_DIR') ?: '/var/db');
define('PMS_TEMPLATE_DIR', getenv('PMS_TEMPLATE_DIR') ?: '/var/template');
define('PMS_DB_FILE', PMS_DB_DIR . '/default.sqlite');
define('PMS_HOST', getenv('PMS_MOCK_HOST') ?: '127.0.0.1');
define('PMS_PORT', getenv('PMS_MOCK_PORT') ?: '8099');
