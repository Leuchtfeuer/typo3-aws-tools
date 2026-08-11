<?php

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
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

    public function __construct(private readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry)
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

            $this->responsible = $this->isCdnReplacementEnabled($language);

            if ($this->responsible) {
                $this->host = (string)$language['awstools_cdn_host'];
            }
        }
    }

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        $resource = $event->getResource();

        if (!$this->responsible
            || ($resource instanceof File && $this->onlineMediaHelperRegistry->getOnlineMediaHelper($resource) !== false)) {
            return;
        }

        $driver = $event->getDriver();
        if ($driver instanceof AbstractHierarchicalFilesystemDriver && $resource instanceof FileInterface) {
            $identifier = $resource->getIdentifier();
            if ($identifier === '') {
                return;
            }
            // @extensionScannerIgnoreLine
            $publicUrl = $driver->getPublicUrl($identifier);
            $event->setPublicUrl($this->host . $publicUrl);
        }
    }

    private function isCdnReplacementEnabled(array $language): bool
    {
        try {
            $typoScript = GeneralUtility::removeDotsFromTS(
                GeneralUtility::makeInstance(ConfigurationManagerInterface::class)
                    ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            );
        } catch (\Exception) {
            return false;
        }

        $config = $typoScript['config']['tx_awstools'] ?? [];

        return $this->isFlagEnabled($config['enabled'] ?? null)
            && $this->isFlagEnabled($config['replacer']['eventListener'] ?? null)
            && $this->isFlagEnabled($language['awstools_cdn_enabled'] ?? null)
            && !empty($language['awstools_cdn_host']);
    }

    private function isFlagEnabled(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
