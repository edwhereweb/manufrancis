<?php
/** @var bool $sent @var array $errors @var array $old */
require_once APP_ROOT . '/views/partials/illustrations.php';
require_once APP_ROOT . '/views/partials/social.php';
$p = require APP_ROOT . '/content/profile.php';
$errors ??= [];
$old ??= [];
$sent ??= false;
$img = fn (string $path) => e('/assets/img/' . $path);
$err = fn (string $field) => isset($errors[$field]) ? '<p class="field-error" id="err-' . $field . '">' . e($errors[$field]) . '</p>' : '';
$aria = fn (string $field) => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="err-' . $field . '"' : '';
?>
<!-- Hero -->
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy reveal">
      <p class="kicker"><span class="dot" aria-hidden="true"></span> Founder &amp; Technical Architect</p>
      <h1><?= e($p['name']) ?></h1>
      <p class="hero-line">Builds secure systems.<br><span class="grad">Trains the people who run them.</span></p>
      <p class="lead"><?= e($p['tagline']) ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="#contact">Work with me</a>
        <a class="link-arrow" href="#ventures">See the work <span aria-hidden="true">&rarr;</span></a>
      </div>
    </div>

    <div class="hero-stage reveal d1">
      <?= illo('orbit') ?>
      <figure class="hero-photo">
        <img src="<?= $img('photos/manu-francis-hero.webp') ?>" width="600" height="400" alt="Manu Francis" fetchpriority="high">
      </figure>
      <div class="float-card fc-1"><img src="<?= $img('logos/ec-council.webp') ?>" alt="" width="84" height="19"><span>CEH &middot; CEI &middot; CSA</span></div>
      <div class="float-card fc-2"><span class="ok-dot" aria-hidden="true"></span><span><strong>100+</strong> organisations secured</span></div>    </div>
  </div>

  <div class="wrap">
    <dl class="stats reveal">
      <?php foreach ($p['stats'] as $s) : ?>
        <?php preg_match('/^(\d+)(.*)$/', $s['value'], $m); ?>
        <div class="stat"><dt><?= e($s['label']) ?></dt><dd data-count="<?= (int) $m[1] ?>" data-suffix="<?= e($m[2]) ?>"><?= e($s['value']) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<!-- Recognition -->
<section class="section award" id="recognition">
  <div class="wrap award-grid">
    <figure class="award-photo reveal">
      <img src="<?= $img($p['award']['img']) ?>" width="1012" height="728" alt="<?= e($p['award']['alt']) ?>" loading="lazy">
    </figure>
    <div class="award-copy reveal d1">
      <?= illo('laurel') ?>
      <p class="eyebrow">Recognition</p>
      <h2><?= e($p['award']['title']) ?></h2>
      <p class="lead-dark"><?= e($p['award']['text']) ?></p>
    </div>
  </div>
</section>

<!-- About -->
<section class="section" id="about">
  <div class="wrap about-grid">
    <div class="about-photo reveal">
      <img src="<?= $img('photos/manu-francis-headshot.webp') ?>" width="500" height="500" alt="Portrait of Manu Francis" loading="lazy">
    </div>
    <div class="reveal d1">
      <p class="eyebrow">About</p>
      <h2>Hands-on, security-first. From architecture to implementation.</h2>
      <?php foreach ($p['summary'] as $para) : ?>
        <p class="body-lg"><?= e($para) ?></p>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- What I do -->
