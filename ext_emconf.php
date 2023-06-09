<?php

$EM_CONF['ns_cloudflare'] = [
    'title' => '[NITSAN] Cloudflare',
    'category' => 'services',
    'author' => 'Team NITSAN',
    'author_company' => 'NITSAN Technologies',
    'author_email' => 'sanjay@nitsan.in',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['NITSAN\\NsCloudflare\\' => 'Classes']
    ],
];
