<?php

declare(strict_types=1);

/**
 * schema.org ProfilePage + Person for the home page, built only from facts in profile.php.
 * Check changes with https://search.google.com/test/rich-results
 */
function profile_schema(array $p, string $siteUrl): array
{
    $eccouncil = ['@type' => 'Organization', 'name' => 'EC-Council', 'url' => 'https://www.eccouncil.org'];

    $website = [
        '@type'         => 'WebSite',
        '@id'           => $siteUrl . '/#website',
        'url'           => $siteUrl . '/',
        'name'          => $p['name'],               // the site name Google shows in results
        'alternateName' => 'manufrancis.com',
        'inLanguage'    => 'en',
        'publisher'     => ['@id' => $siteUrl . '/#person'],
    ];

    return ['@context' => 'https://schema.org', '@graph' => [$website, [
        '@type'        => 'ProfilePage',
        '@id'          => $siteUrl . '/#profile',
        'isPartOf'     => ['@id' => $siteUrl . '/#website'],
        'url'          => $siteUrl . '/',
        'name'         => $p['seo']['title'],
        'description'  => $p['seo']['description'],
        'inLanguage'   => 'en',
        'dateModified' => date('Y-m-d', (int) filemtime(APP_ROOT . '/content/profile.php')),
        'mainEntity'   => [
            '@type'         => 'Person',
            '@id'           => $siteUrl . '/#person',
            'name'          => $p['name'],
            'alternateName' => 'phogerman',
            'url'           => $siteUrl . '/',
            'image'         => $siteUrl . '/assets/img/photos/manu-francis-headshot.webp',
            'description'   => $p['seo']['bio'],
            'givenName'     => 'Manu',
            'familyName'    => 'Francis',
            'jobTitle'      => $p['role'],
            'worksFor'      => array_map(fn ($v) => ['@type' => 'Organization', 'name' => $v['name'], 'url' => $v['url']], $p['ventures']),
            'award'         => $p['award']['title'],
            'hasCredential' => array_map(fn ($c) => [
                '@type'              => 'EducationalOccupationalCredential',
                'name'               => $c['title'] . ' (' . $c['code'] . ')',
                'credentialCategory' => 'certification',
                'recognizedBy'       => $eccouncil,
            ], $p['certifications']),
            'knowsAbout'    => ['Cybersecurity', 'IT Infrastructure', 'Software Engineering', 'Security Operations', 'Ethical Hacking', 'Cybersecurity Training'],
            'sameAs'        => array_column($p['social'], 'url'),
        ],
    ]]];
}
