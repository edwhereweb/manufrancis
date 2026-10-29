<?php

declare(strict_types=1);

/**
 * Everything under /admin.
 * Sign-in: /admin/login (password) -> /admin/2fa (code) or, the first time, /admin/2fa/setup (scan QR).
 * Every other route needs a fully signed-in admin.
 */

require_once APP_ROOT . '/lib/twofa.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// The admin needs these PHP extensions (sodium encrypts the 2FA secret). Say which are missing instead of failing later.
$missing = array_values(array_filter(['sodium', 'mbstring', 'pdo_mysql'], fn ($ext) => !extension_loaded($ext) && !($ext === 'pdo_mysql' && cfg('db_driver') !== 'mysql')));
if (!is_writable(storage_dir())) {
    $missing[] = 'write access to the storage/ folder';
}
if ($missing) {
    error_log('Admin unavailable, server is missing: ' . implode(', ', $missing));
    http_response_code(503);
    view('admin/server-check', ['title' => 'Server setup needed', 'missing' => $missing], 'admin/layout', 503);
}

// ---- Step 1: password ------------------------------------------------------
if ($path === '/admin/login') {
    if (admin_logged_in()) {
        redirect('/admin/leads');
    }
    $error = null;
    if (is_post()) {
        admin_csrf_check();
        $user = (string) ($_POST['user'] ?? '');
        $error = admin_password_step($user, (string) ($_POST['password'] ?? ''));
        if ($error === null) {
            redirect(twofa_enrolled($user) ? '/admin/2fa' : '/admin/2fa/setup');
        }
    }
    view('admin/login', ['title' => 'Sign in', 'error' => $error, 'notice' => login_notice()], 'admin/layout', $error ? 401 : 200);
}

// Already fully signed in: the sign-in steps have nothing to do.
if (in_array($path, ['/admin/2fa', '/admin/2fa/setup'], true) && admin_logged_in()) {
    redirect('/admin/leads');
}

// ---- Step 2: authenticator code --------------------------------------------
if ($path === '/admin/2fa') {
    $user = admin_pending_user() ?? redirect('/admin/login');
    if (!twofa_enrolled($user)) {
        redirect('/admin/2fa/setup');
    }
    $error = null;
    if (is_post()) {
        admin_csrf_check();
        $error = admin_code_step((string) ($_POST['code'] ?? ''));
        if ($error === null) {
            redirect('/admin/leads');
        }
        if (admin_pending_user() === null) {
            view('admin/login', ['title' => 'Sign in', 'error' => $error], 'admin/layout', 401);
        }
    }
    view('admin/2fa', ['title' => 'Two-step verification', 'error' => $error], 'admin/layout', $error ? 401 : 200);
}

// ---- First sign-in: set up the authenticator app ---------------------------
if ($path === '/admin/2fa/setup') {
    $user = admin_pending_user() ?? redirect('/admin/login');
    if (twofa_enrolled($user)) {
        redirect('/admin/2fa'); // Never show a new QR once 2FA exists; reset is done in the database.
    }
    $_SESSION['setup_secret'] ??= twofa_new_secret();
    $secret = $_SESSION['setup_secret'];
    $error = null;

    if (is_post()) {
        admin_csrf_check();
        $bucket = 'otp:' . $user;
        $step = throttle_count($bucket, LOGIN_WINDOW) < OTP_MAX_FAILS ? twofa_match($secret, (string) ($_POST['code'] ?? '')) : null;
        if ($step !== null) {
            throttle_clear($bucket);
            $codes = twofa_enroll($user, $secret, $step);
            audit('security', '2fa_enrolled', $user);
            admin_complete_login($user);
            $_SESSION['show_recovery'] = $codes;
            redirect('/admin/recovery-codes');
        }
        throttle_hit($bucket);
        $error = 'That code did not match. Make sure you scanned this QR code and enter the newest 6-digit code.';
    }

    view('admin/2fa-setup', [
        'title'  => 'Set up two-step verification',
        'qr'     => twofa_qr_data_uri($secret, $user),
        'secret' => twofa_pretty_secret($secret),
        'error'  => $error,
    ], 'admin/layout', $error ? 422 : 200);
}

if ($path === '/admin/logout' && is_post()) {
    admin_csrf_check();
    if (admin_logged_in()) {
        audit('signin', 'signout', (string) $_SESSION['admin']);
    }
    admin_logout();
    redirect('/admin/login?signed_out=1');
}

// ---- Forgot password: email a single-use link ------------------------------
if ($path === '/admin/forgot') {
    $sent = false;
    if (is_post()) {
        admin_csrf_check();
        $bucket = 'reset:' . ip_hash();
        if (throttle_count($bucket, 3600) < RESET_MAX_REQUESTS) {
            throttle_hit($bucket);
            reset_request(mb_substr(trim((string) ($_POST['user'] ?? '')), 0, 100));
        } else {
            audit('security', 'reset_fail', '', 'too many reset requests from this IP');
        }
        $sent = true; // Same answer either way, so the form reveals nothing.
    }
    view('admin/forgot', ['title' => 'Reset password', 'sent' => $sent], 'admin/layout');
}

