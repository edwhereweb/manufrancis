<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);

// Composer libraries (TOTP, QR codes). CI runs `composer install`; vendor/ is deployed, not committed.
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    exit('Dependencies missing: run composer install.');
}
require $autoload;

require APP_ROOT . '/lib/http.php';
require APP_ROOT . '/lib/security.php';
require APP_ROOT . '/lib/db.php';
require APP_ROOT . '/lib/leads.php';
require APP_ROOT . '/lib/auth.php';

$isLocal = cfg('env') === 'local';
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');
ini_set('expose_php', '0');
error_reporting(E_ALL);
if (is_writable(cfg('storage_path'))) {
    ini_set('error_log', cfg('storage_path') . '/php-error.log');
}
date_default_timezone_set('Asia/Kolkata');

// Any uncaught error: log the details with a short reference, show a plain 500 page with that reference.
// Find it later in storage/php-error.log (outside public_html) by searching for the reference.
set_exception_handler(function (Throwable $e) use ($isLocal): void {
    $ref = bin2hex(random_bytes(4));
    error_log(sprintf("[ref %s] %s: %s in %s:%d\n%s", $ref, $e::class, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>Something went wrong</title><link rel="stylesheet" href="/assets/css/site.css">'
        . '<main class="section"><div class="wrap prose"><p class="eyebrow">Error 500</p><h1>Something went wrong</h1>'
        . '<p>Please try again in a moment. If it keeps happening, quote this reference: <code>' . $ref . '</code></p>'
        . ($isLocal ? '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES) . '</pre>' : '')
        . '<p><a href="/">Go to the home page</a></p></div></main>';
});

send_security_headers();

if (strlen((string) cfg('app_key')) < 32) {
    http_response_code(503);
    error_log('app_key is missing or shorter than 32 characters in app/config.local.php');
    exit('Site is not configured yet.');
}
