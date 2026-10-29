<?php

declare(strict_types=1);

/**
 * Admin accounts and passwords, stored in the `admins` table.
 *
 * - First run: if the table is empty, one admin is created from ADMIN_USER / ADMIN_PASSWORD_HASH
 *   (and ADMIN_EMAIL). After that the database is the only source; the secrets are ignored.
 * - Admins can add other admins (with a one-time temporary password they must change), disable them,
 *   or reset their two-step verification.
 * - Reset links are single-use, expire after 30 minutes, are stored only as SHA-256, and the reset
 *   form also asks for an authenticator (or recovery) code, so an inbox alone cannot take an account.
 * - Changing a password signs out that admin's other sessions.
 */

const PASSWORD_MIN_LENGTH = 14;
const RESET_TTL = 1800;           // seconds
const RESET_MAX_REQUESTS = 3;     // per IP per hour

/** Create the first admin from config when there are none. Runs at most once per request. */
function admins_seed(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        return;
    }
    $user = (string) cfg('admin_user');
    $hash = (string) cfg('admin_password_hash');
    if ($user === '' || $hash === '') {
        return;
    }
    $email = (string) (cfg('admin_email') ?: cfg('notify_email'));
    db()->prepare('INSERT INTO admins (username, email, password_hash, password_changed_at, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$user, filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '', $hash, db_now(), db_now(), 'setup']);
    audit('security', 'admin_created', $user, 'first admin, from deploy secrets');
}

function admin_find(string $username): ?array
{
    admins_seed();
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([$username]);

    return $st->fetch() ?: null;
}

function admin_find_active(string $username): ?array
{
    $a = admin_find($username);

    return $a && (int) $a['is_active'] === 1 ? $a : null;
}

/** @return list<array<string,mixed>> */
function admins_all(): array
{
    admins_seed();

    return db()->query('SELECT * FROM admins ORDER BY is_active DESC, username')->fetchAll();
}

function admins_active_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM admins WHERE is_active = 1')->fetchColumn();
}

/** Changes whenever the password changes; sessions created before a change stop working. */
function admin_password_version(string $username): string
{
    $a = admin_find_active($username);

    return $a ? (string) $a['password_changed_at'] : 'disabled';
}

function admin_password_ok(string $username, string $password): bool
{
    $a = admin_find_active($username);
    // Always run password_verify so timing does not reveal whether the username exists.
    // The fallback is a hash of a random, discarded string, so it never matches.
    $ok = password_verify($password, $a['password_hash'] ?? '$2y$10$UkBopR7SgE6RYA/VCK/wB.2fP4bzarKbbEz.yIxwK9pzU4PPODkZy');
    if ($ok && password_needs_rehash($a['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $a['id']]);
    }

    return $a !== null && $ok;
}

/** @return string|null problem with the new password, or null if acceptable */
function password_problem(string $username, string $new, string $confirm): ?string
{
    if ($new !== $confirm) {
        return 'The two new passwords do not match.';
    }
    if (mb_strlen($new) < PASSWORD_MIN_LENGTH) {
        return 'Use at least ' . PASSWORD_MIN_LENGTH . ' characters. A phrase of four or five random words works well.';
    }
    if (mb_strlen($new) > 200) {
        return 'Use at most 200 characters.';
    }
    if (($username !== '' && stripos($new, $username) !== false) || stripos($new, 'manufrancis') !== false || stripos($new, 'password') !== false) {
        return 'Do not use your username, the site name or the word "password".';
    }
    if (count(array_unique(mb_str_split($new))) < 6) {
        return 'That password is too repetitive.';
    }
    $a = admin_find($username);
    if ($a && password_verify($new, $a['password_hash'])) {
        return 'That is your current password. Choose a new one.';
    }

    return null;
}

function admin_set_password(string $username, string $new, bool $mustChange = false): void
{
    db()->prepare('UPDATE admins SET password_hash = ?, password_changed_at = ?, must_change = ? WHERE username = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), db_now(), $mustChange ? 1 : 0, $username]);
    // Any unused reset links are now pointless.
    db()->prepare('DELETE FROM password_resets WHERE username = ?')->execute([$username]);
}

// ---- Managing admins ----------------------------------------------------------

/** Readable random password for new accounts, e.g. "kite-amber-9471-river-glow". Shown once. */
function temporary_password(): string
{
    $words = ['amber', 'anchor', 'arrow', 'birch', 'cedar', 'cliff', 'comet', 'coral', 'delta', 'ember', 'fable', 'flint', 'glow', 'harbor',
        'island', 'jade', 'kite', 'lantern', 'maple', 'meadow', 'nova', 'orbit', 'pebble', 'pixel', 'quartz', 'raven', 'river', 'sage',
        'signal', 'summit', 'tiger', 'timber', 'velvet', 'willow', 'zephyr', 'falcon', 'granite', 'horizon', 'lotus', 'onyx'];
    $pick = fn () => $words[random_int(0, count($words) - 1)];

    return $pick() . '-' . $pick() . '-' . random_int(1000, 9999) . '-' . $pick() . '-' . $pick();
}

/** @return string|null problem, or null on success */
function admin_create(string $username, string $email, string $tempPassword, string $by): ?string
{
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,49}$/', $username)) {
        return 'Username: 3 to 50 characters, lowercase letters, numbers, dot, dash or underscore.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address (used for password resets).';
    }
    if (admin_find($username)) {
        return 'That username is already taken.';
    }
    db()->prepare('INSERT INTO admins (username, email, password_hash, password_changed_at, must_change, created_at, created_by) VALUES (?, ?, ?, ?, 1, ?, ?)')
        ->execute([$username, mb_strtolower($email), password_hash($tempPassword, PASSWORD_DEFAULT), db_now(), db_now(), $by]);

    return null;
}

/** Enable or disable an admin. Refuses to disable yourself or the last active admin. */
function admin_set_active(string $username, bool $active, string $by): ?string
{
    if (!$active && $username === $by) {
        return 'You cannot disable your own account.';
    }
    if (!$active && admins_active_count() <= 1) {
        return 'At least one admin must stay active.';
    }
    db()->prepare('UPDATE admins SET is_active = ? WHERE username = ?')->execute([$active ? 1 : 0, $username]);

    return null;
}

function admin_update_email(string $username, string $email): ?string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Enter a valid email address.';
    }
    db()->prepare('UPDATE admins SET email = ? WHERE username = ?')->execute([mb_strtolower($email), $username]);

    return null;
}

