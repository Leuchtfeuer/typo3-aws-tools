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

use Leuchtfeuer\AwsTools\Configuration\CdnConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\SingletonInterface;

class CdnEventListener implements SingletonInterface
{
    private bool $initialized = false;

    private bool $responsible = false;

    private string $host = '';

    public function __construct(
        private readonly CdnConfiguration $cdnConfiguration,
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

        $this->responsible = $this->cdnConfiguration
            ->isReplacerEnabled(CdnConfiguration::REPLACER_EVENT_LISTENER, $language, $request);

        if ($this->responsible) {
            $this->host = $this->cdnConfiguration->resolveHost($language);
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

        $site = $request->getAttribute('site');
        if ($site !== null) {
            $languages = $site->getAttribute('languages');
            return reset($languages) ?: [];
        }

        return [];
    }
}
