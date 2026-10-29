# AGENTS.md

Profile site for **manufrancis.com** with a small lead manager. Plain PHP 8.2+ and MySQL (SQLite for local/tests).
Keep it boring: no framework, no npm, no build step. Composer only for vetted security libraries (TOTP, QR);
CI installs them and deploys `vendor/`.

## Commands

- Install libraries: `composer install`
- Dev server: `php -S localhost:8000 -t public_html public_html/index.php`
- Tests: `php tests/run.php` (SQLite; needs `pdo_sqlite`, `mbstring`, `sodium`). Set `TEST_DB_*` env vars to run against MySQL (drops tables!).
- Lint: `find app public_html bin tests -name '*.php' -print0 | xargs -0 -n1 php -l`
- Admin password hash: `php bin/hash-password.php`

## Layout

| Path | What |
|---|---|
| `public_html/` | **Web root** (maps to Hostinger `public_html`). Only `index.php` runs; everything else is static. |
| `public_html/index.php` | Front controller and the route list. |
| `public_html/assets/` | `css/site.css` (single stylesheet, tokens at the top), `js/site.js` (progressive enhancement only), `img/logos`, `img/photos` (all `.webp`). |
| `app/` | Private code, never web-reachable. |
| `app/config.php` | Defaults. Real values come from `app/config.local.php` (git-ignored, generated on deploy by `bin/make-config.php`). |
| `app/content/profile.php` | **All profile text and facts.** Edit content here, not in templates. |
| `app/lib/` | `http.php` (cfg, e, view, redirect), `security.php` (headers, form token, IP hash), `db.php` (MySQL/SQLite connection, schema migration, throttle), `leads.php`, `auth.php` (two-step sign-in, sessions, CSRF), `twofa.php` (TOTP, QR, encrypted secret, recovery codes). |
| `app/handlers/` | `contact.php` (POST /), `admin.php` (all /admin routes). |
| `app/views/` | Templates. `layout.php` for the public site, `admin/layout.php` for admin. |
| `storage/` | Runtime: `sessions/`, `php-error.log`, schema markers (`schema-*.ok`). Git-ignored and never uploaded by deploy. |
| `vendor/` | Composer packages. Git-ignored; installed by CI and uploaded next to `app/`. |
| `bin/` | CLI helpers (not deployed). |
| `tests/run.php` | Dependency-free test runner. |

## Design

Calm, product-style layout: white and `#f5f5f7` sections, one dark band each for Recognition and Training, big tight headings, rounded tiles.

- **Tokens** are at the top of `site.css` (`--ink`, `--muted`, `--blue`, `--mint`, `--violet`, `--amber`, radii, shadows). Use them; do not add one-off colours.
- **Font**: Inter variable, self-hosted in `public_html/assets/fonts/` (OFL licence alongside). Never load fonts from Google.
- **Illustrations**: inline SVGs in `app/views/partials/illustrations.php`, called with `illo('shield')` etc. They colour themselves from the tile's `--c/--c2/--c3` (set by `.tone-*`) and animate with CSS classes (`spin`, `pulse`, `scan`, `flow`, `blink`, `float`). No `style=""` (CSP).
- **Motion**: add `reveal` (+ `d1`/`d2` for stagger) to fade a block in on scroll; `data-count` on a stat counts it up. All motion is off under `prefers-reduced-motion`.
- **Photos**: 1200px wide `.webp`, real event photos only, no stamped/overlaid images. Gallery tiles take `size` `wide` or `normal` in `profile.php`.

## Naming

- Files and folders: lowercase kebab-case (`hash-password.php`, `manu-francis-hero.webp`).
- PHP functions: snake_case, prefixed by area (`lead_create`, `admin_login`, `throttle_hit`).
- Constants: UPPER_SNAKE (`LEAD_STATUSES`).
- Images: `.webp`, descriptive kebab-case names. Logos in `img/logos/`, photos in `img/photos/`.

## Rules

- **Content accuracy:** only facts Manu supplied go in `app/content/profile.php`. Do not invent clients, numbers or claims.
- **Escape all output** with `e()`. Use prepared statements for every query.
- **No third-party requests** from pages (no Google Fonts, CDNs, analytics, embeds). This is what keeps the site free of cookie banners and GDPR transfers. The CSP will block them anyway.
- **No inline `<script>` or `style=""`.** The CSP forbids them. Put JS in `site.js`, CSS in `site.css`.
- **Visitors get no cookies.** The admin session cookie is scoped to `/admin`. Keep it that way.
- **New personal data field?** Update the privacy policy table (`app/views/privacy.php`), validation in `lead_validate()`, and the CSV export columns.
- **Changing consent wording?** Bump the version in `CONSENT_TEXT` (`app/lib/leads.php`) so each lead keeps the wording it agreed to.
- New route: add it in `public_html/index.php` (public) or `app/handlers/admin.php` (admin, after `require_admin()`).
- Run the tests before pushing. Pushing to `main` deploys to production.

## Database

- Schema lives in `db_migrate()` (`app/lib/db.php`). To change it: edit the DDL (works on MySQL and SQLite), bump `SCHEMA_VERSION`, and make the change safe on an existing database.
- Store times with `db_now()` (`Y-m-d H:i:s`), never `date('c')`.
- Use `ESCAPE '!'` for LIKE searches (backslash behaves differently in MySQL and SQLite).

## Admin security

- Sign-in is password, then TOTP. Never set `$_SESSION['admin']` anywhere except `admin_complete_login()`.
- New admin pages go after `require_admin()` in `app/handlers/admin.php` and must call `admin_csrf_check()` on POST.
- The TOTP secret is only stored encrypted (`twofa_encrypt`). Never log codes, secrets or recovery codes.

## Deploy

GitHub Actions (`.github/workflows/deploy.yml`): test on SQLite and MySQL, `composer install --no-dev`, write `app/config.local.php` from secrets, upload over FTPS to
`FTP_SERVER_DIR` (the folder that contains `public_html`). The secrets are listed in `README.md`.
