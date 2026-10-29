<?php

// Copy to app/config.local.php for local development. Never commit config.local.php.
return [
    'env'                 => 'local',
    'site_url'            => 'http://localhost:8000',
    'app_key'             => 'change-me-to-64-random-characters-change-me-to-64-random-chars',
    'admin_user'          => 'admin',
    'admin_password_hash' => '', // php bin/hash-password.php
];