// ---- Reset by email -----------------------------------------------------------

/** Create a reset token and email the link. Silent about whether the account exists. */
function reset_request(string $username): void
{
    $a = admin_find_active($username);
    if ($a === null) {
        audit('security', 'reset_fail', $username, 'unknown or disabled username');
        return;
    }
    $token = bin2hex(random_bytes(32));
    db()->prepare('DELETE FROM password_resets WHERE username = ? OR expires_at < ?')->execute([$username, db_now()]);
    db()->prepare('INSERT INTO password_resets (token_hash, username, created_at, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([hash('sha256', $token), $username, db_now(), db_now(RESET_TTL)]);
    audit('security', 'reset_requested', $username);

    $to = (string) $a['email'];
    $from = (string) cfg('mail_from');
    $link = rtrim((string) cfg('site_url'), '/') . '/admin/reset?token=' . $token;
    $body = "Someone asked to reset the admin password for {$username} on " . cfg('site_url') . ".\n\n"
        . "If it was you, open this link within 30 minutes (it works once):\n{$link}\n\n"
        . "You will also need a code from your authenticator app.\n\n"
        . "If it was not you, ignore this email. Your password has not changed.\n"
        . 'Request from IP ' . client_ip() . ' at ' . date('d M Y, H:i T') . "\n";
    $headers = "From: Manu Francis website <{$from}>\r\nContent-Type: text/plain; charset=UTF-8";

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL) || !@mail($to, 'Reset your admin password', $body, $headers)) {
        audit('security', 'reset_mail_failed', $username, $to === '' ? 'admin has no email address' : 'email could not be sent (check MAIL_FROM)');
    }
}

/** Username for a valid, unused, unexpired token of an active admin, or null. */
function reset_user(string $token): ?string
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
        return null;
    }
    $st = db()->prepare('SELECT username FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?');
    $st->execute([hash('sha256', $token), db_now()]);
    $user = $st->fetchColumn();

    return $user === false || !admin_find_active((string) $user) ? null : (string) $user;
}

/** Mark a token used. Returns false if it was already used (or vanished) in the meantime. */
function reset_consume(string $token): bool
{
    $st = db()->prepare('UPDATE password_resets SET used_at = ? WHERE token_hash = ? AND used_at IS NULL');
    $st->execute([db_now(), hash('sha256', $token)]);

    return $st->rowCount() === 1;
}
