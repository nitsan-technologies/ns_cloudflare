<?php

$EM_CONF['ns_cloudflare'] = [
    'title' => 'TYPO3 Cloudflare Cache Manager',
    'description' => 'A TYPO3 extension to simplify cache management via Cloudflare. Seamlessly integrates with Cloudflare to enhance site performance and security for developers and administrators.',
    'category' => 'services',
    'author' => 'Team T3Planet',
    'author_company' => 'T3Planet',
    'author_email' => 'info@t3planet.de',
    'state' => 'stable',
    'version' => '1.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['NITSAN\\NsCloudflare\\' => 'Classes']
    ],
];