// ---- Reset password from the emailed link ----------------------------------
if ($path === '/admin/reset') {
    header('Referrer-Policy: no-referrer'); // the token is in the URL
    $token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
    $user = reset_user($token);
    if ($user === null) {
        view('admin/reset', ['title' => 'Reset password', 'valid' => false], 'admin/layout', 410);
    }
    $needsCode = twofa_enrolled($user);
    $error = null;
    if (is_post()) {
        admin_csrf_check();
        $bucket = 'resetcode:' . $user;
        $new = (string) ($_POST['password'] ?? '');
        if (throttle_count($bucket, LOGIN_WINDOW) >= OTP_MAX_FAILS) {
            $error = 'Too many attempts. Wait 15 minutes and request a new link.';
        } elseif ($needsCode && !twofa_verify($user, (string) ($_POST['code'] ?? ''))) {
            throttle_hit($bucket);
            audit('security', 'reset_fail', $user, 'wrong authenticator code');
            $error = 'That authenticator code is not valid.';
        } elseif ($problem = password_problem($user, $new, (string) ($_POST['confirm'] ?? ''))) {
            $error = $problem;
        } elseif (!reset_consume($token)) {
            $error = 'This link has already been used. Request a new one.';
        } else {
            admin_set_password($user, $new);
            throttle_clear($bucket);
            throttle_clear('login:' . ip_hash());
            audit('security', 'reset_done', $user);
            admin_logout();
            redirect('/admin/login?reset=1');
        }
    }
    view('admin/reset', ['title' => 'Reset password', 'valid' => true, 'token' => $token, 'needsCode' => $needsCode, 'error' => $error], 'admin/layout', $error ? 422 : 200);
}

require_admin();

// ---- Recovery codes (shown once, right after setup or regeneration) ---------
if ($path === '/admin/recovery-codes') {
    $codes = $_SESSION['show_recovery'] ?? null;
    unset($_SESSION['show_recovery']);
    if (!$codes) {
        redirect('/admin/security');
    }
    view('admin/recovery-codes', ['title' => 'Recovery codes', 'codes' => $codes], 'admin/layout');
}

if ($path === '/admin/security') {
    $user = (string) $_SESSION['admin'];
    $errors = [];
    if (is_post()) {
        admin_csrf_check();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'recovery') {
            // Regenerating recovery codes needs a fresh authenticator code.
            $row = twofa_record($user);
            $secret = $row ? twofa_decrypt($row['secret_enc']) : null;
            $step = $secret ? twofa_match($secret, (string) ($_POST['code'] ?? ''), (int) $row['last_step']) : null;
            if ($step !== null) {
                $_SESSION['show_recovery'] = twofa_enroll($user, $secret, $step);
                audit('security', 'recovery_renewed', $user);
                redirect('/admin/recovery-codes');
            }
            $errors['recovery'] = 'That code is not valid.';
        }

        if ($action === 'password') {
            $bucket = 'pwchange:' . $user;
            $new = (string) ($_POST['password'] ?? '');
            if (throttle_count($bucket, LOGIN_WINDOW) >= LOGIN_MAX_FAILS) {
                $errors['password'] = 'Too many attempts. Try again in 15 minutes.';
            } elseif (!admin_password_ok($user, (string) ($_POST['current'] ?? ''))) {
                throttle_hit($bucket);
                audit('security', 'password_change_fail', $user, 'wrong current password');
                $errors['password'] = 'Your current password is not right.';
            } elseif (!twofa_verify($user, (string) ($_POST['code'] ?? ''))) {
                throttle_hit($bucket);
                audit('security', 'password_change_fail', $user, 'wrong authenticator code');
                $errors['password'] = 'That authenticator code is not valid.';
            } elseif ($problem = password_problem($user, $new, (string) ($_POST['confirm'] ?? ''))) {
                $errors['password'] = $problem;
            } else {
                admin_set_password($user, $new);
                admin_refresh_session($user);
                audit('security', 'password_changed', $user, 'other sessions signed out');
                redirect('/admin/security?changed=1');
            }
        }
    }
    view('admin/security', [
        'title'   => 'Security',
        'user'    => $user,
        'record'  => twofa_record($user),
        'left'    => twofa_recovery_left($user),
        'account' => admin_find($user),
        'must'    => isset($_GET['must']),
        'recent'  => array_slice(audit_page('signin', 1)['rows'], 0, 5),
        'changed' => isset($_GET['changed']),
        'errors'  => $errors,
    ], 'admin/layout');
}

