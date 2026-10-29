<?php

declare(strict_types=1);

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;

/**
 * Two-factor authentication (TOTP, RFC 6238) for the admin.
 *
 * - Codes come from any authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, Authy...).
 * - The shared secret is encrypted at rest with libsodium using a key derived from app_key.
 * - A code is accepted once only (replay protection via last_step), with +/-1 step of clock drift.
 * - Ten single-use recovery codes are issued at setup and stored only as password hashes.
 */

const TOTP_ISSUER = 'manufrancis.com';
const TOTP_SECRET_BYTES = 20;      // 160-bit secret, as RFC 4226 recommends
const TOTP_DRIFT_STEPS = 1;        // accept the previous and next 30 s window
const RECOVERY_CODE_COUNT = 10;

function twofa_new_secret(): string
{
    return TOTP::generate(null, TOTP_SECRET_BYTES)->getSecret();
}

function twofa_totp(string $secret, string $user): TOTP
{
    $totp = TOTP::createFromSecret($secret);
    $totp->setLabel($user);
    $totp->setIssuer(TOTP_ISSUER);

    return $totp;
}

/** otpauth:// URI as an SVG data URI QR code (rendered on our server; the secret never leaves it). */
function twofa_qr_data_uri(string $secret, string $user): string
{
    $options = new QROptions(['eccLevel' => EccLevel::M, 'addQuietzone' => true, 'outputBase64' => true]);

    return (new QRCode($options))->render(twofa_totp($secret, $user)->getProvisioningUri());
}

/** Secret split into groups of four for manual entry. */
function twofa_pretty_secret(string $secret): string
{
    return trim(chunk_split($secret, 4, ' '));
}

/**
 * Check a 6-digit code. Returns the matched time step, or null.
 * Codes at or before $lastStep are rejected so a code cannot be used twice.
 */
function twofa_match(string $secret, string $code, int $lastStep = 0, ?int $now = null): ?int
{
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return null;
    }
    $now ??= time();
    $totp = TOTP::createFromSecret($secret);
    $period = $totp->getPeriod();
    $current = intdiv($now, $period);

    for ($step = $current - TOTP_DRIFT_STEPS; $step <= $current + TOTP_DRIFT_STEPS; $step++) {
        if ($step > $lastStep && hash_equals($totp->at($step * $period), $code)) {
            return $step;
        }
    }

    return null;
}

// ---- Encryption at rest -----------------------------------------------------

function twofa_key(): string
{
    return sodium_crypto_generichash('manufrancis-2fa|' . cfg('app_key'), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
}

function twofa_encrypt(string $plain): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, twofa_key()));
}

function twofa_decrypt(string $stored): ?string
{
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }
    $plain = sodium_crypto_secretbox_open(
        substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
        substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
        twofa_key()
    );

    return $plain === false ? null : $plain;
}

// ---- Storage ----------------------------------------------------------------

function twofa_record(string $user): ?array
{
    $st = db()->prepare('SELECT * FROM admin_2fa WHERE username = ?');
    $st->execute([$user]);

    return $st->fetch() ?: null;
}

function twofa_enrolled(string $user): bool
{
    return twofa_record($user) !== null;
}

/**
 * Save a confirmed secret and return fresh recovery codes (shown to the admin once).
 *
 * @return list<string>
 */
function twofa_enroll(string $user, string $secret, int $step): array
{
    [$codes, $hashes] = twofa_make_recovery_codes();
    db()->prepare('DELETE FROM admin_2fa WHERE username = ?')->execute([$user]);
    db()->prepare('INSERT INTO admin_2fa (username, secret_enc, last_step, recovery_json, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$user, twofa_encrypt($secret), $step, json_encode($hashes), db_now()]);

    return $codes;
}

/** Verify a TOTP or recovery code for an enrolled admin and record its use. */
function twofa_verify(string $user, string $input): bool
{
    $row = twofa_record($user);
    $secret = $row ? twofa_decrypt($row['secret_enc']) : null;
    if ($row === null || $secret === null) {
        return false;
    }

    $step = twofa_match($secret, $input, (int) $row['last_step']);
    if ($step !== null) {
        // Conditional update so two simultaneous requests cannot both use the same code.
        $st = db()->prepare('UPDATE admin_2fa SET last_step = ? WHERE username = ? AND last_step < ?');
        $st->execute([$step, $user, $step]);

        return $st->rowCount() === 1;
    }

    return twofa_use_recovery_code($user, $input, $row);
}

/** @return array{0: list<string>, 1: list<string>} [plain codes, hashes] */
function twofa_make_recovery_codes(): array
{
    $codes = [];
    $hashes = [];
    for ($i = 0; $i < RECOVERY_CODE_COUNT; $i++) {
        $code = implode('-', str_split(strtolower(bin2hex(random_bytes(5))), 5)); // e.g. 3f9a2-c81d0
        $codes[] = $code;
        $hashes[] = password_hash($code, PASSWORD_DEFAULT);
    }

    return [$codes, $hashes];
}

function twofa_use_recovery_code(string $user, string $input, array $row): bool
{
    $input = strtolower(trim($input));
    if (!preg_match('/^[0-9a-f]{5}-[0-9a-f]{5}$/', $input)) {
        return false;
    }
    $hashes = json_decode($row['recovery_json'], true) ?: [];
    foreach ($hashes as $i => $hash) {
        if (password_verify($input, $hash)) {
            unset($hashes[$i]);
            db()->prepare('UPDATE admin_2fa SET recovery_json = ? WHERE username = ?')
                ->execute([json_encode(array_values($hashes)), $user]);
            error_log("Admin {$user} signed in with a recovery code; " . count($hashes) . ' left');

            return true;
        }
    }

    return false;
}

function twofa_recovery_left(string $user): int
{
    $row = twofa_record($user);

    return $row ? count(json_decode($row['recovery_json'], true) ?: []) : 0;
}
