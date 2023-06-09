<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') || die();

$_EXTKEY = 'ns_cloudflare';

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/toolbar.css';

$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
->get($_EXTKEY);

$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

if ($versionInformation->getMajorVersion() == 11) {
    if (filter_var($config['enableAnalyticsModule'] ?? false, FILTER_VALIDATE_BOOL)) {
        // Create a module section "Cloudflare" before 'Admin Tools'
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('txcloudflare', '', '', '', [
            'access' => 'user,group',
            'name' => 'nitsan_module',
            'iconIdentifier' => 'module-nscloudflare',
            'labels' => 'LLL:EXT:ns_cloudflare/Resources/Private/Language/locallang_mod_analytics.xlf:nitsan',
            'icon' => 'EXT:ns_cloudflare/Resources/Public/Icons/module-nscloudflare.svg',
        ]);
        $temp_TBE_MODULES = [];
        foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
            if ($key === 'tools') {
                $temp_TBE_MODULES['txcloudflare'] = '';
                $temp_TBE_MODULES[$key] = $val;
            } else {
                $temp_TBE_MODULES[$key] = $val;
            }
        }
        $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $_EXTKEY,
            'txcloudflare',
            'analytics',
            '',
            [\NITSAN\NsCloudflare\Controller\DashboardController::class => 'analytics, ajaxAnalytics'],
            [
                'access' => 'user,group',
                'icon' => 'EXT:ns_cloudflare/Resources/Public/Icons/module-analytics.png',
                'labels' => 'LLL:EXT:ns_cloudflare/Resources/Private/Language/locallang_mod_analytics.xlf',
            ]
        );
    }
}
