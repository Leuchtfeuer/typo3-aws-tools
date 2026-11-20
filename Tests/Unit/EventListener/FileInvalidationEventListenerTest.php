<?php

declare(strict_types=1);

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\AwsTools\Tests\Unit\EventListener;

use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\AwsTools\EventListener\FileInvalidationEventListener;
use Leuchtfeuer\AwsTools\Exception\InvalidInvalidationPathException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileInvalidationEventListenerTest extends UnitTestCase
{
    #[Test]
    public function logsInvalidPathInsteadOfFailing(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('getCloudFrontDistributions')->willReturn(['DIST']);

        $cloudFrontRepository = self::createStub(CloudFrontRepository::class);
        $cloudFrontRepository
            ->method('createBatchInvalidation')
            ->willThrowException(InvalidInvalidationPathException::containsTilde('/fileadmin/a~b.pdf'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('/fileadmin/a~b.pdf'));

        $file = self::createStub(FileInterface::class);
        $file->method('getPublicUrl')->willReturn('/fileadmin/a~b.pdf');

        $subject = new FileInvalidationEventListener($extensionConfiguration, $cloudFrontRepository);
        $subject->setLogger($logger);

        $subject->invalidateFile(new AfterFileContentsSetEvent($file, 'content'));
    }
}
