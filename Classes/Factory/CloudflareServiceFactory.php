<?php

namespace NITSAN\NsCloudflare\Factory;

use NITSAN\NsCloudflare\ExtensionManager\Configuration;
use NITSAN\NsCloudflare\Services\CloudflareService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CloudflareServiceFactory
{
    /**
     * @param \TYPO3\CMS\Core\Configuration\ExtensionConfiguration $extensionConfiguration
     * @return \NITSAN\NsCloudflare\Services\CloudflareService
     */
    public function __invoke(ExtensionConfiguration $extensionConfiguration): CloudflareService
    {
        return GeneralUtility::makeInstance(
            CloudflareService::class,
            $extensionConfiguration->get(Configuration::KEY)
        );
    }
}
