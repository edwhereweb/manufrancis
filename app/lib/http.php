<?php

declare(strict_types=1);

/** Read one setting from app/config.php (merged with config.local.php). */
function cfg(string $key): mixed
{
    static $config = null;
    $config ??= require APP_ROOT . '/config.php';

    return $config[$key] ?? null;
}

/** Escape for HTML output. Use on every value that goes into a template. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return ($_SERVER['HTTPS'] ?? '') === 'on'
        || ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    return rtrim($path, '/') ?: '/';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $to, int $status = 303): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

/** Render app/views/<name>.php inside the layout (or bare when $layout is null). */
function view(string $name, array $vars = [], ?string $layout = 'layout', int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_ROOT . '/views/' . $name . '.php';
    $content = ob_get_clean();
    if ($layout === null) {
        echo $content;
    } else {
        require APP_ROOT . '/views/' . $layout . '.php';
    }
    exit;
}

function not_found(): never
{
    view('404', ['title' => 'Page not found | Manu Francis', 'description' => 'This page does not exist.', 'noindex' => true], 'layout', 404);
}

/** ' width="W" height="H"' read from an image under public_html/assets/img (prevents layout shift). */
function img_size(string $path): string
{
    static $cache = [];
    if (!isset($cache[$path])) {
        $info = @getimagesize(dirname(APP_ROOT) . '/public_html/assets/img/' . $path);
        $cache[$path] = $info ? sprintf(' width="%d" height="%d"', $info[0], $info[1]) : '';
    }

    return $cache[$path];
}

/** Cache-busted asset URL. */
function asset(string $path): string
{
    $file = dirname(APP_ROOT) . '/public_html/assets/' . $path;
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';

    return '/assets/' . $path . '?v=' . $v;
}
