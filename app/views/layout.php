<?php
/** @var string $title @var string $content */
$siteUrl = rtrim((string) cfg('site_url'), '/');
$description = 'Manu Francis: Founder & Technical Architect of Halosec Cyber Defense LLP and Phoger Edwhere Learning LLP. 10+ years in cybersecurity, IT infrastructure and software engineering. CEH, CEI, CSA.';
$canonical = $siteUrl . (request_path() === '/' ? '/' : request_path());
require_once APP_ROOT . '/views/partials/social.php';
$social = (require APP_ROOT . '/content/profile.php')['social'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="profile">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($siteUrl) ?>/assets/img/photos/manu-francis-hero.webp">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="preload" href="/assets/fonts/inter-var-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
  <script type="application/ld+json"><?= json_encode([
      '@context'  => 'https://schema.org',
      '@type'     => 'Person',
      'name'      => 'Manu Francis',
      'url'       => $siteUrl . '/',
      'image'     => $siteUrl . '/assets/img/photos/manu-francis-headshot.webp',
      'jobTitle'  => 'Founder & Technical Architect',
      'worksFor'  => [
          ['@type' => 'Organization', 'name' => 'Halosec Cyber Defense LLP'],
          ['@type' => 'Organization', 'name' => 'Phoger Edwhere Learning LLP'],
      ],
      'alumniOf'  => ['@type' => 'CollegeOrUniversity', 'name' => 'Cochin University of Science and Technology'],
      'knowsAbout' => ['Cybersecurity', 'IT Infrastructure', 'Software Engineering', 'Security Operations', 'Ethical Hacking'],
      'sameAs'    => array_column($social, 'url'),
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="nav" id="top">
    <div class="wrap nav-inner">
      <a class="brand" href="/" aria-label="Manu Francis, home"><span class="brand-mark" aria-hidden="true">MF</span> Manu Francis</a>
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
