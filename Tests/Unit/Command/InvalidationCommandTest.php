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

namespace Leuchtfeuer\AwsTools\Tests\Unit\Command;

use Leuchtfeuer\AwsTools\Command\InvalidationCommand;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\AwsTools\Service\InvalidationPathNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InvalidationCommandTest extends UnitTestCase
{
    #[Test]
    public function rejectsInvalidPathWithoutCallingCloudFront(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('getCloudFrontDistributions')->willReturn(['DIST1']);

        $cloudFrontRepository = $this->createMock(CloudFrontRepository::class);
        $cloudFrontRepository->expects(self::never())->method('createInvalidation');

        $commandTester = new CommandTester(new InvalidationCommand($cloudFrontRepository, $extensionConfiguration, new InvalidationPathNormalizer()));
        $commandTester->execute(['paths' => ['/ok.pdf', '/a~b.pdf']]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('a~b.pdf', $commandTester->getDisplay());
    }
}
