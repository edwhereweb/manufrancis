# AGENTS.md

Profile site for **manufrancis.com** with a small lead manager. Plain PHP 8.1+ and SQLite.
Keep it boring: no framework, no Composer, no npm, no build step. Hostinger shared hosting runs it as-is.

## Commands

- Dev server: `php -S localhost:8000 -t public_html public_html/index.php`
- Tests: `php tests/run.php` (needs `pdo_sqlite`, `mbstring`)
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
| `app/lib/` | `http.php` (cfg, e, view, redirect), `security.php` (headers, form token, IP hash), `db.php` (SQLite + throttle), `leads.php`, `auth.php`. |
| `app/handlers/` | `contact.php` (POST /), `admin.php` (all /admin routes). |
| `app/views/` | Templates. `layout.php` for the public site, `admin/layout.php` for admin. |
| `storage/` | Runtime: `leads.sqlite`, `sessions/`, `php-error.log`. Git-ignored and never uploaded by deploy. |
| `bin/` | CLI helpers (not deployed). |
| `tests/run.php` | Dependency-free test runner. |

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

## Deploy

GitHub Actions (`.github/workflows/deploy.yml`): test, write `app/config.local.php` from secrets, upload over FTPS to
`FTP_SERVER_DIR` (the folder that contains `public_html`). The secrets are listed in `README.md`.
