# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PMS is a German-language PHP content management system (categories → subcategories → content items, users, comments, polls, menus, a template engine). It ships as a Docker image (`php:8.4-apache`). The codebase is mid-refactor: `src/backend/` (the admin area) has been rewritten with modern patterns; the frontend (`index.php` and the `functions_*.php` files it shares with the backend) is legacy procedural code not yet touched by that refactor. Know which world you're in before changing something — the rules below differ sharply between them.

## Commands

```bash
# PHP dependencies + static analysis + unit tests
composer install
vendor/bin/psalm                              # static analysis (errorLevel 5)
vendor/bin/phpunit                             # unit tests (src/backend/ only)
vendor/bin/phpunit tests/unit/SomeTest.php     # single test file
vendor/bin/phpunit --filter testMethodName     # single test method

# JS dependencies vendored locally (no CDN, ever — see "No CDN" below)
npm install
npm run vendor          # builds src/js/vendor/{editor,quill}.js + copies CSS

# Local mock system (SQLite DB, template, PHP dev server)
php tests/mock/setup.php               # (re)creates DB + mock data; --keep-db to preserve DB
php src/cron.php --force               # send the weekly report now (needs the mock DB)
tests/mock/server.sh start|stop|status|logs   # http://127.0.0.1:8099/admin

# End-to-end tests (Playwright, drives the mock server)
cd tests/e2e && npm install
npm test                                       # everything
npx playwright test --project=tests specs/items.spec.js   # single spec
npm run screenshots                            # regenerate tests/screenshots/
```

Full setup/architecture detail for tests lives in `tests/README.md` — read it before writing new tests. `src/backend/README.md` is the canonical architecture doc for the backend — read it before touching `src/backend/`.

## Architecture

### Two codebases in one repo

