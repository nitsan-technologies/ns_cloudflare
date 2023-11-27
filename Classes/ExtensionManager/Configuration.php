<?php

namespace NITSAN\NsCloudflare\ExtensionManager;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use NITSAN\NsCloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration class for the TYPO3 Extension Manager.
 *
 */
class Configuration implements SingletonInterface
{
    /** @var string */
    public const KEY = 'ns_cloudflare';

    /** @var array */
    protected $config;

    /** @var \NITSAN\NsCloudflare\Services\CloudflareService */
    protected $cloudflareService;

    /**
     * DI is not available within Admin Tools, so initialize everything on our own.
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration = null, CloudflareService $cloudflareService = null)
    {
        $this->config = $extensionConfiguration
            ?? GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::KEY);
        $this->cloudflareService = $cloudflareService
            ?? GeneralUtility::makeInstance(\NITSAN\NsCloudflare\Services\CloudflareService::class, $this->config);
    }

    /**
     * Returns an Extension Manager field for selecting domains.
     *
     * @param array $params
     * @return string
     */
    public function getDomains(array $params)
    {
        $extConf = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $extensionConf = $extConf->get('ns_cloudflare');
        $domainWithKey = explode(',',$extensionConf['domains']);
        $selectedDomains = [];
        foreach($domainWithKey as $domainData) {
            $mainDomain = explode('|',$domainData);
            if (!empty($mainDomain))
            {
                if(isset($mainDomain[1])) {
                    $selectedDomains[] = $mainDomain[1];
                }
            }
        }
        $domains = [];
        $out = [];
        try {
            $ret = $this->cloudflareService->send('/zones/');
            if ($ret['success']) {
                $data = $this->cloudflareService->sort($ret, 'name');
                foreach ($data['result'] as $zone) {
                    $domains[$zone['id']] = $zone['name'];
                }
            }
        } catch (\RuntimeException $e) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $e->getMessage(),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                true
            );
            $out[] = $flashMessage;
        }

        $i = 0;

        if (!empty($domains)) {
            $out[] = '<table class="table table-striped table-hover">';
            $out[] = '<thead>';
            $out[] = '<tr><th></th><th></th><th>' . htmlspecialchars($this->sL('settings.labels.zoneIdentifiers')) . '</th></tr>';
            $out[] = '<thead>';
            $out[] = '<tbody>';
        } else {
            $out[] = '<em>' . htmlspecialchars($this->sL('settings.labels.emptyList')) . '</em>';
        }

        foreach ($domains as $identifier => $domain) {
            $out[] = '<tr>';

            $value = $identifier . '|' . $domain;
            $checked = in_array($domain, $selectedDomains) || in_array($value, $selectedDomains)
                ? ' checked="checked"'
                : '';
            $out[] = '<td style="width:20px"><input type="checkbox" id="cloudflare_domain_' . $i . '" value="' . $value . '"' . $checked . ' onclick="toggleCloudflareDomains();" /></td>';
            $out[] = '<td style="padding-right:50px"><label for="cloudflare_domain_' . $i . '">' . htmlspecialchars($domain) . '</label></td>';
            $out[] = '<td><tt>' . htmlspecialchars($identifier) . '</tt></td>';
            $out[] = '</tr>';
            $i++;
        }

        if (!empty($domains)) {
            $out[] = '</tbody>';
            $out[] = '</table>';
        }

        $fieldId = str_replace(['[', ']'], '_', $params['fieldName']);
        $out[] = '<script type="text/javascript">';
        $out[] = <<<JS

function toggleCloudflareDomains() {
    var domains = new Array();
    for (var i = 0; i < {$i}; i++) {
        var e = document.getElementById("cloudflare_domain_" + i);
        if (e.checked) {
            domains.push(e.value);
        }
    }
    document.getElementById("{$fieldId}").value = domains.join(',');
}

JS;
        $out[] = '</script>';
        $out[] = '<input type="hidden" id="' . $fieldId . '" name="' . $params['fieldName'] . '" value="' . $params['fieldValue'] . '" />';

        if(!defined('LF')) {define('LF', chr(10));}

        return implode(LF, $out);
    }

    /**
     * Translates a message.
     *
     * @param string $key
     * @return string
     */
    protected function sL(string $key): string
    {
        return $GLOBALS['LANG']->sL('LLL:EXT:' . self::KEY . '/Resources/Private/Language/locallang_db.xlf:' . $key);
    }
}
