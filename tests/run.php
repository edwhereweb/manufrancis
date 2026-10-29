<?php

declare(strict_types=1);

/**
 * Tiny test runner, no test framework. Run: php tests/run.php
 * Uses SQLite in a temp folder, or MySQL when TEST_DB_NAME is set (CI does this):
 *   TEST_DB_HOST=127.0.0.1 TEST_DB_NAME=mf_test TEST_DB_USER=mf TEST_DB_PASS=... php tests/run.php
 * WARNING: with MySQL the test drops and recreates the tables in that database.
 */

$tmp = sys_get_temp_dir() . '/mf-test-' . bin2hex(random_bytes(4));
mkdir($tmp);
$mysql = (string) getenv('TEST_DB_NAME') !== '';
$config = ['env' => 'local', 'app_key' => str_repeat('k', 40), 'storage_path' => $tmp, 'retention_days' => 30, 'admin_user' => 'admin', 'admin_password_hash' => password_hash('first-admin-pass-123', PASSWORD_DEFAULT), 'admin_email' => 'admin@example.test']
    + ($mysql
        ? ['db_driver' => 'mysql', 'db_host' => getenv('TEST_DB_HOST') ?: '127.0.0.1', 'db_port' => (int) (getenv('TEST_DB_PORT') ?: 3306),
           'db_name' => getenv('TEST_DB_NAME'), 'db_user' => getenv('TEST_DB_USER'), 'db_pass' => getenv('TEST_DB_PASS')]
        : ['db_driver' => 'sqlite']);
file_put_contents($tmp . '/config.php', '<?php return ' . var_export($config, true) . ';');
putenv('MF_CONFIG_FILE=' . $tmp . '/config.php');

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/lib/twofa.php';

echo 'Database: ' . ($mysql ? 'MySQL' : 'SQLite') . PHP_EOL;
if ($mysql) {
    // Start clean: drop tables, then let db() recreate them.
    $raw = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s', $config['db_host'], $config['db_port'], $config['db_name']), $config['db_user'], $config['db_pass']);
    $raw->exec('DROP TABLE IF EXISTS leads, throttle, admin_2fa, admins, password_resets, security_log');
}

$failed = 0;
function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}

$valid = ['name' => ' Asha  K ', 'email' => 'Asha@Example.com', 'phone' => '+91 98765 43210', 'organisation' => 'Acme',
    'interest' => 'training', 'message' => 'We need a CEH workshop for 40 staff.', 'consent' => 'yes'];

echo "Validation\n";
[$d, $err] = lead_validate($valid);
check('valid input passes', $err === []);
check('name whitespace is collapsed', $d['name'] === 'Asha K');
check('email is lower-cased', $d['email'] === 'asha@example.com');
check('missing consent is rejected', isset(lead_validate(['consent' => ''] + $valid)[1]['consent']));
check('bad email is rejected', isset(lead_validate(['email' => "a@b.com\r\nBcc: x@y.z"] + $valid)[1]['email']));
check('unknown topic is rejected', isset(lead_validate(['interest' => 'hack'] + $valid)[1]['interest']));
check('bad phone is rejected', isset(lead_validate(['phone' => '<script>'] + $valid)[1]['phone']));
check('long message is cut to 4000', mb_strlen(lead_validate(['message' => str_repeat('a', 9000)] + $valid)[0]['message']) === 4000);

echo "Form token\n";
$t = form_token();
check('fresh token is rejected (too fast)', !form_token_valid($t));
check('token is accepted after 3s', form_token_valid($t, time() + 5));
check('token expires after 2h', !form_token_valid($t, time() + 7300));
check('tampered token is rejected', !form_token_valid(substr($t, 0, -1) . 'x', time() + 5));
check('garbage token is rejected', !form_token_valid('abc', time() + 5));

echo "CSV\n";
check('formula is neutralised', csv_safe('=HYPERLINK("x")') === "'=HYPERLINK(\"x\")");
check('plain text untouched', csv_safe('Hello') === 'Hello');