- **`src/backend/`** (namespace `Pms\Backend\*`) — the admin area, entry point `src/admin.php`. Fully refactored: one `Controller` per section, `Http\Router`/`Http\Routes` for dispatch, `View\Layout`/`View\Components`/`View\Form` for output. Every controller follows the same shape (process input → handle confirmations → render); see `src/backend/README.md` for the exact pattern, the CSRF/delete-confirmation/redirect rules, and how to add a new section.
- **`src/lib/`** (namespace `Pms\*`, no `Backend`) — shared between frontend and backend: `Support\` (Request, Auth, Csrf, Html, Listing, Errors, …) and `Data\` (Db, EventFeed). Dependency direction is one-way: `Pms\Backend\*` may use `Pms\Support`/`Pms\Data`, never the reverse.
- **`src/index.php`** + **`src/functions_*.php`** — the frontend (public site) and code shared with it. This is old-style procedural code with global state (`$pms_db_connection`, globals like `$cat`/`$subcat`/`$item`), not part of the backend refactor. `tests/e2e/specs/frontend/` pins its current behavior so future changes don't regress it silently — it is not a spec of how things *should* work.
- Both entry points (`admin.php`, `index.php`) define `PMS_BACKEND`/`PMS_FRONTEND` and then load `src/bootstrap.php`, which registers the Composer autoloader. Nothing else may include `bootstrap.php`.

### Database is SQLite, not MySQL — despite the API

`pms_db_class` (in `functions_global.php`) wraps `SQLite3` behind a mysqli-shaped API (`$pms_db_connection`, `mysqli_fetch_object()`-style calls appear in stored content and legacy code). One `.sqlite` file per site under `/var/db`, schema in `src/.db_layout.sql`. Legacy code builds SQL with `make_sql($table, $exp, ...)` (string-concatenated WHERE clauses — callers are responsible for safety). New backend code goes through `Data\Db` instead, which binds parameters; never introduce new string-concatenated SQL.

### The graphical content editor: two engines, switchable per session

`src/functions_editor.php` + `Pms\Support\Editor` pick between **Quill** (default — small, touch-friendly) and **TinyMCE** (optional — has tables/media embedding Quill doesn't). The session key `editorengine` (`quill`|`tinymce`) is toggled via `?editorengine=`, independent of the on/off flag (`richeditor` session key, `?editor=0|1`). `get_editor()` dispatches to `get_quill()` or `get_tinymce()`. Anything that inserts content into "whichever editor is active" (image dialog, crop modal, xlsx import) must check `window.tinyMCE.activeEditor` first, then `window.PMS_ACTIVE_EDITOR` (Quill) — see `src/js/admin-image-dialog.js` for the pattern. Quill's init must wait for `DOMContentLoaded`: its script sits in `<head>`, but the textarea it attaches to is in the body (TinyMCE handles this internally; Quill doesn't).

### No CDN, ever

Everything the browser loads ships with the project — the backend must work with zero internet access. JS dependencies are pinned in the root `package.json`, built by esbuild from `build/*.js` into `src/js/vendor/*.js` (+ matching CSS under `src/css/`), and the **built output is committed to git** (so Docker builds don't need npm). TinyMCE is the one exception: it comes via Composer (`tinymce/tinymce`) and is copied into the webroot by the Dockerfile / symlinked by `tests/mock/setup.php` for local dev. When adding a JS dependency, add a `build/<name>.js` entry file, a `vendor:<name>` npm script, wire it into `npm run vendor`, and commit the built files.

### Schema changes reach running installs only through `init()`

There is no migration system. `entrypoint.sh` runs `init.php` → `pms_db_class::init()` on every container start; it creates the full schema from `.db_layout.sql` only for a brand-new DB. For an **existing** DB, a table added later must also be created with `CREATE TABLE IF NOT EXISTS` in the `else` branch of `init()` (see `item_views`, `push_subscriptions`, `weekly_reports`) — adding it to `.db_layout.sql` alone never reaches deployed sites.

### PWA, push and scheduled tasks

- Backend is an installable PWA: `manifest.php` (dynamic, respects subdirectory installs), `sw.js` (scope `/admin` — without trailing slash, otherwise the manifest's `start_url` isn't covered), `js/admin-pwa-install.js` (install banner). App icons live in `src/app-icons/`, **never `/icons/`**: Debian's Apache in `php:*-apache` aliases `/icons/` to its own directory, so files there 404 in production while working fine on the PHP dev server.
- Web Push (weekly report) uses `minishlink/web-push`: `Backend\Push\PushService` (VAPID keys generated once into `/var/db/vapid.json` — regenerating invalidates every subscription), `Http\PushEndpoint`, `js/admin-push.js`, push handlers in `sw.js`.
- The container has no cron: `entrypoint.sh` loops `php cron.php` every 15 minutes; `cron.php` decides itself whether something is due (`Report\WeeklyReport::isDue()`: Mondays from 08:00 Europe/Berlin, once per week). `php src/cron.php --force` sends last week's report immediately. On the CLI `SCRIPT_NAME` is a filesystem path, so don't build URLs with `Routes::path()` there.
- `item_views` logs every frontend content view (filled in `counter.php`); `visitors_counter` is only "who's online now" and gets pruned — don't use it for history.

### Routing

`.htaccess` rewrites `/admin(/.*)?` to `admin.php`; pretty URLs (`/content/...`, `/action/...`, `/rss/...`) rewrite to `index.php`/`rss.php` with query params. `tests/mock/router.php` reproduces these rules for the PHP dev server. Inside the backend, `Http\Routes::PATHS` maps a symbolic action name (e.g. `item`) to its path (`/admin/inhalte`); `Http\Kernel::CONTROLLERS` maps actions to controller classes.

### Field-level help text

`src/help/<area>/<field>.md` (first line = heading, rest = Markdown) backs the "?" tooltip next to config/form fields, wired via the `help` option on `Form::field()`. `Support\Help` resolves `config/<field>` automatically for the configurator; other areas pass the path explicitly. `tests/unit/HelpTest.php` cross-checks every `help` reference against the files on disk and asserts every configurator setting has one — a typo in a help path fails there, not silently in the browser. Misc reference material that isn't tied to a specific field (template placeholders, CSS classes, the `[php]`/`[code]` scripting reference) lives in `docs/REFERENZ.md` instead.

### Historical context worth knowing before "fixing" something

`tests/BEFUNDE.md` documents defects found and fixed during the backend refactor (e.g. B9: unauthenticated requests could mutate data; B4: delete-by-GET with no confirmation or token) — each is now covered by a regression test (`tests/e2e/specs/known-defects.spec.js`, `security.spec.js`). If something looks like a historical bug, check there first before re-fixing it or reverting the fix.
