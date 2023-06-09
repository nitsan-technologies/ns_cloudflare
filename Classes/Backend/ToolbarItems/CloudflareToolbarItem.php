<?php

namespace NITSAN\NsCloudflare\Backend\ToolbarItems;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use NITSAN\NsCloudflare\Services\CloudflareService;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use NITSAN\NsCloudflare\ExtensionManager\Configuration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

/**
 * Toolbar Menu handler.
 *
 */
class CloudflareToolbarItem implements ToolbarItemInterface
{
    /** @var array */
    protected $config;

    /** @var \TYPO3\CMS\Core\Context\Context */
    protected $context;

    /** @var \NITSAN\NsCloudflare\Services\CloudflareService */
    protected $cloudflareService;

    /**
     * Default constructor.
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration, Context $context, CloudflareService $cloudflareService)
    {
        $this->config = $extensionConfiguration->get(Configuration::KEY);
        $this->context = $context;
        $this->cloudflareService = $cloudflareService;
        $this->getLanguageService()->includeLLFile('EXT:ns_cloudflare/Resources/Private/Language/locallang.xlf');
        // $this->getPageRenderer()->loadJavaScriptModule('TYPO3/CMS/Cloudflare/Toolbar/CloudflareMenu');
    }

    /**
     * Checks whether the user has access to this toolbar item.
     *
     * @return bool true if user has access, false if not
     */
    public function checkAccess(): bool
    {
        try {
            return $this->context->getPropertyFromAspect('backend.user', 'isAdmin');
        } catch (AspectNotFoundException) {
            return false;
        }
    }

    /**
     * Renders the toolbar icon.
     *
     * @return string HTML
     */
    public function getItem(): string
    {
        if(!defined('LF')) {
            define('LF', chr(10));
        }
        $title = $this->getLanguageService()->sL('toolbarItem');

        $item = [];
        $item[] = '<span title="' . htmlspecialchars($title) . '">' . $this->getSpriteIcon('extensions-ns_cloudflare-cloudflare-icon', [], 'inline') . '</span>';
        $badgeClasses = ['badge', 'badge-danger', 'toolbar-item-badge'];

        $item[] = '<span class="' . implode(' ', $badgeClasses) . '" id="tx-cloudflare-counter" style="display:none">0</span>';
        return implode(LF, $item);
    }

