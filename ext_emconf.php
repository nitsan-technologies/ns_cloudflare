<?php

$EM_CONF['ns_cloudflare'] = [
    'title' => '[NITSAN] Cloudflare',
    'description' => 'The Cloudflare TYPO3 extension ensures your TYPO3 website is running optimally on the Cloudflare platform.',
    'category' => 'services',
    'author' => 'T3: Nilesh Malankiya, T3: Rohan Parmar',
    'author_company' => 'NITSAN Technologies',
    'author_email' => 'sanjay@nitsan.in',
    'state' => 'stable',
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['NITSAN\\NsCloudflare\\' => 'Classes']
    ],
];
