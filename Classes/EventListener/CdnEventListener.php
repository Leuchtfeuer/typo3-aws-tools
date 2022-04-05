<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class CdnEventListener implements SingletonInterface
{
    protected bool $responsible = false;

    protected string $host = '';

    public function __construct()
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
            $language = [];

            if (array_key_exists('TYPO3_REQUEST', $GLOBALS)) {
                $language = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->toArray();
            } else {
                /**
                 * @var SiteConfiguration $siteConfiguration
                 */
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

            $this->responsible = filter_var($language['awstools_cdn_enabled'], FILTER_VALIDATE_BOOLEAN) === true && !empty($language['awstools_cdn_host']);

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
            // @extensionScannerIgnoreLine
            $publicUrl = $driver->getPublicUrl($resource->getIdentifier());
            $event->setPublicUrl($this->host . $publicUrl);
        }
    }
}
