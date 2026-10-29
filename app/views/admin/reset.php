<?php /** @var bool $valid @var string $token @var bool $needsCode @var ?string $error */ ?>
<div class="card form narrow">
  <p class="eyebrow">Password reset</p>
  <?php if (!$valid) : ?>
    <h1>This link has expired</h1>
    <p class="muted">Reset links work once and only for 30 minutes.</p>
    <a class="btn btn-accent" href="/admin/forgot">Request a new link</a>
  <?php else : ?>
    <h1>Choose a new password</h1>
    <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="/admin/reset" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="row"><label>New password <input type="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password"></label>
        <p class="muted small">At least <?= PASSWORD_MIN_LENGTH ?> characters. A phrase of four or five random words works well.</p></div>
      <div class="row"><label>Repeat new password <input type="password" name="confirm" required autocomplete="new-password"></label></div>
      <?php if ($needsCode) : ?>
        <div class="row"><label>Authenticator code <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="11"></label>
          <p class="muted small">Or one of your recovery codes.</p></div>
      <?php endif; ?>
      <button class="btn btn-accent btn-block" type="submit">Set new password</button>
    </form>
    <p class="muted small sub-tight">This signs you out everywhere. Sign in again with the new password.</p>
  <?php endif; ?>
</div>
