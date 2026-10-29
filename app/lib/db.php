<?php

declare(strict_types=1);

/** SQLite database in storage/ (outside the web root). Schema is created on first use. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $dir = (string) cfg('storage_path');
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/leads.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 3000; PRAGMA foreign_keys = ON;');
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS leads (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at   TEXT NOT NULL,
            updated_at   TEXT NOT NULL,
            name         TEXT NOT NULL,
            email        TEXT NOT NULL,
            phone        TEXT NOT NULL DEFAULT '',
            organisation TEXT NOT NULL DEFAULT '',
            interest     TEXT NOT NULL DEFAULT '',
            message      TEXT NOT NULL,
            status       TEXT NOT NULL DEFAULT 'new',
            notes        TEXT NOT NULL DEFAULT '',
            consent_at   TEXT NOT NULL,
            consent_text TEXT NOT NULL
        )
        SQL);
    $pdo->exec('CREATE INDEX IF NOT EXISTS leads_created ON leads (created_at)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS throttle (
        bucket TEXT NOT NULL,
        at     INTEGER NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS throttle_bucket ON throttle (bucket, at)');

    return $pdo;
}

/** Count events for $bucket in the last $window seconds. Old rows are cleared as we go. */
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
