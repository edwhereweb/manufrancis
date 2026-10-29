<?php

declare(strict_types=1);

const LEAD_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'won' => 'Won', 'closed' => 'Closed'];

const LEAD_INTERESTS = [
    'cybersecurity'  => 'Cybersecurity services',
    'infrastructure' => 'IT network infrastructure',
    'software'       => 'Software engineering & architecture',
    'training'       => 'Training & workshops',
    'speaking'       => 'Speaking / faculty development',
    'other'          => 'Something else',
];

/** Bump when the consent wording on the form changes, so each lead records what it agreed to. */
const CONSENT_TEXT = 'v1: I agree that Manu Francis may store and use the details in this form to reply to my enquiry, as described in the privacy policy.';

/**
 * Validate the public contact form.
 *
 * @return array{0: array<string,string>, 1: array<string,string>} [clean data, errors by field]
 */
function lead_validate(array $in): array
{
    $clean = fn (string $k, int $max): string => mb_substr(trim(str_replace("\0", '', (string) ($in[$k] ?? ''))), 0, $max);
    $d = [
        'name'         => preg_replace('/\s+/u', ' ', $clean('name', 120)),
        'email'        => strtolower($clean('email', 190)),
        'phone'        => $clean('phone', 30),
        'organisation' => $clean('organisation', 160),
        'interest'     => $clean('interest', 30),
        'message'      => $clean('message', 4000),
    ];
    $errors = [];

    if (mb_strlen($d['name']) < 2) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($d['phone'] !== '' && !preg_match('/^\+?[0-9 ()\-]{6,30}$/', $d['phone'])) {
        $errors['phone'] = 'Please use digits, spaces, + ( ) or - only.';
    }
    if (!array_key_exists($d['interest'], LEAD_INTERESTS)) {
        $errors['interest'] = 'Please choose a topic.';
    }
    if (mb_strlen($d['message']) < 10) {
        $errors['message'] = 'Please tell me a little more (at least 10 characters).';
    }
    if (($in['consent'] ?? '') !== 'yes') {
        $errors['consent'] = 'Please agree to the privacy policy so I can reply.';
    }

    return [$d, $errors];
}

function lead_create(array $d): int
{
    $now = date('c');
    db()->prepare('INSERT INTO leads (created_at, updated_at, name, email, phone, organisation, interest, message, consent_at, consent_text)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$now, $now, $d['name'], $d['email'], $d['phone'], $d['organisation'], $d['interest'], $d['message'], $now, CONSENT_TEXT]);

    return (int) db()->lastInsertId();
}

/** @return list<array<string,mixed>> */
function leads_list(string $status = '', string $search = ''): array
{
    $sql = 'SELECT * FROM leads WHERE 1=1';
    $args = [];
    if (isset(LEAD_STATUSES[$status])) {
        $sql .= ' AND status = ?';
        $args[] = $status;
    }
    if ($search !== '') {
        $sql .= " AND (name LIKE ? ESCAPE '\\' OR email LIKE ? ESCAPE '\\' OR organisation LIKE ? ESCAPE '\\' OR message LIKE ? ESCAPE '\\')";
        $like = '%' . addcslashes($search, '%_\\') . '%';
        array_push($args, $like, $like, $like, $like);
    }
    $st = db()->prepare($sql . ' ORDER BY created_at DESC LIMIT 500');
    $st->execute($args);

    return $st->fetchAll();
}

/** @return array<string,int> count of leads per status */
function leads_counts(): array
{
    $counts = array_fill_keys(array_keys(LEAD_STATUSES), 0);
    foreach (db()->query('SELECT status, COUNT(*) c FROM leads GROUP BY status') as $row) {
        $counts[$row['status']] = (int) $row['c'];
    }

    return $counts;
}

function lead_find(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch() ?: null;
}

function lead_update(int $id, string $status, string $notes): void
{
    if (!isset(LEAD_STATUSES[$status])) {
        return;
    }
    db()->prepare('UPDATE leads SET status = ?, notes = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, mb_substr($notes, 0, 10000), date('c'), $id]);
}

/** Permanent erasure (GDPR Art. 17). */
function lead_delete(int $id): void
{
    db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
}

/** Storage limitation (GDPR Art. 5(1)(e)): drop leads older than the retention period. */
function leads_purge_old(): int
{
    $cutoff = date('c', time() - 86400 * (int) cfg('retention_days'));
    $st = db()->prepare('DELETE FROM leads WHERE created_at < ?');
    $st->execute([$cutoff]);

    return $st->rowCount();
}

/** Email a short alert. Only fixed headers; user input goes in the body (no header injection). */
function lead_notify(array $d, int $id): void
{
    $to = (string) cfg('notify_email');
    $from = (string) cfg('mail_from');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $body = "New enquiry #{$id} on " . cfg('site_url') . "\n\n"
        . "Name: {$d['name']}\nEmail: {$d['email']}\nPhone: {$d['phone']}\nOrganisation: {$d['organisation']}\n"
        . 'Topic: ' . (LEAD_INTERESTS[$d['interest']] ?? $d['interest']) . "\n\n{$d['message']}\n\n"
        . 'Manage: ' . rtrim((string) cfg('site_url'), '/') . "/admin/leads/{$id}\n";
    $headers = "From: Manu Francis website <{$from}>\r\nReply-To: {$d['email']}\r\nContent-Type: text/plain; charset=UTF-8";
    if (!@mail($to, 'New website enquiry #' . $id, $body, $headers)) {
        error_log("Lead #{$id}: notification email could not be sent");
    }
}
