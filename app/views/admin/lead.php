<?php /** @var array $lead @var bool $saved */ ?>
<p><a href="/admin/leads">&larr; All leads</a></p>
<div class="admin-head">
  <h1><?= e($lead['name']) ?></h1>
  <span class="status status-<?= e($lead['status']) ?>"><?= e(LEAD_STATUSES[$lead['status']] ?? $lead['status']) ?></span>
</div>
<?php if ($saved) : ?><p class="notice-inline" role="status">Saved.</p><?php endif; ?>

<div class="lead-grid">
  <div class="card">
    <dl class="details">
      <dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd>
      <dt>Phone</dt><dd><?= $lead['phone'] !== '' ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $lead['phone'])) . '">' . e($lead['phone']) . '</a>' : '<span class="muted">Not given</span>' ?></dd>
      <dt>Organisation</dt><dd><?= e($lead['organisation'] ?: '-') ?></dd>
      <dt>Topic</dt><dd><?= e(LEAD_INTERESTS[$lead['interest']] ?? $lead['interest']) ?></dd>
      <dt>Received</dt><dd><?= e(date('d M Y, H:i', strtotime($lead['created_at']))) ?></dd>
      <dt>Consent</dt><dd class="small"><?= e(date('d M Y, H:i', strtotime($lead['consent_at']))) ?>: &ldquo;<?= e($lead['consent_text']) ?>&rdquo;</dd>
    </dl>
    <h2 class="sub">Message</h2>
    <p class="message"><?= nl2br(e($lead['message'])) ?></p>
  </div>

  <div>
    <form class="card form" method="post">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <div class="row"><label>Status
        <select name="status">
          <?php foreach (LEAD_STATUSES as $k => $label) : ?>
            <option value="<?= e($k) ?>"<?= $lead['status'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select></label></div>
      <div class="row"><label>Private notes <textarea name="notes" rows="6"><?= e($lead['notes']) ?></textarea></label></div>
      <button class="btn btn-accent" type="submit">Save</button>
    </form>

    <form class="card form danger" method="post" data-confirm="Permanently delete this lead? This cannot be undone.">
      <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
      <input type="hidden" name="action" value="delete">
      <p class="small">Delete when the person asks to be erased (GDPR Art. 17) or the lead is spam.</p>
      <button class="btn btn-danger" type="submit">Delete permanently</button>
    </form>
  </div>
</div>
