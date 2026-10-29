<?php /** @var ?string $error */ ?>
<form class="card form narrow" method="post" action="/admin/login">
  <h1>Sign in</h1>
  <?php if ($error) : ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
  <div class="row"><label>Username <input name="user" required autocomplete="username"></label></div>
  <div class="row"><label>Password <input type="password" name="password" required autocomplete="current-password"></label></div>
  <button class="btn btn-accent" type="submit">Sign in</button>
</form>
