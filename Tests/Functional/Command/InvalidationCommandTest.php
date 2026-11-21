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

namespace Leuchtfeuer\AwsTools\Tests\Functional\Command;

use Leuchtfeuer\AwsTools\Command\InvalidationCommand;
use Leuchtfeuer\AwsTools\Tests\Functional\AwsToolsFunctionalTestCase;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

final class InvalidationCommandTest extends AwsToolsFunctionalTestCase
{
    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function invalidatingCopiedCdnUrlSendsItsPath(string $fileName, string $expectedPath): void
    {
        $tester = new CommandTester($this->get(InvalidationCommand::class));

        $tester->execute(['paths' => [FileNameCases::CDN_HOST . $expectedPath]]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }
}
