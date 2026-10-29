<?php

declare(strict_types=1);

/**
 * Make the ADMIN_PASSWORD_HASH secret. The password is read without echoing where possible.
 * Usage: php bin/hash-password.php
 */

fwrite(STDOUT, 'Admin password (min 14 chars): ');
if (PHP_OS_FAMILY !== 'Windows') {
    shell_exec('stty -echo');
}
$password = trim((string) fgets(STDIN));
if (PHP_OS_FAMILY !== 'Windows') {
    shell_exec('stty echo');
}
fwrite(STDOUT, PHP_EOL);

if (strlen($password) < 14) {
    fwrite(STDERR, "Use at least 14 characters.\n");
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;
