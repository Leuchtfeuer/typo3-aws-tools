<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class CdnEventListener implements SingletonInterface
{
    protected bool $responsible = false;

    protected string $host = '';

    public function __construct()
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (empty($request) || ApplicationType::fromRequest($request)->isFrontend()) {
            $language = [];

            if (!empty($request)) {
                $language = $request->getAttribute('language')->toArray();
            } else {
                /**
                 * @var SiteConfiguration $siteConfiguration
                 */
                $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
                $calledBaseUri = rtrim(GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR'), '/');
                $allSites = $siteConfiguration->getAllExistingSites();

                foreach ($allSites as $site) {
                    $baseUri = rtrim((string)$site->getBase(), '/');

                    if ($baseUri === $calledBaseUri) {
                        $languages = $site->getAttribute('languages');
                        $language = reset($languages);
                        break;
                    }
                }

                if (count($language) === 0 && $site = reset($allSites)) {
                    // if no site matches, get the first as default
                    $languages = $site->getAttribute('languages');
                    $language = reset($languages);
                }
            }

            $typoscript = GeneralUtility::makeInstance(ConfigurationManagerInterface::class)
                ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            $config = $typoscript['config']['tx_awstools.'] ?? [];
            $this->responsible = isset($config['enabled'], $config['replacer.']['eventListener'], $language['awstools_cdn_enabled']) && $config['enabled'] && filter_var($language['awstools_cdn_enabled'], FILTER_VALIDATE_BOOLEAN) === true && !empty($language['awstools_cdn_host']) && $config['replacer.']['eventListener'] === '1';

            if ($this->responsible) {
                $this->host = $language['awstools_cdn_host'];
            }
        }
    }

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        $resource = $event->getResource();

        if (!$this->responsible
            || ($resource instanceof File && OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($resource) !== false)) {
            return;
        }

        $driver = $event->getDriver();
        if ($driver instanceof AbstractHierarchicalFilesystemDriver && $resource instanceof FileInterface) {
            // @extensionScannerIgnoreLine
            $publicUrl = $driver->getPublicUrl($resource->getIdentifier());
            $event->setPublicUrl($this->host . $publicUrl);
        }
    }
}
