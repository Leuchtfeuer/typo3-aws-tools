<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use Aws\CloudFront\Exception\CloudFrontException;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileReplacedEvent;
use TYPO3\CMS\Core\SingletonInterface;

class FileInvalidationEventListener implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $distributions;
    private $cloudFrontRepository;

    public function __construct(ExtensionConfiguration $extensionConfiguration, CloudFrontRepository $cloudFrontRepository)
    {
        $this->distributions = $extensionConfiguration->getCloudFrontDistributions();
        $this->cloudFrontRepository = $cloudFrontRepository;
    }

    public function invalidateOnBackendUploadReplace(BeforeFileReplacedEvent $event)
    {
        $this->invalidatePath($event->getFile()->getPublicUrl());
    }

    public function invalidateFile(AfterFileContentsSetEvent $event): void
    {
        $this->invalidatePath($event->getFile()->getPublicUrl());
    }

    private function invalidatePath($path): void
    {
        try {
            $this->cloudFrontRepository->createBatchInvalidation($this->distributions, $path);
        } catch (CloudFrontException $exception) {
            $this->logger->error(sprintf('%s:%s', $exception->getAwsErrorCode(), $exception->getAwsErrorMessage()));
        }
    }
}
