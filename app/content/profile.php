<?php

declare(strict_types=1);

/**
 * All profile facts in one place. Edit text here, not in the templates.
 * Source: Manu's own profile brief. Do not add claims that are not in it.
 */
return [
    'name'    => 'Manu Francis',
    'role'    => 'Founder & Technical Architect',
    'tagline' => 'Cybersecurity, IT infrastructure and software engineering. Not only managing projects, but building, implementing and optimising them.',
    'summary' => [
        'Over 10 years of experience managing and hands-on delivering projects across Cybersecurity, IT Infrastructure and Software Engineering, from technical implementation and architecture to system optimisation.',
        'My focus is cybersecurity research, security-conscious project development and end-to-end security implementation. I founded Halosec Cyber Defense LLP and Phoger Edwhere Learning LLP, and I am the technical architect of both.',
    ],

    'stats' => [
        ['value' => '10+',  'label' => 'Years in cybersecurity, IT infrastructure & software engineering'],
        ['value' => '40K+', 'label' => 'Students trained, government and non-government'],
        ['value' => '100+', 'label' => 'Companies & organisations secured'],
        ['value' => '50+',  'label' => 'Codebases managed and contributed to'],
    ],

    'ventures' => [
        [
            'name'   => 'Halosec Cyber Defense LLP',
            'role'   => 'Founder & Technical Architect',
            'logo'   => 'logos/halosec.webp',
            'points' => [
                'Built the company from scratch.',
                "Technical architect for the company's solutions, implementations and system processes.",
                'Authorised Partner for Sophos in India.',
                'Official Zoho Partner.',
                'Provides complete cybersecurity services and IT services.',
            ],
            'badges' => [
                ['img' => 'logos/sophos-authorized-partner.webp', 'alt' => 'Sophos Authorised Partner'],
                ['img' => 'logos/sophos-endpoint-xdr-partner.webp', 'alt' => 'Sophos Endpoint & XDR Partner'],
                ['img' => 'logos/zoho-partner.webp', 'alt' => 'Zoho Partner'],
                ['img' => 'logos/hostinger-partner.webp', 'alt' => 'Hostinger Partner'],
            ],
        ],
        [
            'name'   => 'Phoger Edwhere Learning LLP',
            'role'   => 'Founder & Technical Architect',
            'logo'   => 'logos/edwhere.webp',
            'points' => [
                'Technical architect for learning systems, architectures and technical workflows.',
                'Leads overall academic operations and directs training programs.',
                'Accredited Training Centre (ATC) of the International Council of E-Commerce Consultants (EC-Council).',
                'Secured ISO 9001 accreditation.',
                "Developed training programs and full workflows built on the U.S. Government's National Institute of Standards and Technology (NIST) standards.",
            ],
            'badges' => [
                ['img' => 'logos/ec-council-atc.webp', 'alt' => 'EC-Council Accredited Training Center'],
                ['img' => 'logos/iso-9001.webp', 'alt' => 'ISO 9001:2015'],
                ['img' => 'logos/nist.webp', 'alt' => 'NIST'],
            ],
        ],
    ],

    'work' => [
        ['title' => 'Security',              'illo' => 'shield',  'tone' => 'blue',   'text' => 'Secured 100+ companies and organisations, with cybersecurity research and end-to-end security implementation.'],
        ['title' => 'IT infrastructure',     'illo' => 'network', 'tone' => 'mint',   'text' => 'Designed, built and actively maintains IT network infrastructure for 100+ organisations.'],
        ['title' => 'Software engineering',  'illo' => 'code',    'tone' => 'violet', 'text' => 'Manages and contributes to 50+ codebases alongside the engineering team, leading technical architecture and implementation.'],
        ['title' => 'Training & mentorship', 'illo' => 'teach',   'tone' => 'amber',  'text' => 'Trained 40,000+ students, for government and non-government clients.'],
    ],

    'associations' => [
        ['name' => 'Kerala Police Cyberdome', 'img' => 'logos/kerala-police.webp'],
        ['name' => 'Labour India (Santhosh George Kulangara)', 'img' => 'logos/labour-india.webp'],
        ['name' => 'All India Council for Technical Education (AICTE)', 'img' => 'logos/aicte.webp'],
        ['name' => 'AICTE Training and Learning (ATAL) Academy', 'img' => null],
        ['name' => 'Engineering colleges across India', 'img' => null],
        ['name' => 'Partners and enterprises across the UAE and globally', 'img' => null],
    ],

    'education' => [
        ['title' => 'M.Tech in Computer Science and Engineering (Executive)', 'where' => 'Cochin University of Science and Technology (CUSAT)', 'note' => 'Currently pursuing', 'img' => 'logos/cusat.webp'],
        ['title' => 'B.Tech in Computer Science and Engineering', 'where' => '', 'note' => '', 'img' => null],
        ['title' => 'Nanodegree in Cybersecurity', 'where' => '', 'note' => '', 'img' => null],
        ['title' => '200+ MOOCs and online courses', 'where' => 'Cybersecurity and allied domains', 'note' => '', 'img' => null],
    ],

    'certifications' => [
        ['code' => 'CEH', 'title' => 'Certified Ethical Hacker'],
        ['code' => 'CEI', 'title' => 'Certified EC-Council Instructor'],
        ['code' => 'CSA', 'title' => 'Certified SOC Analyst'],
    ],

    'award' => [
        'title'   => 'Technopreneur Award',
        'img'     => 'photos/technopreneur-award.webp',
        'alt'     => 'Manu Francis receiving the Technopreneur Award from Group Captain Prasanth Balakrishnan Nair and Shri C. Balagopal',
        'text'    => 'Manu Francis receiving the Technopreneur Award from Group Captain Prasanth Balakrishnan Nair and Shri C. Balagopal.',
    ],

    // Training photos from Edwhere sessions. 'size' controls the tile: wide, tall or normal.
    'gallery' => [
        ['img' => 'photos/workshop-asiet-kalady.webp',      'size' => 'wide',   'caption' => 'Cybersecurity workshop, ASIET Kalady', 'alt' => 'Manu Francis presenting a cybersecurity workshop at ASIET Kalady'],
        ['img' => 'photos/fdp-sjcet-memento.webp',          'size' => 'normal', 'caption' => 'IoT Security FDP, SJCET Palai', 'alt' => 'Manu Francis presented with a memento after the ATAL AICTE IoT Security faculty development program at SJCET Palai'],
        ['img' => 'photos/iot-security-cusat.webp',         'size' => 'normal', 'caption' => 'IoT Security session, CUSAT (ATAL AICTE)', 'alt' => 'IoT Security session by Manu Francis at CUSAT, sponsored by ATAL AICTE'],
        ['img' => 'photos/fdp-atal-aicte-sjcet.webp',       'size' => 'wide',   'caption' => 'Faculty development program, ATAL AICTE', 'alt' => 'Faculty development program on IoT Security sponsored by ATAL AICTE at SJCET Palai'],
        ['img' => 'photos/training-toc-h-iste.webp',        'size' => 'normal', 'caption' => 'Hands-on training for ISTE, Toc H', 'alt' => 'Manu Francis conducting hands-on cybersecurity training for ISTE at Toc H Institute of Science and Technology'],
        ['img' => 'photos/session-mace-kothamangalam.webp', 'size' => 'normal', 'caption' => 'Session at MACE Kothamangalam', 'alt' => 'Manu Francis conducting a session at Mar Athanasius College of Engineering, Kothamangalam'],
        ['img' => 'photos/lab-session-asiet.webp',          'size' => 'normal', 'caption' => 'Lab session, ASIET', 'alt' => 'Manu Francis guiding students during a cybersecurity lab session at ASIET'],
        ['img' => 'photos/plp101-session.webp',             'size' => 'wide',   'caption' => 'Students at a PLP101 session', 'alt' => 'Students at the PLP101 session by Manu Francis'],
        ['img' => 'photos/ethical-hacking-graduates.webp',  'size' => 'normal', 'caption' => 'Ethical hacking graduates', 'alt' => 'Graduates of the Essentials of Ethical Hacking course with Manu Francis'],
    ],
];