// ---- Admin accounts ----------------------------------------------------------
if ($path === '/admin/admins') {
    $me = (string) $_SESSION['admin'];
    $error = null;
    $created = null;
    if (is_post()) {
        admin_csrf_check();
        $action = (string) ($_POST['action'] ?? '');
        $target = (string) ($_POST['username'] ?? '');
        $bucket = 'adminmgmt:' . $me;

        // Every change to accounts needs a fresh code from the signed-in admin's authenticator.
        if (throttle_count($bucket, LOGIN_WINDOW) >= OTP_MAX_FAILS) {
            $error = 'Too many wrong codes. Try again in 15 minutes.';
        } elseif (!twofa_verify($me, (string) ($_POST['code'] ?? ''))) {
            throttle_hit($bucket);
            audit('security', 'code_fail', $me, 'while managing admins');
            $error = 'That authenticator code is not valid.';
        } elseif ($action === 'create') {
            $username = mb_strtolower(trim((string) ($_POST['new_username'] ?? '')));
            $temp = temporary_password();
            $error = admin_create($username, trim((string) ($_POST['email'] ?? '')), $temp, $me);
            if ($error === null) {
                audit('security', 'admin_created', $username, 'by ' . $me);
                $created = ['username' => $username, 'password' => $temp];
            }
        } elseif (admin_find($target) === null) {
            $error = 'Unknown admin.';
        } elseif ($action === 'disable' || $action === 'enable') {
            $error = admin_set_active($target, $action === 'enable', $me);
            if ($error === null) {
                audit('security', 'admin_' . $action . 'd', $target, 'by ' . $me);
            }
        } elseif ($action === 'reset_2fa') {
            if ($target === $me) {
                $error = 'To change your own authenticator, ask another admin to reset it.';
            } else {
                db()->prepare('DELETE FROM admin_2fa WHERE username = ?')->execute([$target]);
                audit('security', 'admin_2fa_reset', $target, 'by ' . $me);
            }
        } elseif ($action === 'email') {
            $error = admin_update_email($target, trim((string) ($_POST['email'] ?? '')));
            if ($error === null) {
                audit('security', 'admin_email_changed', $target, 'by ' . $me);
            }
        }
    }
    $admins = admins_all();
    foreach ($admins as &$a) {
        $a['has_2fa'] = twofa_enrolled($a['username']);
    }
    unset($a);
    view('admin/admins', ['title' => 'Admins', 'me' => $me, 'admins' => $admins, 'error' => $error, 'created' => $created,
        'done' => is_post() && $error === null && $created === null], 'admin/layout', $error ? 422 : 200);
}

// ---- Activity and security log ---------------------------------------------
if ($path === '/admin/activity') {
    $cat = (string) ($_GET['type'] ?? '');
    $cat = isset(AUDIT_CATEGORIES[$cat]) ? $cat : '';
    $failures = ($_GET['failures'] ?? '') === '1';
    audit_purge();
    view('admin/activity', [
        'title'    => 'Activity',
        'cat'      => $cat,
        'failures' => $failures,
        'counts'   => audit_counts(),
        'log'      => audit_page($cat, (int) ($_GET['page'] ?? 1), $failures),
    ], 'admin/layout');
}

if ($path === '/admin' || $path === '/admin/leads') {
    leads_purge_old();
    $status = (string) ($_GET['status'] ?? '');
    $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    view('admin/leads', [
        'title'  => 'Leads',
        'leads'  => leads_list($status, $q),
        'counts' => leads_counts(),
        'status' => $status,
        'q'      => $q,
    ], 'admin/layout');
}

if ($path === '/admin/leads/export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
    $cols = ['id', 'created_at', 'status', 'name', 'email', 'phone', 'organisation', 'interest', 'message', 'notes', 'consent_at'];
    fputcsv($out, $cols);
    $all = leads_list();
    audit('data', 'lead_exported', (string) $_SESSION['admin'], count($all) . ' leads');
    foreach ($all as $lead) {
        fputcsv($out, array_map(fn ($c) => csv_safe((string) $lead[$c]), $cols));
    }
    exit;
}

if (preg_match('#^/admin/leads/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];
    $lead = lead_find($id) ?? not_found();

    if (is_post()) {
        admin_csrf_check();
        if (($_POST['action'] ?? '') === 'delete') {
            lead_delete($id);
            audit('data', 'lead_deleted', (string) $_SESSION['admin'], 'lead #' . $id);
            redirect('/admin/leads?deleted=1');
        }
        lead_update($id, (string) ($_POST['status'] ?? ''), (string) ($_POST['notes'] ?? ''));
        redirect("/admin/leads/{$id}?saved=1");
    }

    view('admin/lead', ['title' => 'Lead #' . $id, 'lead' => $lead, 'saved' => isset($_GET['saved'])], 'admin/layout');
}

not_found();
