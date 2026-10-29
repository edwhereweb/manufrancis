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
    view('admin/login', ['title' => 'Sign in', 'error' => $error], 'admin/layout', $error ? 401 : 200);
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
    admin_logout();
    redirect('/admin/login');
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
    $error = null;
    if (is_post()) {
        admin_csrf_check();
        // Regenerating recovery codes needs a fresh authenticator code.
        $row = twofa_record($user);
        $secret = $row ? twofa_decrypt($row['secret_enc']) : null;
        $step = $secret ? twofa_match($secret, (string) ($_POST['code'] ?? ''), (int) $row['last_step']) : null;
        if ($step !== null) {
            $_SESSION['show_recovery'] = twofa_enroll($user, $secret, $step);
            redirect('/admin/recovery-codes');
        }
        $error = 'That code is not valid.';
    }
    view('admin/security', [
        'title'    => 'Security',
        'user'     => $user,
        'record'   => twofa_record($user),
        'left'     => twofa_recovery_left($user),
        'error'    => $error,
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
    foreach (leads_list() as $lead) {
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
            redirect('/admin/leads?deleted=1');
        }
        lead_update($id, (string) ($_POST['status'] ?? ''), (string) ($_POST['notes'] ?? ''));
        redirect("/admin/leads/{$id}?saved=1");
    }

    view('admin/lead', ['title' => 'Lead #' . $id, 'lead' => $lead, 'saved' => isset($_GET['saved'])], 'admin/layout');
}

not_found();
