<?php /** @var string $title @var string $content */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> | Admin</title>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
</head>
<body class="admin">
  <header class="nav">
    <div class="wrap nav-inner">
      <a class="brand" href="/admin/leads"><span class="brand-mark" aria-hidden="true">MF</span> Leads</a>
      <?php if (admin_logged_in()) : ?>
        <div class="admin-nav">
          <?php $here = request_path(); ?>
          <a href="/admin/leads"<?= str_starts_with($here, '/admin/leads') ? ' aria-current="page"' : '' ?>>Leads</a>
          <a href="/admin/activity"<?= $here === '/admin/activity' ? ' aria-current="page"' : '' ?>>Activity</a>
          <a href="/admin/admins"<?= $here === '/admin/admins' ? ' aria-current="page"' : '' ?>>Admins</a>
          <a href="/admin/security"<?= $here === '/admin/security' ? ' aria-current="page"' : '' ?>>Security</a>
          <form method="post" action="/admin/logout">
            <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
            <button class="btn btn-sm btn-ghost" type="submit">Sign out</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </header>
  <main class="section"><div class="wrap"><?= $content ?></div></main>
</body>
</html>
