<?php

$EM_CONF['ns_cloudflare'] = [
    'title' => 'TYPO3 Cloudflare Cache Manager',
    'description' => 'A TYPO3 extension to simplify cache management via Cloudflare. Seamlessly integrates with Cloudflare to enhance site performance and security for developers and administrators.',
    'category' => 'services',
    'author' => 'Team NITSAN',
    'author_email' => 'info@nitsantech.de',
    'author_company' => 'NITSAN',
    'state' => 'stable',
    'version' => '1.0.4',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['NITSAN\\NsCloudflare\\' => 'Classes']
    ],
];
