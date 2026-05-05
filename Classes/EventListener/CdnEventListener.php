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
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
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

        if (empty($language['awstools_cdn_enabled']) || empty($language['awstools_cdn_host'])) {
            return;
        }

        $this->responsible = $this->isCdnEnabledInTypoScript($request);

        if ($this->responsible) {
            $this->host = $language['awstools_cdn_host'];
        }
    }

    /**
     * Checks TypoScript config.tx_awstools for CDN activation flags.
     *
     * In eID contexts (e.g. tx_cms_showpic) TypoScript is not bootstrapped — returns true
     * so that CDN rewriting follows the site language config alone.
     */
    private function isCdnEnabledInTypoScript(ServerRequestInterface $request): bool
    {
        // TYPO3 14 native: available on full frontend page requests
        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        if ($frontendTypoScript instanceof FrontendTypoScript) {
            try {
                $config = $frontendTypoScript->getConfigArray()['tx_awstools.'] ?? [];
                return !empty($config['enabled']) && !empty($config['replacer.']['eventListener']);
            } catch (\RuntimeException) {
                // config not yet initialised — treat as unavailable
            }
        }

        // Extbase fallback: works on normal frontend pages, may fail in eID contexts
        try {
            $typoscript = $this->configurationManager
                ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            if (!empty($typoscript)) {
                $config = $typoscript['config']['tx_awstools.'] ?? [];
                return !empty($config['enabled']) && !empty($config['replacer.']['eventListener']);
            }
        } catch (\Exception) {
            // ConfigurationManager unavailable (no page context)
        }

        // eID context: TypoScript not bootstrapped — language config is sufficient
        return true;
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
        $normalizedParams = $request->getAttribute('normalizedParams');
        $calledBaseUri = $normalizedParams instanceof NormalizedParams
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
