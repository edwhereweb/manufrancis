<?php /** @var string $qr @var string $secret @var ?string $error */ ?>
<div class="card setup">
  <p class="eyebrow">One-time setup</p>
  <h1>Turn on two-step verification</h1>
  <p class="muted">Every sign-in will need your password and a code from your phone.</p>

  <ol class="steps">
    <li>
      <h2>Install an authenticator app</h2>
      <p class="muted small">Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden or Authy all work.</p>
    </li>
    <li>
      <h2>Scan this QR code</h2>
      <div class="qr-row">
        <img class="qr" src="<?= e($qr) ?>" width="220" height="220" alt="QR code for your authenticator app">
        <div>
          <p class="muted small">Can't scan? Choose "enter a setup key" in the app and type:</p>
          <p class="secret"><code><?= e($secret) ?></code></p>
          <p class="muted small">Account: manufrancis.com &middot; Type: time-based</p>
        </div>
      </div>
    </li>
    <li>
      <h2>Enter the 6-digit code it shows</h2>
      <form class="form" method="post" action="/admin/2fa/setup" autocomplete="off">
        <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
        <div class="row inline">
          <input class="otp" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="7" pattern="[0-9 ]{6,7}" aria-label="6-digit code">
          <button class="btn btn-accent" type="submit">Turn on</button>
        </div>
      </form>
    </li>
  </ol>
  <p class="muted small">Keep this page private. Anyone who sees the QR code or setup key can create codes for this account.</p>
</div>
