# manufrancis.com

Personal profile site for Manu Francis, with a built-in, GDPR-minded lead manager.
Plain PHP 8.2+, MySQL (Hostinger) and two small Composer libraries for two-step sign-in.
No framework, no front-end build step, no third-party requests from pages.

| Library | Used for |
|---|---|
| [`spomky-labs/otphp`](https://github.com/Spomky-Labs/otphp) | Time-based one-time codes (TOTP, RFC 6238) |
| [`chillerlan/php-qrcode`](https://github.com/chillerlan/php-qrcode) | QR code for authenticator setup, drawn on our server |

## Run locally

```bash
composer install
cp app/config.local.example.php app/config.local.php   # set app_key, admin user and password hash
php -S localhost:8000 -t public_html public_html/index.php
```

Open http://localhost:8000. Admin: http://localhost:8000/admin.
Needs PHP extensions `pdo_sqlite` (local) or `pdo_mysql`, plus `mbstring` and `sodium`.
Locally the example config uses SQLite; production uses MySQL.

Tests: `php tests/run.php` (SQLite). Against MySQL:
`TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=mf_test TEST_DB_USER=... TEST_DB_PASS=... php tests/run.php`
(this drops and recreates the tables in that database, so never point it at production).

## Deploy (GitHub → Hostinger, automatic)

Every push to `main` runs the tests on SQLite and MySQL 8.4, installs Composer packages,
writes the server config from GitHub secrets, then uploads everything over FTPS
(`.github/workflows/deploy.yml`). No secret is ever committed.

### One-time setup

1. **hPanel → Databases → MySQL Databases.** Create a database and user (strong password).
   Note the database name, user and password; they look like `u123456789_mf`.
   The host is `localhost` for sites on the same Hostinger account. Tables are created automatically on first visit.
2. **hPanel → Files → FTP Accounts.** Note the FTP host, user and password.
   Connect once with an FTP client and find the folder that **contains** `public_html`
   (usually `domains/manufrancis.com/`, or `/` if your FTP user starts there).
   `app/`, `vendor/` and `storage/` go next to `public_html`, outside the web root.
3. **hPanel → Advanced → PHP Configuration:** PHP 8.2 or newer, with `pdo_mysql`, `mbstring` and `sodium` (all on by default).
4. **SSL:** make sure the free SSL certificate is active. The site forces HTTPS.
5. **Emails:** create `no-reply@manufrancis.com` (used as `MAIL_FROM` for lead alerts).
6. **GitHub → repo Settings → Environments → `production`**, add these secrets:

| Secret | Value |
|---|---|
| `FTP_SERVER` | FTP host from hPanel, e.g. `ftp.manufrancis.com` |
| `FTP_USERNAME` | FTP user |
| `FTP_PASSWORD` | FTP password |
| `FTP_SERVER_DIR` | Folder that contains `public_html`, ending in `/`, e.g. `domains/manufrancis.com/` |
| `DB_HOST` | Usually `localhost` (optional, that is the default) |
| `DB_NAME` | MySQL database name from step 1 |
| `DB_USER` | MySQL user from step 1 |
| `DB_PASSWORD` | MySQL password from step 1 |
| `APP_KEY` | 64 random characters: `php -r "echo bin2hex(random_bytes(32));"` |
| `ADMIN_USER` | First admin's username (used only to create the first account) |
| `ADMIN_PASSWORD_HASH` | First admin's password hash: output of `php bin/hash-password.php` (never the plain password) |
| `ADMIN_EMAIL` | First admin's email, for password reset links |
| `NOTIFY_EMAIL` | Where new-lead alerts go (optional) |
| `MAIL_FROM` | `no-reply@manufrancis.com` (optional, needed for alerts) |
| `PRIVACY_EMAIL` | Address shown on the privacy page for data requests (optional) |

   Optionally add the variable `SITE_URL` (defaults to `https://manufrancis.com`).
7. Push to `main`, or run the workflow by hand from the Actions tab.

**Keep `APP_KEY` the same once live.** It encrypts the two-step secret; changing it means setting up the authenticator again (leads are not affected).

## Admin sign-in (two-step verification)

1. Go to `/admin`, enter the username and password.
2. **First time only:** a setup page shows a QR code. Scan it with an authenticator app
   (Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden, Authy), enter the 6-digit code,
   then **save the 10 recovery codes** shown once.
3. Every later sign-in: password, then the current 6-digit code (or one recovery code).

**Lost the phone and the recovery codes?** Another admin can reset your two-step verification under **Admin → Admins**.
If you are the only admin, delete your row from the `admin_2fa` table in hPanel → Databases → phpMyAdmin.
The next sign-in will show the setup QR again (the password is still required).
New recovery codes can be made any time under **Admin → Security**.

## Admin accounts and passwords

Admins live in the `admins` table. On the very first visit, when that table is empty, one admin is created from
`ADMIN_USER`, `ADMIN_PASSWORD_HASH` and `ADMIN_EMAIL`. After that the database is the only source: changing those
secrets does nothing.

- **Change password:** Admin → Security (current password + authenticator code). Signs out your other sessions.
- **Forgot password:** "Forgot password?" on the sign-in page emails a single-use link (30 minutes) to the admin's email.
  The reset page also asks for an authenticator or recovery code. Needs `MAIL_FROM` set to a mailbox on the domain.
- **Add an admin:** Admin → Admins. They get a one-time temporary password, must choose their own at first sign-in,
  and must set up an authenticator app. Every account change needs your current authenticator code.
- **Disable an admin or reset their two-step:** Admin → Admins → Manage. You cannot disable yourself or the last active admin.
- **Locked out completely?** In phpMyAdmin, run `UPDATE admins SET password_hash = '<output of php bin/hash-password.php>' WHERE username = 'you';`

## Activity and security log

Admin → Activity lists sign-ins, failed attempts, lockouts, password and two-step changes, admin account changes,
lead exports and deletions, with the IP address and browser, 25 per page. Filter by type or show failures only.
Entries are deleted after 90 days.

## Lead management

`/admin` → sign in → list, filter by status, search, open a lead, change status, add private notes,
export CSV, or delete permanently (for erasure requests). Leads older than 730 days are deleted automatically.

## Security and privacy summary

- Web root is `public_html/` only; code (`app/`, `vendor/`) and runtime files (`storage/`) live outside it, with `Require all denied` as a backup. Leads live in MySQL.
- Only `index.php` may execute; directory listing is off; dotfiles are blocked.
- Strict Content-Security-Policy (no inline scripts, no third-party origins), HSTS, frame denial, nosniff, referrer and permissions policies.
- Contact form: signed time-limited token (no cookie), Origin check, honeypot, per-IP rate limit, server-side validation, prepared statements (native, not emulated), escaped output.
- Admin sign-in: bcrypt password + TOTP two-step verification (mandatory, set up on first sign-in).
  - TOTP secret 160-bit, encrypted at rest with libsodium (key derived from `APP_KEY`); QR drawn server-side, never sent to a third party.
  - Each code works once (replay protection), ±30 s clock drift allowed.
  - 10 single-use recovery codes, stored as bcrypt hashes.
  - Throttling: 5 wrong passwords per IP and 5 wrong codes per account per 15 minutes; the password step expires after 5 minutes.
  - Passwords: bcrypt in the `admins` table, at least 14 characters, rehashed automatically when PHP's default improves. Reset tokens are single-use, 30 minutes, stored as SHA-256, and need a 2FA code too. A password change ends that admin's other sessions.
  - Audit trail of all sign-in, security and data-access events (90 days).
  - Session: cookie scoped to `/admin` (`__Secure-` prefix, HttpOnly, Secure, SameSite=Strict), strict mode, ID rotated at each step, 30-minute idle and 12-hour absolute timeout, bound to the browser.
  - CSRF tokens on every admin form, `noindex`, `no-store`.
- Dependencies are pinned in `composer.lock` and checked with `composer audit` on every CI run.
- GDPR: explicit unticked consent with stored wording and timestamp, privacy policy, no visitor cookies or trackers, IPs only as keyed hashes kept for 24 hours, automatic retention purge, erasure and export from admin.
