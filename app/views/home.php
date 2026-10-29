<?php
/** @var bool $sent @var array $errors @var array $old */
$p = require APP_ROOT . '/content/profile.php';
$errors ??= [];
$old ??= [];
$sent ??= false;
$img = fn (string $path) => e('/assets/img/' . $path);
$err = fn (string $field) => isset($errors[$field]) ? '<p class="field-error" id="err-' . $field . '">' . e($errors[$field]) . '</p>' : '';
$aria = fn (string $field) => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="err-' . $field . '"' : '';
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow">Cybersecurity &middot; IT Infrastructure &middot; Software Engineering</p>
      <h1><?= e($p['name']) ?></h1>
      <p class="hero-role"><?= e($p['role']) ?>, Halosec Cyber Defense &amp; Edwhere Learning</p>
      <p class="lead"><?= e($p['tagline']) ?></p>
      <div class="hero-cta">
        <a class="btn btn-accent" href="#contact">Work with me</a>
        <a class="btn btn-ghost" href="#credentials">See credentials</a>
      </div>
      <ul class="cert-chips" aria-label="Certifications">
        <?php foreach ($p['certifications'] as $c) : ?>
          <li title="<?= e($c['title']) ?>"><?= e($c['code']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <figure class="hero-photo">
      <img src="<?= $img('photos/manu-francis-hero.webp') ?>" width="600" height="400" alt="Manu Francis in a server room" fetchpriority="high">
    </figure>
  </div>
  <div class="wrap">
    <dl class="stats">
      <?php foreach ($p['stats'] as $s) : ?>
        <div class="stat"><dt><?= e($s['label']) ?></dt><dd><?= e($s['value']) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<section class="section" id="about">
  <div class="wrap about-grid">
    <img class="headshot" src="<?= $img('photos/manu-francis-headshot.webp') ?>" width="500" height="500" alt="Portrait of Manu Francis" loading="lazy">
    <div>
      <p class="eyebrow">About</p>
      <h2>Hands-on, security-first, from architecture to implementation</h2>
      <?php foreach ($p['summary'] as $para) : ?>
        <p><?= e($para) ?></p>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="ventures">
  <div class="wrap">
    <p class="eyebrow">Ventures</p>
    <h2>Two companies, built and architected</h2>
    <div class="ventures">
      <?php foreach ($p['ventures'] as $v) : ?>
        <article class="card venture">
          <div class="logo-plate"><img src="<?= $img($v['logo']) ?>" alt="<?= e($v['name']) ?> logo" loading="lazy"></div>
          <h3><?= e($v['name']) ?></h3>
          <p class="muted"><?= e($v['role']) ?></p>
          <ul class="ticks">
            <?php foreach ($v['points'] as $pt) : ?><li><?= e($pt) ?></li><?php endforeach; ?>
          </ul>
          <div class="badges">
            <?php foreach ($v['badges'] as $b) : ?>
              <img src="<?= $img($b['img']) ?>" alt="<?= e($b['alt']) ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="work">
  <div class="wrap">
    <p class="eyebrow">Work</p>
    <h2>Key achievements</h2>
    <div class="work-grid">
      <?php foreach ($p['work'] as $w) : ?>
        <div class="card"><h3><?= e($w['title']) ?></h3><p><?= e($w['text']) ?></p></div>
      <?php endforeach; ?>
    </div>

    <h3 class="sub">Associated with 50+ organisations, including</h3>
    <ul class="logo-wall">
      <?php foreach ($p['associations'] as $a) : ?>
        <li class="<?= $a['img'] ? 'has-logo' : 'text-only' ?>">
          <?php if ($a['img']) : ?><img src="<?= $img($a['img']) ?>" alt="" loading="lazy"><?php endif; ?>
          <span><?= e($a['name']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="gallery">
      <?php foreach ($p['gallery'] as $g) : ?>
        <img src="<?= $img($g['img']) ?>" alt="<?= e($g['alt']) ?>" loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="credentials">
  <div class="wrap cred-grid">
    <div>
      <p class="eyebrow">Certifications</p>
      <h2>EC-Council certified</h2>
      <div class="logo-plate wide"><img src="<?= $img('logos/ec-council.webp') ?>" alt="EC-Council" loading="lazy"></div>
      <ul class="cert-list">
        <?php foreach ($p['certifications'] as $c) : ?>
          <li><strong><?= e($c['code']) ?></strong> <?= e($c['title']) ?> <span class="muted">(EC-Council)</span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <p class="eyebrow">Education</p>
      <h2>Academic credentials</h2>
      <ul class="edu-list">
        <?php foreach ($p['education'] as $ed) : ?>
          <li>
            <?php if ($ed['img']) : ?><img class="edu-logo" src="<?= $img($ed['img']) ?>" alt="" loading="lazy"><?php endif; ?>
            <strong><?= e($ed['title']) ?></strong>
            <?php if ($ed['where']) : ?><span><?= e($ed['where']) ?></span><?php endif; ?>
            <?php if ($ed['note']) : ?><em class="pill"><?= e($ed['note']) ?></em><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section contact" id="contact">
  <div class="wrap contact-grid">
    <div>
      <p class="eyebrow">Contact</p>
      <h2>Let's talk about your project</h2>
      <p>Security implementation, IT infrastructure, software architecture, training or speaking: send a short note and I will get back to you.</p>
      <p class="muted small">Your details are used only to reply to you, are never shared for marketing, and are deleted automatically after <?= (int) round(cfg('retention_days') / 30) ?> months. Read the <a href="/privacy">privacy policy</a>.</p>
    </div>

    <?php if ($sent) : ?>
      <div class="card notice" role="status">
        <h3>Thank you, your message is in.</h3>
        <p>I will reply to the email address you gave. If you want your details removed at any time, just ask.</p>
      </div>
    <?php else : ?>
      <form class="card form" method="post" action="/#contact" novalidate>
        <?php if (isset($errors['form'])) : ?><p class="form-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
        <?php if ($errors && !isset($errors['form'])) : ?><p class="form-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
        <input type="hidden" name="token" value="<?= e(form_token()) ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="row">
          <label>Name <span aria-hidden="true">*</span>
            <input name="name" required maxlength="120" autocomplete="name" value="<?= e($old['name'] ?? '') ?>"<?= $aria('name') ?>>
          </label><?= $err('name') ?>
        </div>
        <div class="row two">
          <div>
            <label>Email <span aria-hidden="true">*</span>
              <input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>"<?= $aria('email') ?>>
            </label><?= $err('email') ?>
          </div>
          <div>
            <label>Phone <span class="muted">(optional)</span>
              <input type="tel" name="phone" maxlength="30" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"<?= $aria('phone') ?>>
            </label><?= $err('phone') ?>
          </div>
        </div>
        <div class="row two">
          <div>
            <label>Organisation <span class="muted">(optional)</span>
              <input name="organisation" maxlength="160" autocomplete="organization" value="<?= e($old['organisation'] ?? '') ?>">
            </label>
          </div>
          <div>
            <label>Topic <span aria-hidden="true">*</span>
              <select name="interest" required<?= $aria('interest') ?>>
                <option value="">Choose one</option>
                <?php foreach (LEAD_INTERESTS as $k => $label) : ?>
                  <option value="<?= e($k) ?>"<?= ($old['interest'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label><?= $err('interest') ?>
          </div>
        </div>
        <div class="row">
          <label>Message <span aria-hidden="true">*</span>
            <textarea name="message" rows="5" required maxlength="4000"<?= $aria('message') ?>><?= e($old['message'] ?? '') ?></textarea>
          </label><?= $err('message') ?>
        </div>
        <div class="row">
          <label class="check">
            <input type="checkbox" name="consent" value="yes"<?= ($old['consent'] ?? '') === 'yes' ? ' checked' : '' ?><?= $aria('consent') ?>>
            <span>I agree that Manu Francis may store and use these details to reply to my enquiry, as described in the <a href="/privacy">privacy policy</a>. I can withdraw this at any time.</span>
          </label><?= $err('consent') ?>
        </div>
        <button class="btn btn-accent" type="submit">Send message</button>
      </form>
    <?php endif; ?>
  </div>
</section>
