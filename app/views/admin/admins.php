<?php /** @var string $me @var list<array> $admins @var ?string $error @var ?array $created @var bool $done */ ?>
<div class="admin-head"><h1>Admins</h1></div>
<?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($done) : ?><p class="notice-inline" role="status">Saved.</p><?php endif; ?>
<?php if ($created) : ?>
  <div class="card created" role="status">
    <h2 class="h-card">Account created for <?= e($created['username']) ?></h2>
    <p class="muted small">Send this temporary password privately (not in the same message as the username). It is shown only now. They must change it and set up their authenticator at first sign-in.</p>
    <p class="secret"><code><?= e($created['password']) ?></code></p>
  </div>
<?php endif; ?>

<div class="table-wrap">
  <table class="leads">
    <thead><tr><th>Admin</th><th>Status</th><th>Two-step</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($admins as $a) : $active = (int) $a['is_active'] === 1; ?>
        <tr>
          <td><strong><?= e($a['username']) ?></strong><?= $a['username'] === $me ? ' <span class="muted small">(you)</span>' : '' ?><br><span class="muted small"><?= e($a['email'] ?: 'no email') ?></span></td>
          <td>
            <span class="status <?= $active ? 'status-won' : 'status-closed' ?>"><?= $active ? 'Active' : 'Disabled' ?></span>
            <?php if ((int) $a['must_change'] === 1) : ?><br><span class="status status-contacted">Temporary password</span><?php endif; ?>
          </td>
          <td><?= $a['has_2fa'] ? '<span class="status status-won">On</span>' : '<span class="status status-contacted">Not set up</span>' ?></td>
          <td class="small"><?= e(date('d M Y', strtotime($a['created_at']))) ?><br><span class="muted">by <?= e($a['created_by'] ?: '-') ?></span></td>
          <td>
            <details class="actions">
              <summary>Manage</summary>
              <form class="form" method="post" action="/admin/admins" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
                <input type="hidden" name="username" value="<?= e($a['username']) ?>">
                <div class="row"><label>Email <input type="email" name="email" value="<?= e($a['email']) ?>"></label></div>
                <div class="row"><label>Your authenticator code <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="7"></label></div>
                <div class="btn-row">
                  <button class="btn btn-sm btn-ghost" name="action" value="email">Save email</button>
                  <?php if ($a['username'] !== $me) : ?>
                    <button class="btn btn-sm btn-ghost" name="action" value="<?= $active ? 'disable' : 'enable' ?>"><?= $active ? 'Disable' : 'Enable' ?></button>
                    <?php if ($a['has_2fa']) : ?><button class="btn btn-sm btn-ghost" name="action" value="reset_2fa">Reset two-step</button><?php endif; ?>
                  <?php endif; ?>
                </div>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<form class="card form sub-card narrow-left" method="post" action="/admin/admins" autocomplete="off">
  <h2 class="h-card">Add an admin</h2>
  <p class="muted small">They get a one-time temporary password, must choose their own at first sign-in, and must set up an authenticator app.</p>
  <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
  <input type="hidden" name="action" value="create">
  <div class="row two">
    <div><label>Username <input name="new_username" required pattern="[a-z0-9][a-z0-9._\-]{2,49}" autocapitalize="none" spellcheck="false"></label></div>
    <div><label>Email <input type="email" name="email" required></label></div>
  </div>
  <div class="row"><label>Your authenticator code <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="7"></label></div>
  <button class="btn btn-accent" type="submit">Create admin</button>
</form>
