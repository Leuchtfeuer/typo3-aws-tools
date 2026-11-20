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

namespace Leuchtfeuer\AwsTools\Tests\Unit\Domain\Repository;

use Aws\CloudFront\CloudFrontClient;
use Aws\MockHandler;
use Aws\Result;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Service\InvalidationPathNormalizer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CloudFrontRepositoryTest extends UnitTestCase
{
    private MockHandler $mockHandler;

    private CloudFrontRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $this->mockHandler->append(new Result([]));

        $client = new CloudFrontClient([
            'region' => 'us-east-1',
            'version' => Constants::VERSION,
            'credentials' => false,
            'handler' => $this->mockHandler,
        ]);
        $this->subject = new CloudFrontRepository($client, new InvalidationPathNormalizer());
    }

    #[Test]
    public function createInvalidationSendsNormalizedPaths(): void
    {
        $this->subject->createInvalidation('DIST', ['/_assets/x/a.json*', 'https://cdn.example.com/b.json?1']);

        $paths = $this->mockHandler->getLastCommand()['InvalidationBatch']['Paths'];
        self::assertSame(['/_assets/x/a.json*', '/b.json?1'], $paths['Items']);
        self::assertSame(2, $paths['Quantity']);
    }
}