    /**
     * Renders the drop down.
     *
     * @return string HTML
     */
    public function getDropDown(): string
    {
        $languageService = $this->getLanguageService();
        $entries = [];
        $version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        if (!empty($domains)) {
            foreach ($domains as $domain) {
                list($identifier, ) = explode('|', $domain, 2);
                try {
                    $ret = $this->cloudflareService->send('/zones/' . $identifier);

                    if ($ret['success']) {
                        $zone = $ret['result'];

                        switch (true) {
                            case $zone['development_mode'] > 0:
                                $status = 'dev-mode';
                                $active = 0;
                                break;
                            case $zone['status'] === 'active':
                                $status = 'active';
                                $active = 1;
                                break;
                            case $zone['paused']:
                            default:
                                $status = 'deactivated';
                                $active = null;
                                break;
                        }
                        if ($version == 11) {
                            $entries[] = '<div class="dropdown-table-row" data-zone-status="' . $status . '">';
                            $entries[] = '    <div class="dropdown-table-column dropdown-table-column-top dropdown-table-icon">';
                            $entries[] = $this->getZoneIcon($status);
                            $entries[] = '    </div>';
                            $entries[] = '    <div class="dropdown-table-column">';
                            $entries[] = htmlspecialchars($zone['name']);
                            if ($active !== null) {
                                $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . '); return false;';
                                $entries[] = '<a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->sL('toggle_development') . '</a>';
                            } else {
                                $entries[] = $languageService->sL('zone_inactive');
                            }
                            $entries[] = '    </div>';
                            $entries[] = '</div>';
                        } else {
                            $entries[] = '<ul class="dropdown-list"><li><div class="dropdown-item t3js-toolbar-cache-flush-action"><span class="dropdown-item-columns"><span class="dropdown-item-column dropdown-item-column-icon text-success"><span class="t3js-icon icon icon-size-small icon-state-default icon-actions-system-cache-clear-impact-low" data-identifier="actions-system-cache-clear-impact-low"><span class="icon-markup">';
                            $entries[] = $this->getZoneIcon($status);
                            $entries[] = '</span></span></span><span class="dropdown-item-column dropdown-item-column-text">';
                            $entries[] = htmlspecialchars($zone['name']);
                            $entries[] = '<br><small class="text-body-secondary">';
                            if ($active !== null) {
                                $onClickCode = 'TYPO3.CloudflareMenu.toggleDevelopmentMode(\'' . $identifier . '\', ' . $active . '); return false;';
                                $entries[] = '<a href="#" onclick="' . htmlspecialchars($onClickCode) . '">' . $languageService->sL('toggle_development') . '</a>';
                            } else {
                                $entries[] = $languageService->sL('zone_inactive');
                            }
                            $entries[] = '</small></span></span></div></li></ul>';
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Nothing to do
                }
            }
        }
        $content = '';
        $version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        if ($version == 11) {
            if (!empty($entries)) {
                $content .= '<h3 class="dropdown-headline">[NITSAN] Cloudflare</h3>';
                $content .= '<div class="dropdown-table">' . implode('', $entries) . '</div>';
            } else {
                $content .= '<p>' . $languageService->sL('no_domains') . '</p>';
            }
        }else{
            if (!empty($entries)) {
                $content .= '<h3 class="dropdown-headline">[NITSAN] Cloudflare</h3>';
                $content .= '<ul class="dropdown-list">' . implode('', $entries) . '</ul>';
            } else {
                $content .= '<p>' . $languageService->sL('no_domains') . '</p>';
            }
        }

        return $content;
    }

    /**
     * Returns the icon associated to a given Cloudflare status.
     *
     * @param string $status
     * @return string
     */
    protected function getZoneIcon($status)
    {
        $languageService = $this->getLanguageService();
        switch ($status) {
            case 'active':
                $icon = $this->getSpriteIcon('extensions-ns_cloudflare-online', ['title' => $languageService->sL('zone_active')]);
                break;
            case 'dev-mode':
                $icon = $this->getSpriteIcon('extensions-ns_cloudflare-direct', ['title' => $languageService->sL('zone_development')]);
                break;
            case 'deactivated':
            default:
                $icon = $this->getSpriteIcon('extensions-ns_cloudflare-offline', ['title' => $languageService->sL('zone_inactive')]);
                break;
        }
        return $icon;
    }

    /**
     * Returns the HTML code for a sprite icon.
     *
     * @param string $iconName
     * @param array $options
     * @param string $alternativeMarkupIdentifier
     * @return string
     */
    protected function getSpriteIcon($iconName, array $options, $alternativeMarkupIdentifier = null)
    {
        /** @var IconFactory $iconFactory */
        static $iconFactory = null;

        if ($iconFactory === null) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
        $icon = $iconFactory->getIcon($iconName, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render($alternativeMarkupIdentifier);
        if (strpos($icon, '<img ') !== false) {
            $icon = str_replace('<img ', '<img title="' . htmlspecialchars($options['title']) . '" ', $icon);
        }

        return $icon;
    }

    /**
     * No additional attributes.
     *
     * @return array List item HTML attributes
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down.
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Position relative to others.
     *
     * @return int
     */
    public function getIndex()
    {
        return 25;
    }

    /******************
     *** AJAX CALLS ***
     ******************/

    /**
     * Renders the menu so that it can be returned as response to an AJAX call
     *
     * @param ServerRequestInterface $request
     *
     * @return JsonResponse
     */
    public function renderAjax(ServerRequestInterface $request)
    {
        $menu = $this->getDropDown();

        return new JsonResponse([
            'success' => true,
            'html' => $menu,
        ]);
    }

    /**
     * Toggles development mode for a given zone.
     *
     * @param ServerRequestInterface $request
     *
     * @return JsonResponse
     */
    public function toggleDevelopmentMode(ServerRequestInterface $request)
    {
        $zone = $request['zone'];
        $active = $request['active'];
        $ret = [];
        try {
            $ret = $this->cloudflareService->send('/zones/' . $zone . '/settings/development_mode', [
                'value' => $active ? 'on' : 'off',
            ], 'PATCH');
        } catch (\RuntimeException $e) {
            // Nothing to do
        }

        return new JsonResponse([
            'success' => $ret['success'] === true,
        ]);
    }

    /**
     * Purges cache from all configured zones.
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function purge(ServerRequestInterface $request)
    {
        /** @var \NITSAN\NsCloudflare\Hooks\TCEmain $tceMain */
        $tceMain = GeneralUtility::makeInstance(\NITSAN\NsCloudflare\Hooks\TCEmain::class);
        $tceMain->clearCache();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**********************
     *** HELPER METHODS ***
     **********************/

    /**
     * Returns current PageRenderer.
     *
     * @return \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns the LanguageService.
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
