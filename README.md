# manufrancis.com

Personal profile site for Manu Francis, with a built-in, GDPR-minded lead manager.
Plain PHP 8.1+ and SQLite. No framework, no Composer, no build step, no third-party requests.

## Run locally

```bash
cp app/config.local.example.php app/config.local.php   # then edit app_key, admin user and hash
php -S localhost:8000 -t public_html public_html/index.php
```

Open http://localhost:8000. Admin: http://localhost:8000/admin.
The PHP extensions `pdo_sqlite` and `mbstring` must be enabled.

Tests: `php tests/run.php`

## Deploy (GitHub → Hostinger, automatic)

Every push to `main` runs the tests, then uploads the site to Hostinger over FTPS
(`.github/workflows/deploy.yml`). The server config is generated from GitHub secrets,
so no secret is ever committed.

### One-time setup

1. **Hostinger, hPanel → Files → FTP Accounts.** Note the FTP host, user and password.
   Connect once with an FTP client and find the folder that **contains** `public_html`
   (usually `domains/manufrancis.com/`, or `/` if your FTP user starts there).
   The private folders `app/` and `storage/` go next to `public_html`, outside the web root.
2. **Hostinger, hPanel → Advanced → PHP Configuration:** PHP 8.1 or newer with `pdo_sqlite` enabled (default on Hostinger).
3. **Hostinger, SSL:** make sure the free SSL certificate is active. The site forces HTTPS.
4. **Hostinger, Emails:** create `no-reply@manufrancis.com` (used as `MAIL_FROM` for lead alerts).
5. **GitHub → repo Settings → Environments → `production`**, add these secrets:

| Secret | Value |
|---|---|
| `FTP_SERVER` | FTP host from hPanel, e.g. `ftp.manufrancis.com` |
| `FTP_USERNAME` | FTP user |
| `FTP_PASSWORD` | FTP password |
| `FTP_SERVER_DIR` | Folder that contains `public_html`, ending in `/`, e.g. `domains/manufrancis.com/` |
| `APP_KEY` | 64 random characters: `php -r "echo bin2hex(random_bytes(32));"` |
| `ADMIN_USER` | Admin username |
| `ADMIN_PASSWORD_HASH` | Output of `php bin/hash-password.php` (never the plain password) |
| `NOTIFY_EMAIL` | Where new-lead alerts go (optional) |
| `MAIL_FROM` | `no-reply@manufrancis.com` (optional, needed for alerts) |
| `PRIVACY_EMAIL` | Address shown on the privacy page for data requests (optional) |

   Optionally add the variable `SITE_URL` (defaults to `https://manufrancis.com`).
6. Push to `main`, or run the workflow by hand from the Actions tab.

**Keep `APP_KEY` the same once live.** Changing it invalidates open forms and IP hashes (no data is lost).

## Lead management

`/admin` → sign in → list, filter by status, search, open a lead, change status, add private notes,
export CSV, or delete permanently (for erasure requests). Leads older than 730 days are deleted automatically.

## Security and privacy summary

- Web root is `public_html/` only; code (`app/`) and data (`storage/`) live outside it, with `Require all denied` as a backup.
- Only `index.php` may execute; directory listing is off; dotfiles are blocked.
- Strict Content-Security-Policy (no inline scripts, no third-party origins), HSTS, frame denial, nosniff, referrer and permissions policies.
- Contact form: signed time-limited token (no cookie), Origin check, honeypot, per-IP rate limit, server-side validation, prepared statements, escaped output.
- Admin: bcrypt password, login throttling, session cookie scoped to `/admin` (HttpOnly, Secure, SameSite=Strict), idle timeout, session ID rotation, CSRF tokens, `noindex`.
- GDPR: explicit unticked consent with stored wording and timestamp, privacy policy, no visitor cookies or trackers, IPs only as keyed hashes kept for 24 hours, automatic retention purge, erasure and export from admin.
