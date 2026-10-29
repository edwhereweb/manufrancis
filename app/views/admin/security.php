<?php /** @var string $user @var ?array $record @var int $left @var ?string $error */ ?>
<div class="admin-head"><h1>Security</h1></div>
<div class="lead-grid">
  <div class="card">
    <dl class="details">
      <dt>Account</dt><dd><?= e($user) ?></dd>
      <dt>Two-step</dt><dd><span class="status status-won">On</span> since <?= e($record ? date('d M Y', strtotime($record['created_at'])) : '-') ?></dd>
      <dt>Recovery codes</dt><dd><?= (int) $left ?> left<?= $left <= 3 ? ' <span class="status status-contacted">Make new ones</span>' : '' ?></dd>
    </dl>
    <p class="muted small sub-tight">Lost the phone and all recovery codes? Delete your row in the <code>admin_2fa</code> table in phpMyAdmin (Hostinger hPanel &rarr; Databases). The next sign-in will ask you to set up the authenticator again.</p>
  </div>
  <form class="card form" method="post" action="/admin/security" autocomplete="off">
    <h2 class="h-card">New recovery codes</h2>
    <p class="muted small">Replaces all old codes. Enter a current code from your authenticator app to confirm.</p>
    <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
    <div class="row inline">
      <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="7" aria-label="6-digit code">
      <button class="btn btn-accent" type="submit">Generate</button>
    </div>
  </form>
</div>
