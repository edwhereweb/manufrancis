<?php

declare(strict_types=1);

/**
 * Tiny test runner, no dependencies. Run: php tests/run.php
 * Uses a throwaway config and database in the system temp folder.
 */

$tmp = sys_get_temp_dir() . '/mf-test-' . bin2hex(random_bytes(4));
mkdir($tmp);
file_put_contents($tmp . '/config.php', '<?php return ' . var_export([
    'env' => 'local', 'app_key' => str_repeat('k', 40), 'storage_path' => $tmp, 'retention_days' => 30,
], true) . ';');
putenv('MF_CONFIG_FILE=' . $tmp . '/config.php');

require dirname(__DIR__) . '/app/bootstrap.php';

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
db()->prepare('UPDATE leads SET created_at = ? WHERE id = ?')->execute([date('c', time() - 86400 * 40), $id]);
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

echo $failed ? "\n{$failed} failed\n" : "\nAll passed\n";
exit($failed ? 1 : 0);