echo "Leads store\n";
$id = lead_create($d);
check('lead is saved', lead_find($id)['email'] === 'asha@example.com');
lead_update($id, 'contacted', 'Called back');
check('status and notes update', lead_find($id)['status'] === 'contacted' && lead_find($id)['notes'] === 'Called back');
lead_update($id, 'bogus', 'x');
check('invalid status is ignored', lead_find($id)['status'] === 'contacted');
check('search finds lead', count(leads_list('', 'acme')) === 1);
check('LIKE wildcards are escaped', count(leads_list('', '%')) === 0);
lead_create(['message' => 'Budget is 50% of last year'] + $d);
check('literal % can be searched', count(leads_list('', '50%')) === 1);
db()->prepare('UPDATE leads SET created_at = ? WHERE id = ?')->execute([db_now(-86400 * 40), $id]);
check('old leads are purged', leads_purge_old() === 1 && lead_find($id) === null);
$id2 = lead_create($d);
lead_delete($id2);
check('delete erases lead', lead_find($id2) === null);

echo "Throttle\n";
for ($i = 0; $i < 3; $i++) {
    throttle_hit('t');
}
check('throttle counts hits', throttle_count('t', 60) === 3);
throttle_clear('t');
check('throttle clears', throttle_count('t', 60) === 0);

echo "Two-step verification\n";
$secret = twofa_new_secret();
check('secret is 160-bit base32 (32 chars)', strlen($secret) === 32 && preg_match('/^[A-Z2-7]+$/', $secret) === 1);
$totp = OTPHP\TOTP::createFromSecret($secret);
$now = time();
$code = $totp->at($now);
check('current code matches', twofa_match($secret, $code, 0, $now) === intdiv($now, 30));
check('code with a space matches', twofa_match($secret, substr($code, 0, 3) . ' ' . substr($code, 3), 0, $now) !== null);
check('previous window accepted (clock drift)', twofa_match($secret, $totp->at($now - 30), 0, $now) !== null);
check('code from 2 minutes ago rejected', twofa_match($secret, $totp->at($now - 120), 0, $now) === null);
check('wrong code rejected', twofa_match($secret, $code === '000000' ? '111111' : '000000', 0, $now) === null);
check('non-numeric rejected', twofa_match($secret, 'abcdef', 0, $now) === null);
check('already-used step rejected', twofa_match($secret, $code, intdiv($now, 30), $now) === null);

$enc = twofa_encrypt($secret);
check('secret is encrypted at rest', !str_contains($enc, $secret) && twofa_decrypt($enc) === $secret);
check('tampered ciphertext fails', twofa_decrypt(substr($enc, 0, -4) . 'AAAA') === null);
check('QR renders as SVG data URI', str_starts_with(twofa_qr_data_uri($secret, 'admin'), 'data:image/svg+xml;base64,'));

$codes = twofa_enroll('admin', $secret, intdiv($now, 30) - 2);
check('10 recovery codes issued', count($codes) === 10 && preg_match('/^[0-9a-f]{5}-[0-9a-f]{5}$/', $codes[0]) === 1);
check('stored row has no plain secret', !str_contains(json_encode(twofa_record('admin')), $secret));
$live = $totp->at(time());
check('valid code verifies once', twofa_verify('admin', $live));
check('same code cannot be replayed', !twofa_verify('admin', $live));
check('recovery code works', twofa_verify('admin', strtoupper($codes[3])));
check('recovery code is single-use', !twofa_verify('admin', $codes[3]) && twofa_recovery_left('admin') === 9);
check('unknown user fails', !twofa_verify('nobody', $totp->at(time())));

