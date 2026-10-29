<?php

declare(strict_types=1);

/**
 * Database connection. Production uses MySQL (Hostinger); tests and local dev may use SQLite.
 * Tables are created by db_migrate() the first time a schema version is seen.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (cfg('db_driver') === 'sqlite') {
        $pdo = new PDO('sqlite:' . storage_dir() . '/leads.sqlite', null, null, $options);
        $pdo->exec('PRAGMA busy_timeout = 3000');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), (int) cfg('db_port'), cfg('db_name'));
        $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), $options);
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");
    }

    db_migrate($pdo);

    return $pdo;
}

function storage_dir(): string
{
    $dir = (string) cfg('storage_path');
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    return $dir;
}

/** Current time as stored in the database (works for MySQL DATETIME and SQLite text). */
function db_now(int $offsetSeconds = 0): string
{
    return date('Y-m-d H:i:s', time() + $offsetSeconds);
}

const SCHEMA_VERSION = 2;

/** Create tables once per schema version. A marker file avoids running DDL on every request. */
function db_migrate(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    // Marker is per database, so pointing at a new/empty database runs the migration again.
    $dbId = substr(hash('sha256', $driver . '|' . cfg('db_host') . '|' . cfg('db_name')), 0, 12);
    $marker = storage_dir() . "/schema-{$dbId}-v" . SCHEMA_VERSION . '.ok';
    if (is_file($marker)) {
        return;
    }

    $mysql = $driver === 'mysql';
    $id = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $engine = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $time = $mysql ? 'DATETIME' : 'TEXT';

    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id           {$id},
        created_at   {$time} NOT NULL,
        updated_at   {$time} NOT NULL,
        name         VARCHAR(120) NOT NULL,
        email        VARCHAR(190) NOT NULL,
        phone        VARCHAR(30)  NOT NULL DEFAULT '',
        organisation VARCHAR(160) NOT NULL DEFAULT '',
        interest     VARCHAR(30)  NOT NULL DEFAULT '',
        message      TEXT NOT NULL,
        status       VARCHAR(20)  NOT NULL DEFAULT 'new',
        notes        TEXT NOT NULL,
        consent_at   {$time} NOT NULL,
        consent_text VARCHAR(255) NOT NULL
    ){$engine}");

    $pdo->exec("CREATE TABLE IF NOT EXISTS throttle (
        bucket VARCHAR(120) NOT NULL,
        at     INT NOT NULL
    ){$engine}");

    // One row per admin username. The TOTP secret is encrypted with app_key (see twofa.php).
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_2fa (
        username      VARCHAR(100) NOT NULL PRIMARY KEY,
        secret_enc    TEXT NOT NULL,
        last_step     BIGINT NOT NULL DEFAULT 0,
        recovery_json TEXT NOT NULL,
        created_at    {$time} NOT NULL
    ){$engine}");

    // Index creation is not idempotent in MySQL, so ignore "already exists".
    foreach (['CREATE INDEX leads_created ON leads (created_at)', 'CREATE INDEX leads_status ON leads (status)', 'CREATE INDEX throttle_bucket ON throttle (bucket, at)'] as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException) {
        }
    }

    @file_put_contents($marker, db_now());
}

/** Count events for $bucket in the last $window seconds. Rows older than a day are cleared as we go. */
function throttle_count(string $bucket, int $window): int
{
    db()->prepare('DELETE FROM throttle WHERE at < ?')->execute([time() - 86400]);
    $st = db()->prepare('SELECT COUNT(*) FROM throttle WHERE bucket = ? AND at > ?');
    $st->execute([$bucket, time() - $window]);

    return (int) $st->fetchColumn();
}

function throttle_hit(string $bucket): void
{
    db()->prepare('INSERT INTO throttle (bucket, at) VALUES (?, ?)')->execute([$bucket, time()]);
}

function throttle_clear(string $bucket): void
{
    db()->prepare('DELETE FROM throttle WHERE bucket = ?')->execute([$bucket]);
}
