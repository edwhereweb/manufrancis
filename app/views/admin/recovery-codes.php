<?php /** @var list<string> $codes */ ?>
<div class="card setup">
  <p class="eyebrow">Two-step verification is on</p>
  <h1>Save your recovery codes</h1>
  <p class="muted">If you lose your phone, each code below lets you sign in <strong>once</strong>. They are shown only now. Store them in a password manager or print them.</p>
  <ul class="codes">
    <?php foreach ($codes as $c) : ?><li><code><?= e($c) ?></code></li><?php endforeach; ?>
  </ul>
  <a class="btn btn-accent" href="/admin/leads">I have saved them, continue</a>
</div>