<section class="section section-alt" id="work">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">What I do</p>
      <h2>Four disciplines. One security mindset.</h2>
    </div>
    <div class="bento">
      <?php foreach ($p['work'] as $i => $w) : ?>
        <article class="tile tone-<?= e($w['tone']) ?> reveal d<?= $i % 2 ?>">
          <div class="tile-art"><?= illo($w['illo']) ?></div>
          <h3><?= e($w['title']) ?></h3>
          <p><?= e($w['text']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Ventures -->
<section class="section" id="ventures">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">Ventures</p>
      <h2>Two companies, built from the ground up.</h2>
    </div>
    <div class="ventures">
      <?php foreach ($p['ventures'] as $i => $v) : ?>
        <article class="venture reveal d<?= $i ?>">
          <div class="venture-logo"><img src="<?= $img($v['logo']) ?>"<?= img_size($v['logo']) ?> alt="<?= e($v['name']) ?> logo" loading="lazy"></div>
          <h3><?= e($v['name']) ?></h3>
          <p class="muted"><?= e($v['role']) ?></p>
          <ul class="ticks">
            <?php foreach ($v['points'] as $pt) : ?><li><?= e($pt) ?></li><?php endforeach; ?>
          </ul>
          <div class="badges">
            <?php foreach ($v['badges'] as $b) : ?>
              <img src="<?= $img($b['img']) ?>"<?= img_size($b['img']) ?> alt="<?= e($b['alt']) ?>" title="<?= e($b['alt']) ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- In the classroom -->
<section class="section section-dark" id="training">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">In the classroom</p>
      <h2>40,000+ students trained.<br> <span class="muted-dark">For government and non-government clients.</span></h2>
    </div>
    <div class="gallery">
      <?php foreach ($p['gallery'] as $i => $g) : ?>
        <figure class="shot shot-<?= e($g['size']) ?> reveal d<?= $i % 3 ?>">
          <img src="<?= $img($g['img']) ?>"<?= img_size($g['img']) ?> alt="<?= e($g['alt']) ?>" loading="lazy">
          <figcaption><?= e($g['caption']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Associations -->
<section class="section" id="associations">
  <div class="wrap">
    <div class="section-head center reveal">
      <p class="eyebrow">Associations</p>
      <h2>Worked with 50+ organisations.</h2>
    </div>
    <ul class="logos reveal">
      <?php foreach ($p['associations'] as $a) : ?>
        <li class="<?= $a['img'] ? '' : 'text-only' ?>">
          <?php if ($a['img']) : ?><img src="<?= $img($a['img']) ?>"<?= img_size($a['img']) ?> alt="" loading="lazy"><?php endif; ?>
          <span><?= e($a['name']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- Credentials -->
<section class="section section-alt" id="credentials">
  <div class="wrap cred-grid">
    <div class="cred-card reveal">
      <p class="eyebrow">Certifications</p>
      <h2>EC-Council certified.</h2>
      <img class="cred-logo" src="<?= $img('logos/ec-council.webp') ?>"<?= img_size('logos/ec-council.webp') ?> alt="EC-Council" loading="lazy">
      <ul class="cred-list">
        <?php foreach ($p['certifications'] as $c) : ?>
          <li><span class="code"><?= e($c['code']) ?></span><span><?= e($c['title']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="cred-card reveal d1">
      <p class="eyebrow">Education</p>
      <h2>Always learning.</h2>
      <ul class="cred-list edu">
        <?php foreach ($p['education'] as $ed) : ?>
          <li>
            <span>
              <?php if ($ed['img']) : ?><img class="edu-logo" src="<?= $img($ed['img']) ?>"<?= img_size($ed['img']) ?> alt="" loading="lazy"><?php endif; ?>
              <strong><?= e($ed['title']) ?></strong>
              <?php if ($ed['where']) : ?><small><?= e($ed['where']) ?></small><?php endif; ?>
            </span>
            <?php if ($ed['note']) : ?><em class="pill"><?= e($ed['note']) ?></em><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<!-- Contact -->
<section class="section contact" id="contact">
  <div class="wrap contact-grid">
    <div class="reveal">
      <p class="eyebrow">Contact</p>
      <h2>Let's build something secure.</h2>
      <p class="body-lg">Security implementation, IT infrastructure, software architecture, training or speaking. Send a short note and I will get back to you.</p>
      <p class="social-label">Or find me on</p>
      <?= social_links($p['social'], 'social-lg') ?>
      <p class="muted small">Your details are used only to reply to you, are never shared for marketing, and are deleted automatically after <?= (int) round(cfg('retention_days') / 30) ?> months. Read the <a href="/privacy">privacy policy</a>.</p>
    </div>

    <?php if ($sent) : ?>
      <div class="form-card notice reveal d1" role="status">
        <span class="ok-big" aria-hidden="true">&#10003;</span>
        <h3>Thank you. Your message is in.</h3>
        <p>I will reply to the email address you gave. If you want your details removed at any time, just ask.</p>
      </div>
    <?php else : ?>
      <form class="form-card form reveal d1" method="post" action="/#contact" novalidate>
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
            <label>Phone <span class="opt">optional</span>
              <input type="tel" name="phone" maxlength="30" autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"<?= $aria('phone') ?>>
            </label><?= $err('phone') ?>
          </div>
        </div>
        <div class="row two">
          <div>
            <label>Organisation <span class="opt">optional</span>
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
        <button class="btn btn-primary btn-block" type="submit">Send message</button>
      </form>
    <?php endif; ?>
  </div>
</section>
