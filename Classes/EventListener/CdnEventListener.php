<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class CdnEventListener implements SingletonInterface
{
    protected $responsible = false;

    protected $host = '';

    public function __construct(EnvironmentService $environmentService)
    {
        if ($environmentService->isEnvironmentInFrontendMode()) {
            $language = $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->toArray();
            $this->responsible = (bool)($language['awstools_cdn_enabled'] ?? false) === true && !empty($language['awstools_cdn_host']);

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
