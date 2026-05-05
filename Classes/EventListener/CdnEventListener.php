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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NormalizedParams;
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
    private bool $initialized = false;

    private bool $responsible = false;

    private string $host = '';

    public function __construct(
        private readonly ConfigurationManagerInterface $configurationManager,
        private readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry
    ) {}

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        if (!$this->initialized) {
            $this->initializeFromRequest();
        }

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

    private function initializeFromRequest(): void
    {
        $this->initialized = true;

        $request = $this->resolveRequest();
        if (!$request instanceof ServerRequestInterface || !ApplicationType::fromRequest($request)->isFrontend()) {
            return;
        }

        $language = $this->resolveLanguage($request);

        try {
            $typoscript = $this->configurationManager
                ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

            $config = $typoscript['config']['tx_awstools.'] ?? [];
            $this->responsible = false;
            if (!empty($config['enabled']) && !empty($config['replacer.']['eventListener'])
                && !empty($language['awstools_cdn_enabled']) && !empty($language['awstools_cdn_host'])
            ) {
                $this->responsible = true;
            }
        } catch (\Exception) {
            $this->responsible = false;
        }

        if ($this->responsible) {
            $this->host = $language['awstools_cdn_host'];
        }
    }

    private function resolveRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    /** @return array<string, mixed> */
    private function resolveLanguage(ServerRequestInterface $request): array
    {
        $languageAttribute = $request->getAttribute('language');
        if ($languageAttribute !== null) {
            return $languageAttribute->toArray();
        }

        // Fallback: resolve language from site configuration using the request URI
        /** @var SiteConfiguration $siteConfiguration */
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $calledBaseUri = $normalizedParams !== null
            ? rtrim($normalizedParams->getRequestDir(), '/')
            : rtrim(GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR'), '/');
        $allSites = $siteConfiguration->getAllExistingSites();

        foreach ($allSites as $site) {
            $baseUri = rtrim((string)$site->getBase(), '/');

            if ($baseUri === $calledBaseUri) {
                $languages = $site->getAttribute('languages');
                return reset($languages) ?: [];
            }
        }

        if ($site = reset($allSites)) {
            // if no site matches, get the first as default
            $languages = $site->getAttribute('languages');
            return reset($languages) ?: [];
        }

        return [];
    }
}
