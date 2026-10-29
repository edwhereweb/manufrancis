<?php /** @var array $leads @var array $counts @var string $status @var string $q */ ?>
<div class="admin-head">
  <h1>Leads</h1>
  <a class="btn btn-sm btn-ghost" href="/admin/leads/export.csv">Export CSV</a>
</div>
<?php if (isset($_GET['deleted'])) : ?><p class="notice-inline" role="status">Lead deleted permanently.</p><?php endif; ?>

<nav class="filters" aria-label="Filter by status">
  <a href="/admin/leads"<?= $status === '' ? ' aria-current="page"' : '' ?>>All <span><?= array_sum($counts) ?></span></a>
  <?php foreach (LEAD_STATUSES as $k => $label) : ?>
    <a href="/admin/leads?status=<?= e($k) ?>"<?= $status === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span><?= (int) $counts[$k] ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="search" method="get" action="/admin/leads">
  <?php if ($status) : ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email, organisation, message" aria-label="Search leads">
  <button class="btn btn-sm btn-accent" type="submit">Search</button>
</form>

<?php if (!$leads) : ?>
  <p class="muted">No leads yet.</p>
<?php else : ?>
  <div class="table-wrap">
    <table class="leads">
      <thead><tr><th>Received</th><th>Name</th><th>Topic</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($leads as $l) : ?>
          <tr>
            <td><?= e(date('d M Y, H:i', strtotime($l['created_at']))) ?></td>
            <td><a href="/admin/leads/<?= (int) $l['id'] ?>"><?= e($l['name']) ?></a><br><span class="muted small"><?= e($l['email']) ?><?= $l['organisation'] ? ' &middot; ' . e($l['organisation']) : '' ?></span></td>
            <td><?= e(LEAD_INTERESTS[$l['interest']] ?? $l['interest']) ?></td>
            <td><span class="status status-<?= e($l['status']) ?>"><?= e(LEAD_STATUSES[$l['status']] ?? $l['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<p class="muted small">Leads older than <?= (int) cfg('retention_days') ?> days are deleted automatically (GDPR storage limitation).</p>
