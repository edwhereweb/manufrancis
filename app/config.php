<?php

declare(strict_types=1);

/**
 * Default settings. Real values live in app/config.local.php (never committed),
 * which the deploy workflow writes from GitHub secrets. See bin/make-config.php.
 */
$defaults = [
    'env'                 => 'production',         // 'local' shows errors and allows plain http
    'site_url'            => 'https://manufrancis.com',
    'app_key'             => '',                   // 32+ random chars; signs form tokens and hashes IPs
    'admin_user'          => '',
    'admin_password_hash' => '',                   // php bin/hash-password.php
    'notify_email'        => '',                   // where new-lead alerts go; empty = no email
    'mail_from'           => '',                   // e.g. no-reply@manufrancis.com (must be on your domain)
    'privacy_email'       => '',                   // shown on the privacy page; empty = use the contact form
    'retention_days'      => 730,                  // leads older than this are deleted automatically
    'storage_path'        => dirname(__DIR__) . '/storage',  // sessions, logs, schema markers

    // Database. Production: Hostinger MySQL (hPanel > Databases). 'sqlite' is for tests/local only.
    'db_driver'           => 'mysql',
    'db_host'             => 'localhost',
    'db_port'             => 3306,
    'db_name'             => '',
    'db_user'             => '',
    'db_pass'             => '',
];

// MF_CONFIG_FILE lets tests point at a throwaway config.
$file = getenv('MF_CONFIG_FILE') ?: __DIR__ . '/config.local.php';
$local = is_file($file) ? require $file : [];

return array_merge($defaults, is_array($local) ? $local : []);
