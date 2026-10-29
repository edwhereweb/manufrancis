<?php

// Copy to app/config.local.php for local development. Never commit config.local.php.
return [
    'env'                 => 'local',
    'site_url'            => 'http://localhost:8000',
    'app_key'             => 'change-me-to-64-random-characters-change-me-to-64-random-chars',
    'admin_user'          => 'admin',
    'admin_password_hash' => '', // php bin/hash-password.php

    // Local: SQLite file in storage/. To use MySQL instead, set db_driver to 'mysql' and fill these in.
    'db_driver'           => 'sqlite',
    'db_host'             => '127.0.0.1',
    'db_name'             => '',
    'db_user'             => '',
    'db_pass'             => '',
];
