<?php

declare(strict_types=1);

/** POST / : public contact form. Re-renders the form with errors, or redirects on success. */

$title = (require APP_ROOT . '/content/profile.php')['seo']['title'];
$fail = fn (array $errors, array $old = []) => view('home', ['title' => $title, 'errors' => $errors, 'old' => $old], 'layout', 422);

// Bots: honeypot filled in, or no valid signed token. Pretend success so they learn nothing.
if (($_POST['website'] ?? '') !== '') {
    redirect('/?sent=1#contact');
}
if (!same_origin() || !form_token_valid((string) ($_POST['token'] ?? ''))) {
    $fail(['form' => 'The form expired or was sent too quickly. Please check your details and send it again.'], $_POST);
}

$bucket = 'contact:' . ip_hash();
if (throttle_count($bucket, 3600) >= 5) {
    $fail(['form' => 'Too many messages from your network in the last hour. Please try again later.'], $_POST);
}

[$data, $errors] = lead_validate($_POST);
if ($errors) {
    $fail($errors, $data + ['consent' => $_POST['consent'] ?? '']);
}

throttle_hit($bucket);
$id = lead_create($data);
lead_notify($data, $id);
leads_purge_old();

redirect('/?sent=1#contact');
