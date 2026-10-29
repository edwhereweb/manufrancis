<?php

declare(strict_types=1);

/** Everything under /admin. Every route except login needs a signed-in admin. */

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if ($path === '/admin/login') {
    $error = null;
    if (is_post()) {
        admin_csrf_check();
        $error = admin_login((string) ($_POST['user'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($error === null) {
            redirect('/admin/leads');
        }
    }
    view('admin/login', ['title' => 'Sign in', 'error' => $error], 'admin/layout', $error ? 401 : 200);
}

if ($path === '/admin/logout' && is_post()) {
    admin_csrf_check();
    admin_logout();
    redirect('/admin/login');
}

require_admin();

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
