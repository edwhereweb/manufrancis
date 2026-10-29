<?php

declare(strict_types=1);

function send_security_headers(): void
{
    header_remove('X-Powered-By');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'"
        . (is_https() ? '; upgrade-insecure-requests' : ''));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Stateless form token: "<time>.<hmac>". Lets the public contact form resist CSRF
 * and simple bots without setting any cookie on visitors.
 */
function form_token(): string
{
    $t = (string) time();

    return $t . '.' . hash_hmac('sha256', 'contact|' . $t, (string) cfg('app_key'));
}

/** Valid if signed by us, at least 3 seconds old (bots submit instantly) and under 2 hours. */
function form_token_valid(string $token, int $now = 0): bool
{
    $now = $now ?: time();
    [$t, $sig] = array_pad(explode('.', $token, 2), 2, '');
    if (!ctype_digit($t)) {
        return false;
    }
    $age = $now - (int) $t;

    return $age >= 3 && $age <= 7200
        && hash_equals(hash_hmac('sha256', 'contact|' . $t, (string) cfg('app_key')), $sig);
}

/** Reject cross-site POSTs when the browser tells us where they came from. */
function same_origin(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return true; // Older browsers / privacy tools; the signed token still protects the form.
    }

    return parse_url($origin, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')
        || parse_url($origin, PHP_URL_HOST) === parse_url((string) cfg('site_url'), PHP_URL_HOST);
}

/** Keyed hash of the visitor IP. We never store raw IPs (GDPR data minimisation). */
function ip_hash(): string
{
    return hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', (string) cfg('app_key'));
}

/** Prevent spreadsheet formula injection when leads are exported to CSV. */
function csv_safe(string $value): string
{
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
}
