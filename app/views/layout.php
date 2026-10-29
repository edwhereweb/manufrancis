<?php
/**
 * Public page layout.
 * @var string $title        page title (50-60 chars on the home page)
 * @var string $content      rendered page body
 * @var string $description  optional per-page meta description (keep under 160 chars)
 * @var bool   $noindex      optional; true for pages search engines should skip (404)
 */
require_once APP_ROOT . '/views/partials/social.php';
require_once APP_ROOT . '/views/partials/schema.php';
$profile = require APP_ROOT . '/content/profile.php';
$social = $profile['social'];
$siteUrl = rtrim((string) cfg('site_url'), '/');
$description ??= $profile['seo']['description'];
$noindex ??= false;
$isHome = request_path() === '/';
$canonical = $siteUrl . ($isHome ? '/' : request_path());
$shareImage = $siteUrl . '/assets/img/share/manu-francis-share.jpg';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
<?php if ($noindex) : ?>
  <meta name="robots" content="noindex">
<?php else : ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
  <meta property="og:site_name" content="Manu Francis">
  <meta property="og:locale" content="en_IN">
  <meta property="og:type" content="<?= $isHome ? 'profile' : 'website' ?>">
<?php if ($isHome) : ?>
  <meta property="profile:first_name" content="Manu">
  <meta property="profile:last_name" content="Francis">
  <meta property="profile:username" content="phogerman">
<?php endif; ?>
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($shareImage) ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Manu Francis, Founder &amp; Technical Architect">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@phogerman">
  <meta name="twitter:creator" content="@phogerman">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($description) ?>">
  <meta name="twitter:image" content="<?= e($shareImage) ?>">
  <meta name="twitter:image:alt" content="Manu Francis, Founder &amp; Technical Architect">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preload" href="/assets/fonts/inter-var-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if ($isHome) : ?>
  <script type="application/ld+json"><?= json_encode(profile_schema($profile, $siteUrl), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="nav" id="top">
    <div class="wrap nav-inner">
      <a class="brand" href="/" aria-label="Manu Francis, home"><img class="brand-logo" src="/assets/img/brand/lighthouse-mark.svg" width="26" height="34" alt=""> Manu Francis</a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links">Menu</button>
      <nav id="nav-links" class="nav-links" aria-label="Main">
        <a href="/#about">About</a>
        <a href="/#work">Expertise</a>
        <a href="/#ventures">Ventures</a>
        <a href="/#training">Training</a>
        <a href="/#credentials">Credentials</a>
        <a class="btn btn-sm btn-accent" href="/#contact">Get in touch</a>
      </nav>
    </div>
  </header>

  <main id="main">
    <?= $content ?>
  </main>

  <footer class="footer">
    <div class="wrap footer-inner">
      <p>&copy; <?= date('Y') ?> Manu Francis. All rights reserved.</p>
      <?= social_links($social) ?>
      <p><a href="/privacy">Privacy policy</a> <span aria-hidden="true">&middot;</span> No tracking, no ads, no cookies.</p>
    </div>
  </footer>
</body>
</html>
