<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);

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

send_security_headers();

if (strlen((string) cfg('app_key')) < 32) {
    http_response_code(503);
    error_log('app_key is missing or shorter than 32 characters in app/config.local.php');
    exit('Site is not configured yet.');
}
