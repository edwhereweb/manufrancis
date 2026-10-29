<?php /** @var ?string $error */ ?>
<form class="card form narrow" method="post" action="/admin/2fa" autocomplete="off">
  <p class="eyebrow">Step 2 of 2</p>
  <h1>Enter your code</h1>
  <p class="muted">Open your authenticator app and enter the 6-digit code for <strong>manufrancis.com</strong>.</p>
  <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
  <div class="row">
    <label>Authentication code
      <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="11" pattern="[0-9 ]{6,7}|[0-9a-fA-F]{5}-[0-9a-fA-F]{5}" autofocus>
    </label>
  </div>
  <button class="btn btn-accent btn-block" type="submit">Verify</button>
  <p class="muted small sub-tight">Lost your phone? Enter one of your recovery codes (like <code>3f9a2-c81d0</code>) instead.</p>
</form>
