<?php /** @var bool $sent */ ?>
<div class="card form narrow">
  <p class="eyebrow">Password reset</p>
  <h1>Forgot your password?</h1>
  <?php if ($sent) : ?>
    <p class="notice-inline" role="status">If that username belongs to an active admin, a reset link is on its way to the admin's email address. It works once, for 30 minutes.</p>
    <p class="muted small">No email? Check spam, then ask another admin to help, or see the README for the database fallback.</p>
    <p><a href="/admin/login">&larr; Back to sign in</a></p>
  <?php else : ?>
    <p class="muted">Enter your username. We'll email a reset link to the address on your admin account. You'll also need your authenticator app.</p>
    <form method="post" action="/admin/forgot">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <div class="row"><label>Username <input name="user" required autocomplete="username" autocapitalize="none" spellcheck="false"></label></div>
      <button class="btn btn-accent btn-block" type="submit">Email me a reset link</button>
    </form>
    <p class="small sub-tight"><a href="/admin/login">&larr; Back to sign in</a></p>
  <?php endif; ?>
</div>
