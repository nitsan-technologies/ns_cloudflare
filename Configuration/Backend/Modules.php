<?php

$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
->get('ns_cloudflare');

if (filter_var($config['enableAnalyticsModule'] ?? false, FILTER_VALIDATE_BOOL)) {
    return [
        'nitsan_module' => [
            'labels' => 'LLL:EXT:ns_cloudflare/Resources/Private/Language/locallang_mod_analytics.xlf:nitsan',
            'icon' => 'EXT:ns_cloudflare/Resources/Public/Icons/module-nscloudflare.svg',
            'iconIdentifier' => 'module-nscloudflare',
            'position' => ['after' => 'web'],
        ],
        'txcloudflare' => [
            'parent' => 'nitsan_module',
            'position' => ['before' => 'top'],
            'path' => '/module/nitsan/NsCloud/',
            'access' => 'user,group',
            'icon' => 'EXT:ns_cloudflare/Resources/Public/Icons/module-analytics.png',
            'labels' => 'LLL:EXT:ns_cloudflare/Resources/Private/Language/locallang_mod_analytics.xlf',
            'extensionName' => 'NsCloudflare',
            'controllerActions' => [
                \NITSAN\NsCloudflare\Controller\DashboardController::class => [
                    'analytics',
                    'ajaxAnalytics'
                ],
            ],
        ],

    ];
}
