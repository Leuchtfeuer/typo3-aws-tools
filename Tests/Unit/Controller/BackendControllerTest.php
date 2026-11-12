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

namespace Leuchtfeuer\AwsTools\Tests\Unit\Controller;

use Aws\Command;
use Aws\Exception\AwsException;
use Leuchtfeuer\AwsTools\Controller\BackendController;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\AwsTools\Service\InvalidationPathNormalizer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendControllerTest extends UnitTestCase
{
    #[Test]
    public function invalidateActionPassesFullPublicUrlThatNormalizesToItsPath(): void
    {
        $storage = self::createStub(ResourceStorage::class);
        $storage->method('checkUserActionPermission')->willReturn(true);

        $file = self::createStub(File::class);
        $file->method('getPublicUrl')->willReturn('https://cdn.example.com/fileadmin/a.pdf');
        $file->method('getStorage')->willReturn($storage);
        $storage->method('getFile')->willReturn($file);

        $storageRepository = self::createStub(StorageRepository::class);
        $storageRepository->method('findByUid')->willReturn($storage);

        $context = self::createStub(Context::class);
        $context->method('getPropertyFromAspect')->willReturn(true);

        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('getCloudFrontDistributions')->willReturn(['DIST']);

        $invalidatedPath = null;
        $cloudFrontRepository = $this->createMock(CloudFrontRepository::class);
        $cloudFrontRepository
            ->expects(self::once())
            ->method('createBatchInvalidation')
            ->willReturnCallback(static function (array $distributions, string $path) use (&$invalidatedPath): array {
                $invalidatedPath = $path;
                // Leaves the controller through its AWS error branch, which needs no localization
                throw new AwsException('stop', new Command('CreateInvalidation'));
            });

        $body = new Stream('php://temp', 'rw');
        $body->write((string)json_encode(['type' => 'File', 'identifier' => '/a.pdf', 'storage' => 1]));
        $body->rewind();

        $subject = new BackendController(
            $cloudFrontRepository,
            $extensionConfiguration,
            self::createStub(ResourceFactory::class),
            $storageRepository,
            $context
        );
        $subject->invalidateAction((new ServerRequest())->withBody($body));

        self::assertSame('/fileadmin/a.pdf', (new InvalidationPathNormalizer())->normalize((string)$invalidatedPath));
    }
}
