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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InvalidationCommandTest extends UnitTestCase
{
    #[Test]
    public function invalidatesGivenPathsInConfiguredDistribution(): void
    {
        $extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('getCloudFrontDistributions')->willReturn(['DIST1']);

        $invalidatedDistributions = [];
        $cloudFrontRepository = $this->createMock(CloudFrontRepository::class);
        $cloudFrontRepository
            ->expects(self::once())
            ->method('createInvalidation')
            ->willReturnCallback(static function (string $distribution, array $paths) use (&$invalidatedDistributions): array {
                $invalidatedDistributions[$distribution] = $paths;

                return ['Invalidation' => ['InvalidationBatch' => ['Paths' => ['Items' => $paths]]]];
            });

        $commandTester = new CommandTester(new InvalidationCommand($cloudFrontRepository, $extensionConfiguration));
        $commandTester->execute(['paths' => ['/a.json*']]);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertSame(['DIST1' => ['/a.json*']], $invalidatedDistributions);
    }
}
