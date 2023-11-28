<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') || defined('TYPO3_MODE') || die();

$_EXTKEY = 'ns_cloudflare';

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Resources/Public/Css/visual/toolbar.css';

$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
->get($_EXTKEY);

$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
