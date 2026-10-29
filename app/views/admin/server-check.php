<?php /** @var list<string> $missing */ ?>
<div class="card setup">
  <p class="eyebrow">Server setup needed</p>
  <h1>The admin can't run on this server yet</h1>
  <p class="muted">These are missing:</p>
  <ul class="codes">
    <?php foreach ($missing as $m) : ?><li><code><?= e($m) ?></code></li><?php endforeach; ?>
  </ul>
  <p class="muted small">Hostinger: hPanel &rarr; Advanced &rarr; PHP Configuration &rarr; <strong>PHP extensions</strong>, tick the missing ones and save. Then reload this page. The public site is not affected.</p>
</div>