echo "Admin accounts\n";
check('first admin is created from config', admin_find('admin') !== null && admin_find('admin')['email'] === 'admin@example.test');
admins_seed();
check('seeding does not duplicate', count(admins_all()) === 1);
check('right password accepted', admin_password_ok('admin', 'first-admin-pass-123'));
check('wrong password rejected', !admin_password_ok('admin', 'nope'));
check('unknown user rejected', !admin_password_ok('ghost', 'first-admin-pass-123'));
check('short password refused', password_problem('admin', 'short', 'short') !== null);
check('mismatch refused', password_problem('admin', 'long-enough-phrase-1', 'long-enough-phrase-2') !== null);
check('username inside password refused', password_problem('admin', 'my-admin-is-great-1', 'my-admin-is-great-1') !== null);
check('repetitive password refused', password_problem('admin', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaa') !== null);
check('current password refused', password_problem('admin', 'first-admin-pass-123', 'first-admin-pass-123') !== null);
check('good password accepted', password_problem('admin', 'violet tiger orbit quartz', 'violet tiger orbit quartz') === null);
$v1 = admin_password_version('admin');
sleep(1);
admin_set_password('admin', 'violet tiger orbit quartz');
check('password change works', admin_password_ok('admin', 'violet tiger orbit quartz') && !admin_password_ok('admin', 'first-admin-pass-123'));
check('password change bumps session version', admin_password_version('admin') !== $v1);

check('bad username refused', admin_create('Bad Name', 'x@example.test', 'temp', 'admin') !== null);
check('bad email refused', admin_create('second', 'not-an-email', 'temp', 'admin') !== null);
$temp = temporary_password();
check('temporary password is long', strlen($temp) >= 20);
check('new admin created', admin_create('second', 'Second@Example.test', $temp, 'admin') === null);
check('duplicate username refused', admin_create('second', 'x@example.test', 'temp', 'admin') !== null);
check('new admin must change password', (int) admin_find('second')['must_change'] === 1 && admin_password_ok('second', $temp));
check('email is lower-cased', admin_find('second')['email'] === 'second@example.test');
check('cannot disable yourself', admin_set_active('admin', false, 'admin') !== null);
check('can disable another admin', admin_set_active('second', false, 'admin') === null && !admin_password_ok('second', $temp));
check('disabled admin has no session version', admin_password_version('second') === 'disabled');
check('cannot disable last active admin', admin_set_active('admin', false, 'second') !== null);
admin_set_active('second', true, 'admin');

echo "Password reset\n";
$token = bin2hex(random_bytes(32));
db()->prepare('INSERT INTO password_resets (token_hash, username, created_at, expires_at) VALUES (?, ?, ?, ?)')
    ->execute([hash('sha256', $token), 'admin', db_now(), db_now(RESET_TTL)]);
check('valid token finds admin', reset_user($token) === 'admin');
check('malformed token rejected', reset_user('xyz') === null && reset_user(str_repeat('0', 64)) === null);
check('token consumed once', reset_consume($token) && !reset_consume($token));
check('used token rejected', reset_user($token) === null);
$old = bin2hex(random_bytes(32));
db()->prepare('INSERT INTO password_resets (token_hash, username, created_at, expires_at) VALUES (?, ?, ?, ?)')
    ->execute([hash('sha256', $old), 'admin', db_now(-3600), db_now(-1800)]);
check('expired token rejected', reset_user($old) === null);
reset_request('ghost');
check('unknown user reset is only logged', (int) db()->query("SELECT COUNT(*) FROM password_resets WHERE username = 'ghost'")->fetchColumn() === 0);

echo "Audit log\n";
db()->exec('DELETE FROM security_log');
for ($i = 0; $i < 30; $i++) {
    audit('signin', $i % 3 === 0 ? 'password_fail' : 'signin_ok', 'admin');
}
audit('data', 'lead_exported', 'admin', '3 leads');
$counts = audit_counts();
check('counts by category', $counts['all'] === 31 && $counts['signin'] === 30 && $counts['data'] === 1);
$p1 = audit_page('', 1);
check('page 1 has 25 rows of 2 pages', count($p1['rows']) === 25 && $p1['pages'] === 2 && $p1['total'] === 31);
check('page 2 has the rest', count(audit_page('', 2)['rows']) === 6);
check('out-of-range page is clamped', audit_page('', 99)['page'] === 2);
check('category filter', audit_page('data', 1)['total'] === 1);
check('failures filter', audit_page('', 1, true)['total'] === 10);
db()->prepare('UPDATE security_log SET at = ? WHERE event = ?')->execute([db_now(-86400 * 100), 'lead_exported']);
audit_purge();
check('entries older than 90 days purged', audit_counts()['data'] === 0);
check('device names are readable', ua_short('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140 Safari/537.36') === 'Chrome on Windows');

echo $failed ? "\n{$failed} failed\n" : "\nAll passed\n";
exit($failed ? 1 : 0);
