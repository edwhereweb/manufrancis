<?php /** @var ?string $error @var ?string $notice */ ?>
<form class="card form narrow" method="post" action="/admin/login">
  <p class="eyebrow">Step 1 of 2</p>
  <h1>Sign in</h1>
  <?php if (!empty($notice)) : ?><p class="notice-inline" role="status"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
  <div class="row"><label>Username <input name="user" required autocomplete="username" autocapitalize="none" spellcheck="false"></label></div>
  <div class="row"><label>Password <input type="password" name="password" required autocomplete="current-password"></label></div>
  <button class="btn btn-accent btn-block" type="submit">Continue</button>
  <p class="small sub-tight"><a href="/admin/forgot">Forgot password?</a></p>
</form>
