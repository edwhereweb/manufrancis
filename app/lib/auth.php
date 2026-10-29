<?php

declare(strict_types=1);

/**
 * Admin sign-in in two steps:
 *   1. username + password  -> session is "pending" (not signed in) for 5 minutes
 *   2. authenticator code (or recovery code) -> signed in
 * First sign-in after deploy forces authenticator setup before step 2 can complete.
 */

const LOGIN_MAX_FAILS = 5;
const LOGIN_WINDOW = 900;          // seconds
const OTP_MAX_FAILS = 5;
const PENDING_TTL = 300;           // seconds allowed between password and code
const ADMIN_IDLE_TIMEOUT = 1800;   // signed-in idle timeout
const ADMIN_ABSOLUTE_TIMEOUT = 43200;

/** Session cookie is scoped to /admin only, so public visitors never get a cookie. */
function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(is_https() ? '__Secure-mf_admin' : 'mf_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/admin',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) ADMIN_IDLE_TIMEOUT);
    $dir = storage_dir() . '/sessions';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    session_save_path($dir);
    session_start();
}

function ua_hash(): string
{
    return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

function admin_logged_in(): bool
{
    admin_session_start();
    $s = $_SESSION;
    $ok = !empty($s['admin'])
        && !empty($s['mfa'])
        && ($s['last_seen'] ?? 0) > time() - ADMIN_IDLE_TIMEOUT
        && ($s['since'] ?? 0) > time() - ADMIN_ABSOLUTE_TIMEOUT
        && hash_equals((string) ($s['ua'] ?? ''), ua_hash())
        // A password change elsewhere ends this session.
        && hash_equals(admin_password_version((string) $s['admin']), (string) ($s['pwv'] ?? ''));
    if ($ok) {
        $_SESSION['last_seen'] = time();
    }

    return $ok;
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('/admin/login');
    }
    // New accounts start with a temporary password: nothing else until it is changed.
    $a = admin_find_active((string) $_SESSION['admin']);
    if ($a && (int) $a['must_change'] === 1 && !in_array(request_path(), ['/admin/security', '/admin/recovery-codes'], true)) {
        redirect('/admin/security?must=1');
    }
}

/** Username that passed the password step and is waiting for its code, or null. */
function admin_pending_user(): ?string
{
    admin_session_start();
    $p = $_SESSION['pending'] ?? null;
    if (!is_array($p) || ($p['at'] ?? 0) < time() - PENDING_TTL || !hash_equals((string) ($p['ua'] ?? ''), ua_hash())) {
        return null;
    }

    return (string) $p['user'];
}

/**
 * Step 1: check username and password.
 *
 * @return string|null error message, or null on success (session is now pending)
 */
function admin_password_step(string $user, string $password): ?string
{
    $bucket = 'login:' . ip_hash();
    if (throttle_count($bucket, LOGIN_WINDOW) >= LOGIN_MAX_FAILS) {
        audit('signin', 'password_locked', $user);
        return 'Too many attempts. Try again in 15 minutes.';
    }

    if (!admin_password_ok($user, $password)) {
        throttle_hit($bucket);
        audit('signin', 'password_fail', $user, admin_find_active($user) ? 'wrong password' : 'unknown or disabled username');
        return 'Wrong username or password.';
    }

    audit('signin', 'password_ok', $user);
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION = ['pending' => ['user' => $user, 'at' => time(), 'ua' => ua_hash()], 'csrf' => bin2hex(random_bytes(32))];

    return null;
}

/**
 * Step 2: check the authenticator or recovery code.
 *
 * @return string|null error message, or null on success (signed in)
 */
function admin_code_step(string $code): ?string
{
    $user = admin_pending_user();
    if ($user === null) {
        return 'Your sign-in expired. Please start again.';
    }
    $bucket = 'otp:' . $user;
    if (throttle_count($bucket, LOGIN_WINDOW) >= OTP_MAX_FAILS) {
        audit('signin', 'code_locked', $user);
        admin_logout();
        return 'Too many wrong codes. Sign-in is locked for 15 minutes.';
    }
    if (!twofa_verify($user, $code)) {
        throttle_hit($bucket);
        audit('signin', 'code_fail', $user);
        return 'That code is not valid. Check the time on your phone and try the newest code.';
    }

    throttle_clear($bucket);
    throttle_clear('login:' . ip_hash());
    admin_complete_login($user);

    return null;
}

/** Mark the session fully signed in (after a code was accepted or 2FA was just set up). */
function admin_complete_login(string $user): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'admin'     => $user,
        'mfa'       => true,
        'csrf'      => bin2hex(random_bytes(32)),
        'since'     => time(),
        'last_seen' => time(),
        'ua'        => ua_hash(),
        'pwv'       => admin_password_version($user),
    ];
    audit('signin', 'signin_ok', $user);
    audit_purge();
}

/** After the password changes in this session: keep this session, end all others. */
function admin_refresh_session(string $user): void
{
    session_regenerate_id(true);
    $_SESSION['pwv'] = admin_password_version($user);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', ['expires' => 1, 'path' => '/admin', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Strict']);
}

/** Friendly message on the sign-in page after sign-out or a password reset. */
function login_notice(): ?string
{
    return match (true) {
        isset($_GET['reset'])      => 'Your password has been reset. Sign in with the new one.',
        isset($_GET['signed_out']) => 'You have signed out.',
        default                    => null,
    };
}

function admin_csrf(): string
{
    admin_session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));

    return $_SESSION['csrf'];
}

function admin_csrf_check(): void
{
    if (!same_origin() || !hash_equals(admin_csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request. Go back, reload the page and try again.');
    }
}
