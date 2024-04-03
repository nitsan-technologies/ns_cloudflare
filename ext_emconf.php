<?php

$EM_CONF['ns_cloudflare'] = [
    'title' => 'Cloudflare',
    'description' => 'The TYPO3 Cloudflare Extension simplifies cache management, offering an efficient solution for TYPO3 administrators and developers. Seamlessly integrating with Cloudflare, a global leader in web performance and security, this extension provides advanced features to optimize, secure, and structure your online presence.Compatible with TYPO3 version 12. 
    
    Documentation & Free Support: https://t3planet.com/typo3-cloudflare-extension',
    'category' => 'services',
    'author' => 'T3: Nilesh Malankiya, T3: Rohan Parmar QA: Krishna Dhapa',
    'author_company' => 'T3Planet // NITSAN',
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
