<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;


class CdnEventListener implements SingletonInterface
{
    protected $responsible = false;

    protected $host = '';

    public function __construct(EnvironmentService $environmentService)
    {
        if ($environmentService->isEnvironmentInFrontendMode()) {
            $language = [];

            if (array_key_exists('TYPO3_REQUEST', $GLOBALS)) {
                $language = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->toArray();
            } else {
                $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
                $calledBaseUri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
                $allSites = $siteConfiguration->getAllExistingSites();

                foreach($allSites as $site) {
                    $baseUri = (string)$site->getBase();

                    if ($baseUri === $calledBaseUri) {
                        $languages = $site->getAttribute('languages');
                        $language = reset($languages);
                        break;
                    }
                }
            }

            $replacer = $GLOBALS['TSFE']->config['config']['tx_awstools.']['replacer.'] ?? [];
            $this->responsible = filter_var($language['awstools_cdn_enabled'], FILTER_VALIDATE_BOOLEAN) === true && !empty($language['awstools_cdn_host'] && $replacer['eventListener'] === '1');

            if ($this->responsible) {
                $this->host = $language['awstools_cdn_host'];
            }
        }
    }

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        if (!$this->responsible) {
            return;
        }

        $driver = $event->getDriver();
        $resource = $event->getResource();

        if ($driver instanceof AbstractHierarchicalFilesystemDriver && $resource instanceof FileInterface) {
            $publicUrl = $driver->getPublicUrl($resource->getIdentifier());
            $event->setPublicUrl($this->host . $publicUrl);
        }
    }
}
