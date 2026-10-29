<?php

declare(strict_types=1);

// Local dev server (php -S): let it serve real files directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$path = request_path();

// ---- Public pages --------------------------------------------------------
if ($path === '/') {
    if (is_post()) {
        require APP_ROOT . '/handlers/contact.php';
    }
    view('home', ['title' => 'Manu Francis | Cybersecurity Architect & Founder', 'sent' => isset($_GET['sent'])]);
}

if ($path === '/privacy') {
    view('privacy', ['title' => 'Privacy Policy | Manu Francis']);
}

// ---- Admin (lead management) ---------------------------------------------
if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    require APP_ROOT . '/handlers/admin.php';
}

not_found();
