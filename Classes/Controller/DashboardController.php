<?php

namespace NITSAN\NsCloudflare\Controller;

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

use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use NITSAN\NsCloudflare\Services\CloudflareService;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use NITSAN\NsCloudflare\ExtensionManager\Configuration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

/**
 * Dashboard controller.
 *
 */
class DashboardController extends ActionController
{
    /** @var array */
    protected $config;

    /** @var \NITSAN\NsCloudflare\Services\CloudflareService */
    protected $cloudflareService;

    /** @var array */
    protected $zones;

    protected  ModuleTemplateFactory $moduleTemplateFactory;

    /**
     * @param \TYPO3\CMS\Backend\Template\ModuleTemplateFactory $moduleTemplateFactory
     * @param \TYPO3\CMS\Core\Configuration\ExtensionConfiguration $extensionConfiguration
     * @param \NITSAN\NsCloudflare\Services\CloudflareService $cloudflareService
     */
    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        ExtensionConfiguration $extensionConfiguration,
        CloudflareService $cloudflareService
    ) {
        $this->config = $extensionConfiguration->get(Configuration::KEY);
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->cloudflareService = $cloudflareService;
        $domains = GeneralUtility::trimExplode(',', $this->config['domains'], true);
        $this->zones = [];
        foreach ($domains as $domain) {
            list($identifier, $zone) = explode('|', $domain);
            $this->zones[$identifier] = $zone;
        }
    }

    /**
     * Default action: analytics
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function analyticsAction(): ResponseInterface
    {
        $defaultIdentifier = null;
        if (!empty($this->zones)) {
            $defaultIdentifier = key($this->zones);
        }

        $assign = [
            'zones' => $this->zones,
            'defaultIdentifier' => $defaultIdentifier,
            'defaultZone' => $defaultIdentifier !== null ? $this->zones[$defaultIdentifier] : null,
            'periods' => $this->getAvailablePeriods($defaultIdentifier),
        ];

        $this->view->assignMultiple($assign);
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        // $this->addZoneAndPeriodsToButtonBar($moduleTemplate->getDocHeaderComponent());
        $moduleTemplate->setContent($this->view->render());
        
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @param DocHeaderComponent $docHeaderComponent
     * @return void
     */
    private function addZoneAndPeriodsToButtonBar(DocHeaderComponent $docHeaderComponent): void
    {
        $version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        $menuRegistry = $docHeaderComponent->getMenuRegistry();
        $zoneMenu = $menuRegistry->makeMenu()
            ->setIdentifier('zone');
        $zoneMenu->addMenuItem(
            $zoneMenu->makeMenuItem()
                ->setTitle('Pick a zone')
                ->setHref('#')
        );

        $defaultIdentifier = null;
        if (!empty($this->zones)) {
            $defaultIdentifier = key($this->zones);
            // Make first value default
            foreach ($this->zones as $identifier => $zone) {
                $menuItem = $zoneMenu->makeMenuItem()
                    ->setTitle($zone)
                    ->setHref('#' . $identifier)
                    ->setActive($identifier === $defaultIdentifier);
                $zoneMenu->addMenuItem($menuItem);
            }
        } else {
            $menuItem = $zoneMenu->makeMenuItem()
                ->setTitle('No zones found')
                ->setHref('#unknown')
                ->setActive(true);
            $zoneMenu->addMenuItem($menuItem);
        }
        $menuRegistry->addMenu($zoneMenu);

        // Period Menu
        $periodMenu = $menuRegistry->makeMenu()
            ->setIdentifier('period');
        $periodMenu->addMenuItem(
            $periodMenu->makeMenuItem()
                ->setTitle('Pick a period')
                ->setHref('#')
        );

        $periods = $this->getAvailablePeriods($defaultIdentifier);
        if (!empty($periods)) {
            $defaultValue = '1440';

            foreach ($periods as $value => $label) {
                $menuItem = $periodMenu->makeMenuItem()
                    ->setTitle($label)
                    ->setHref('#' . $value)
                    ->setActive($value === $defaultValue);
                $periodMenu->addMenuItem($menuItem);
            }
        } else {
            $menuItem = $periodMenu->makeMenuItem()
                ->setTitle('No periods found')
                ->setHref('#')
                ->setActive(true);
            $periodMenu->addMenuItem($menuItem);
        }
        $menuRegistry->addMenu($periodMenu);

        $buttonBar = $docHeaderComponent->getButtonBar();
        // Shortcut button
        $shortCutButton = $buttonBar->makeShortcutButton();
        $shortCutButton
            ->setDisplayName('Cloudflare: Analytics');
        if($version == 11) {
            $shortCutButton
                ->setRouteIdentifier('txcloudflare_analytics');
        } else {
            $shortCutButton
                ->setRouteIdentifier('txcloudflare');
        }
        $buttonBar->addButton($shortCutButton);
    }

    /**
     * Returns the JSON data for the requests analytics.
     *
     * @return JsonResponse
     */
    public function ajaxAnalyticsAction(ServerRequestInterface $serverRequest): JsonResponse
    {
        $zone = $serverRequest->getQueryParams()['zone'] ?? 'unknown';
        if (empty($zone)) {
            return new JsonResponse([], 204);
        }
        $since = $serverRequest->getQueryParams()['since'] ?? 0;

        $availablePeriods = $this->getAvailablePeriods($zone);
        if (!isset($availablePeriods[$since])) {
            $since = (int)key($availablePeriods);
        }
        try {
            $cfData = $this->cloudflareService->send('/zones/' . $zone . '/analytics/dashboard', ['since' => -$since]);
        } catch (\RuntimeException $e) {
            $cfData = [];
        }

        if (!isset($cfData['success'])) {
            return new JsonResponse([], 204);
        }

        $data = [
            'periods' => $availablePeriods,
            'period' => $this->translate('period.' . $since),
            'timeseries' => isset($cfData['result']['timeseries']) ? $cfData['result']['timeseries']:[],
        ];

        // Compute some additional statistics
        $uniquesMinimum = PHP_INT_MAX;
        $uniquesMaximum = 0;
        $threatsMaximumCountry = 0;
        $threatsTopCountry = 'N/A';
        $threatsMaximumType = 0;
        $threatsTopType = 'N/A';

        foreach ($data['timeseries'] as $tsKey => $info) {
            $threats = [
                'all' => $info['threats']['all'],
                'type' => [],
            ];

            // Fix info from Cloudflare API
            if (!is_array($info['threats']['type'])) {
                $info['threats']['type'] = [];
            }
            if (!is_array($info['threats']['country'])) {
                $info['threats']['country'] = [];
            }

            if ($info['uniques']['all'] < $uniquesMinimum) {
                $uniquesMinimum = $info['uniques']['all'];
            }
            if ($info['uniques']['all'] > $uniquesMaximum) {
                $uniquesMaximum = $info['uniques']['all'];
            }
            foreach ($info['threats']['country'] as $country => $count) {
                if ($count > $threatsMaximumCountry) {
                    $threatsMaximumCountry = $count;
                    $threatsTopCountry = $country;
                }
            }
            foreach ($info['threats']['type'] as $type => $count) {
                $threatName = $this->getThreatName($type);
                if ($count > $threatsMaximumType) {
                    $threatsMaximumType = $count;
                    $threatsTopType = $threatName;
                }
                $threats['type'][$type] = [
                    'name' => $threatName,
                    'all' => $count,
                ];
            }

            $data['timeseries'][$tsKey]['threats'] = $threats;
        }
        if(isset($data['totals'])) {
            $data['totals'] = array_merge_recursive($cfData['result']['totals'], [
                'requests' => [
                    'c1' => number_format($cfData['result']['totals']['requests']['all']),
                    'c2' => number_format($cfData['result']['totals']['requests']['cached']),
                    'c3' => number_format($cfData['result']['totals']['requests']['uncached']),
                ],
                'bandwidth' => [
                    'c1' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['all']),
                    'c2' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['cached']),
                    'c3' => GeneralUtility::formatSize($cfData['result']['totals']['bandwidth']['uncached']),
                ],
                'uniques' => [
                    'c1' => number_format($cfData['result']['totals']['uniques']['all']),
                    'c2' => $uniquesMaximum,
                    'c3' => $uniquesMinimum,
                ],
                'threats' => [
                    'c1' => number_format($cfData['result']['totals']['threats']['all']),
                    'c2' => $threatsTopCountry,
                    'c3' => $threatsTopType,
                ],
            ]);
            // Sort some data for better display as graphs
            arsort($data['totals']['bandwidth']['content_type']);
            arsort($data['totals']['requests']['content_type']);
        }


        return new JsonResponse($data);
    }

    /**
     * Returns the available periods for a given zone (depends on the Cloudflare plan).
     *
     * @param string|null $zone
     * @return array
     */
    protected function getAvailablePeriods(?string $zone): array
    {
        if ($zone === null) {
            return [];
        }

        $periods = [
            '30' => $this->translate('period.30'),
            '360' => $this->translate('period.360'),
            '720' => $this->translate('period.720'),
            '1440' => $this->translate('period.1440'),
            '10080' => $this->translate('period.10080'),
            '43200' => $this->translate('period.43200'),
        ];
        try {
            $info = $this->cloudflareService->send('/zones/' . $zone);
        } catch (\RuntimeException $e) {
            $info = [];
        }

        if (isset($info['success'])) {
            switch ($info['result']['plan']['legacy_id'] ?? 'unknown') {
                case 'free':
                    unset($periods['30'], $periods['360'], $periods['720']);
                    break;
                case 'business':
                case 'pro':
                    unset($periods['30']);
                    break;
                case 'enterprise':
                    break;
            }
        }

        return $periods;
    }

    /**
     * Returns a human-readable name of a given threat type.
     *
     * See https://support.cloudflare.com/hc/en-us/articles/204191238-What-are-the-types-of-Threats-
     *
     * @param string $type
     * @return string
     */
    protected function getThreatName(string $type): string
    {
        $name = $this->translate('dashboard.threats.' . $type);
        if (empty($name)) {
            return $type;
        }
        return $name;
    }

    /**
     * Returns the localized label of a given key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param array|null $arguments the arguments of the extension, being passed over to vsprintf
     * @return string Localized label
     */
    protected function translate(string $key, ?array $arguments = null): string
    {
        return LocalizationUtility::translate($key, Configuration::KEY, $arguments);
    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(
        ServerRequestInterface $request
    ): ModuleTemplate {
        return $this->moduleTemplateFactory->create($request);
    }
}
