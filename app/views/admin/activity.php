<?php
/** @var string $cat @var bool $failures @var array $counts @var array $log */
// Link to this page with some filters changed. Empty values and page 1 are left out of the URL.
$url = function (array $change) use ($cat, $failures): string {
    $q = array_merge(['type' => $cat, 'failures' => $failures ? '1' : '', 'page' => ''], $change);
    $q = array_filter($q, fn ($v) => $v !== '' && $v !== 1);

    return '/admin/activity' . ($q ? '?' . http_build_query($q) : '');
};
$p = $log['page'];
$pages = $log['pages'];
?>
<div class="admin-head">
  <h1>Activity</h1>
  <span class="muted small">Kept for <?= AUDIT_RETENTION_DAYS ?> days</span>
</div>

<nav class="filters" aria-label="Filter by type">
  <a href="<?= e($url(['type' => '', 'page' => ''])) ?>"<?= $cat === '' ? ' aria-current="page"' : '' ?>>All <span><?= (int) $counts['all'] ?></span></a>
  <?php foreach (AUDIT_CATEGORIES as $k => $label) : ?>
    <a href="<?= e($url(['type' => $k, 'page' => ''])) ?>"<?= $cat === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
  <a class="filter-fail" href="<?= e($url(['failures' => $failures ? '' : '1', 'page' => ''])) ?>"<?= $failures ? ' aria-current="page"' : '' ?>>Failures only</a>
</nav>

<?php if (!$log['rows']) : ?>
  <p class="muted">Nothing to show.</p>
<?php else : ?>
  <div class="table-wrap">
    <table class="leads log">
      <thead><tr><th>When</th><th>Event</th><th>Admin</th><th>IP address</th><th>Device</th></tr></thead>
      <tbody>
        <?php foreach ($log['rows'] as $r) : [$label, $tone] = AUDIT_EVENTS[$r['event']] ?? [$r['event'], 'info']; ?>
          <tr>
            <td class="nowrap"><?= e(date('d M Y', strtotime($r['at']))) ?><br><span class="muted small"><?= e(date('H:i:s', strtotime($r['at']))) ?></span></td>
            <td><span class="dot-tone tone-<?= e($tone) ?>" aria-hidden="true"></span><?= e($label) ?>
              <?= $r['detail'] !== '' ? '<br><span class="muted small">' . e($r['detail']) . '</span>' : '' ?></td>
            <td><?= e($r['username'] ?: '-') ?></td>
            <td class="nowrap"><?= e($r['ip'] ?: '-') ?><?= $r['forwarded'] !== '' ? '<br><span class="muted small" title="X-Forwarded-For">via ' . e($r['forwarded']) . '</span>' : '' ?></td>
            <td class="small" title="<?= e($r['user_agent']) ?>"><?= e(ua_short($r['user_agent'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pager" aria-label="Pages">
    <span class="muted small"><?= (int) $log['total'] ?> entries &middot; page <?= $p ?> of <?= $pages ?></span>
    <span class="pager-links">
      <?php if ($p > 1) : ?><a class="btn btn-sm btn-ghost" href="<?= e($url(['page' => $p - 1])) ?>" rel="prev">&larr; Newer</a><?php endif; ?>
      <?php
      $show = array_unique(array_filter([1, $p - 2, $p - 1, $p, $p + 1, $p + 2, $pages], fn ($n) => $n >= 1 && $n <= $pages));
      sort($show);
      $prev = 0;
      foreach ($show as $n) :
          if ($n - $prev > 1) : ?><span class="muted">&hellip;</span><?php endif;
          if ($n === $p) : ?><span class="page-current" aria-current="page"><?= $n ?></span><?php
          else : ?><a class="page-link" href="<?= e($url(['page' => $n])) ?>"><?= $n ?></a><?php endif;
          $prev = $n;
      endforeach; ?>
      <?php if ($p < $pages) : ?><a class="btn btn-sm btn-ghost" href="<?= e($url(['page' => $p + 1])) ?>" rel="next">Older &rarr;</a><?php endif; ?>
    </span>
  </nav>
<?php endif; ?>
