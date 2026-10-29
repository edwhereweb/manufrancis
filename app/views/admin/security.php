<?php
/** @var string $user @var ?array $record @var int $left @var ?array $account @var list<array> $recent @var bool $changed @var bool $must @var array $errors */
$mustChange = $account && (int) $account['must_change'] === 1;
?>
<div class="admin-head"><h1>Security</h1></div>
<?php if ($mustChange) : ?><p class="form-error" role="alert">You're using a temporary password. Choose your own password to continue.</p><?php endif; ?>
<?php if ($changed) : ?><p class="notice-inline" role="status">Password changed. Your other sessions have been signed out.</p><?php endif; ?>

<div class="lead-grid">
  <div>
    <div class="card">
      <dl class="details">
        <dt>Account</dt><dd><?= e($user) ?></dd>
        <dt>Email</dt><dd><?= e($account['email'] ?? '') ?: '<span class="muted">None, so password reset by email will not work</span>' ?></dd>
        <dt>Password</dt><dd>Changed <?= e($account ? date('d M Y', strtotime($account['password_changed_at'])) : '-') ?></dd>
        <dt>Two-step</dt><dd><?php if ($record) : ?><span class="status status-won">On</span> since <?= e(date('d M Y', strtotime($record['created_at']))) ?><?php else : ?><span class="status status-contacted">Not set up</span><?php endif; ?></dd>
        <dt>Recovery codes</dt><dd><?= (int) $left ?> left<?= $record && $left <= 3 ? ' <span class="status status-contacted">Make new ones</span>' : '' ?></dd>
      </dl>
    </div>

    <div class="card sub-card">
      <div class="admin-head small-head"><h2 class="h-card">Recent sign-ins</h2><a class="small" href="/admin/activity?type=signin">All activity &rarr;</a></div>
      <?php if (!$recent) : ?><p class="muted small">Nothing yet.</p><?php endif; ?>
      <ul class="mini-log">
        <?php foreach ($recent as $r) : [$label, $tone] = AUDIT_EVENTS[$r['event']] ?? [$r['event'], 'info']; ?>
          <li><span class="dot-tone tone-<?= e($tone) ?>" aria-hidden="true"></span><span><?= e($label) ?><?= $r['username'] !== '' ? ' &middot; ' . e($r['username']) : '' ?><br>
            <span class="muted small"><?= e(date('d M, H:i', strtotime($r['at']))) ?> &middot; <?= e($r['ip']) ?> &middot; <?= e(ua_short($r['user_agent'])) ?></span></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div>
    <form class="card form" method="post" action="/admin/security" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <input type="hidden" name="action" value="password">
      <h2 class="h-card">Change password</h2>
      <?php if (isset($errors['password'])) : ?><p class="form-error" role="alert"><?= e($errors['password']) ?></p><?php endif; ?>
      <div class="row"><label>Current password <input type="password" name="current" required autocomplete="current-password"></label></div>
      <div class="row"><label>New password <input type="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password"></label>
        <p class="muted small">At least <?= PASSWORD_MIN_LENGTH ?> characters.</p></div>
      <div class="row"><label>Repeat new password <input type="password" name="confirm" required autocomplete="new-password"></label></div>
      <div class="row"><label>Authenticator code <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="11"></label></div>
      <button class="btn btn-accent" type="submit">Change password</button>
      <p class="muted small sub-tight">This signs out your other sessions.</p>
    </form>

    <?php if ($record) : ?>
    <form class="card form sub-card" method="post" action="/admin/security" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <input type="hidden" name="action" value="recovery">
      <h2 class="h-card">New recovery codes</h2>
      <p class="muted small">Replaces all old codes. Enter a current code from your authenticator app to confirm.</p>
      <?php if (isset($errors['recovery'])) : ?><p class="form-error" role="alert"><?= e($errors['recovery']) ?></p><?php endif; ?>
      <div class="row inline">
        <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="7" aria-label="6-digit code">
        <button class="btn btn-accent" type="submit">Generate</button>
      </div>
    </form>
    <?php endif; ?>
    <p class="muted small sub-tight">Lost your phone and recovery codes? Another admin can reset your two-step verification on the Admins page. If you are the only admin, delete your row in the <code>admin_2fa</code> table in phpMyAdmin.</p>
  </div>
</div>
