<?php

declare(strict_types=1);

/**
 * Admin audit trail: sign-ins, security events and data access, kept for 90 days.
 * Never put passwords, codes, secrets or tokens in a log entry.
 */

const AUDIT_RETENTION_DAYS = 90;
const AUDIT_PER_PAGE = 25;

const AUDIT_CATEGORIES = [
    'signin'   => 'Sign-ins',
    'security' => 'Security',
    'data'     => 'Data access',
];

/** Event => [label, tone]. Tone picks the badge colour: ok, warn, bad, info. */
const AUDIT_EVENTS = [
    'signin_ok'           => ['Signed in', 'ok'],
    'signout'             => ['Signed out', 'info'],
    'password_ok'         => ['Password accepted, waiting for code', 'info'],
    'password_fail'       => ['Wrong username or password', 'bad'],
    'password_locked'     => ['Sign-in blocked (too many passwords)', 'bad'],
    'code_fail'           => ['Wrong authenticator code', 'bad'],
    'code_locked'         => ['Sign-in locked (too many codes)', 'bad'],
    'recovery_code_used'  => ['Signed in with a recovery code', 'warn'],
    '2fa_enrolled'        => ['Two-step verification turned on', 'ok'],
    'recovery_renewed'    => ['New recovery codes made', 'warn'],
    'password_changed'    => ['Password changed', 'warn'],
    'password_change_fail'=> ['Password change refused', 'bad'],
    'reset_requested'     => ['Password reset requested', 'warn'],
    'reset_mail_failed'   => ['Reset email could not be sent', 'bad'],
    'reset_done'          => ['Password reset completed', 'warn'],
    'reset_fail'          => ['Password reset refused', 'bad'],
    'admin_created'       => ['Admin account created', 'warn'],
    'admin_disabled'      => ['Admin account disabled', 'warn'],
    'admin_enabled'       => ['Admin account enabled', 'warn'],
    'admin_2fa_reset'     => ['Two-step verification reset for admin', 'warn'],
    'admin_email_changed' => ['Admin email changed', 'warn'],
    'lead_exported'       => ['Leads exported to CSV', 'info'],
    'lead_deleted'        => ['Lead deleted', 'warn'],
];

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function audit(string $category, string $event, string $username = '', string $detail = ''): void
{
    try {
        db()->prepare('INSERT INTO security_log (at, category, event, username, ip, forwarded, user_agent, detail) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                db_now(),
                $category,
                $event,
                mb_substr($username, 0, 100),
                client_ip(),
                mb_substr((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''), 0, 100),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                mb_substr($detail, 0, 255),
            ]);
    } catch (Throwable $e) {
        // Logging must never break sign-in; fall back to the PHP error log.
        error_log("audit write failed ({$event}): " . $e->getMessage());
    }
}

function audit_purge(): void
{
    db()->prepare('DELETE FROM security_log WHERE at < ?')->execute([db_now(-86400 * AUDIT_RETENTION_DAYS)]);
}

/** @return array<string,int> entries per category (and 'all') */
function audit_counts(): array
{
    $counts = ['all' => 0] + array_fill_keys(array_keys(AUDIT_CATEGORIES), 0);
    foreach (db()->query('SELECT category, COUNT(*) c FROM security_log GROUP BY category') as $row) {
        $counts[$row['category']] = (int) $row['c'];
        $counts['all'] += (int) $row['c'];
    }

    return $counts;
}

/**
 * One page of log entries, newest first.
 *
 * @return array{rows: list<array<string,mixed>>, page: int, pages: int, total: int}
 */
function audit_page(string $category, int $page, bool $failuresOnly = false): array
{
    $where = [];
    $args = [];
    if (isset(AUDIT_CATEGORIES[$category])) {
        $where[] = 'category = ?';
        $args[] = $category;
    }
    if ($failuresOnly) {
        $bad = array_keys(array_filter(AUDIT_EVENTS, fn ($e) => $e[1] === 'bad'));
        $where[] = 'event IN (' . implode(',', array_fill(0, count($bad), '?')) . ')';
        array_push($args, ...$bad);
    }
    $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $st = db()->prepare('SELECT COUNT(*) FROM security_log' . $sqlWhere);
    $st->execute($args);
    $total = (int) $st->fetchColumn();
    $pages = max(1, (int) ceil($total / AUDIT_PER_PAGE));
    $page = min(max(1, $page), $pages);

    $st = db()->prepare('SELECT * FROM security_log' . $sqlWhere . ' ORDER BY at DESC, id DESC LIMIT ? OFFSET ?');
    $i = 1;
    foreach ($args as $a) {
        $st->bindValue($i++, $a);
    }
    $st->bindValue($i++, AUDIT_PER_PAGE, PDO::PARAM_INT);
    $st->bindValue($i, ($page - 1) * AUDIT_PER_PAGE, PDO::PARAM_INT);
    $st->execute();

    return ['rows' => $st->fetchAll(), 'page' => $page, 'pages' => $pages, 'total' => $total];
}

/** Short, readable device name from a user agent ("Chrome on Windows"). */
function ua_short(string $ua): string
{
    if ($ua === '') {
        return '-';
    }
    $browser = match (true) {
        str_contains($ua, 'Edg/')     => 'Edge',
        str_contains($ua, 'OPR/')     => 'Opera',
        str_contains($ua, 'Firefox/') => 'Firefox',
        str_contains($ua, 'Chrome/')  => 'Chrome',
        str_contains($ua, 'Safari/')  => 'Safari',
        str_contains($ua, 'curl/')    => 'curl',
        default                       => 'Other',
    };
    $os = match (true) {
        str_contains($ua, 'Windows')                                  => 'Windows',
        str_contains($ua, 'Android')                                  => 'Android',
        str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')      => 'iOS',
        str_contains($ua, 'Mac OS X')                                 => 'macOS',
        str_contains($ua, 'Linux')                                    => 'Linux',
        default                                                       => '',
    };

    return $os ? "{$browser} on {$os}" : $browser;
}
