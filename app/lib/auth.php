<?php

declare(strict_types=1);

const LOGIN_MAX_FAILS = 5;
const LOGIN_WINDOW = 900; // seconds
const ADMIN_IDLE_TIMEOUT = 1800;

/** Session cookie is scoped to /admin only, so public visitors never get a cookie. */
function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('mf_admin');
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
    $dir = cfg('storage_path') . '/sessions';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    session_save_path($dir);
    session_start();
}

function admin_logged_in(): bool
{
    admin_session_start();
    $ok = !empty($_SESSION['admin'])
        && ($_SESSION['last_seen'] ?? 0) > time() - ADMIN_IDLE_TIMEOUT
        && hash_equals((string) ($_SESSION['ua'] ?? ''), hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''));
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
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}

/** @return string|null error message, or null on success */
function admin_login(string $user, string $password): ?string
{
    $bucket = 'login:' . ip_hash();
    if (throttle_count($bucket, LOGIN_WINDOW) >= LOGIN_MAX_FAILS) {
        return 'Too many attempts. Try again in 15 minutes.';
    }

    $hash = (string) cfg('admin_password_hash');
    $userOk = hash_equals((string) cfg('admin_user'), $user) && cfg('admin_user') !== '';
    // Always run password_verify so timing does not reveal whether the username exists.
    // The fallback is a hash of a random, discarded string, so it never matches.
    $passOk = password_verify($password, $hash ?: '$2y$10$UkBopR7SgE6RYA/VCK/wB.2fP4bzarKbbEz.yIxwK9pzU4PPODkZy');

    if (!$userOk || !$passOk) {
        throttle_hit($bucket);
        error_log('Admin login failed');
        return 'Wrong username or password.';
    }

    throttle_clear($bucket);
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION = [
        'admin'     => true,
        'csrf'      => bin2hex(random_bytes(32)),
        'last_seen' => time(),
        'ua'        => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];

    return null;
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    session_destroy();
    setcookie('mf_admin', '', ['expires' => 1, 'path' => '/admin', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Strict']);
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
