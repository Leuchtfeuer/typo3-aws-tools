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

namespace Leuchtfeuer\AwsTools\Tests\Functional\Controller;

use Leuchtfeuer\AwsTools\Controller\BackendController;
use Leuchtfeuer\AwsTools\Tests\Functional\AwsToolsFunctionalTestCase;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;

final class BackendControllerTest extends AwsToolsFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUser(1);
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function invalidatingFileFromFileListUsesItsCdnPath(string $fileName, string $expectedPath): void
    {
        $response = $this->invalidateFromFileList('File', '/' . FileNameCases::FOLDER . '/' . $fileName);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }

    /**
     * Sends the request body CloudFrontInvalidationModule.js builds from the action button's data attributes.
     */
    private function invalidateFromFileList(string $type, string $identifier): ResponseInterface
    {
        $body = new Stream('php://temp', 'rw');
        $body->write((string)json_encode(['type' => $type, 'identifier' => $identifier, 'storage' => '1']));
        $body->rewind();

        return $this->get(BackendController::class)->invalidateAction(
            (new ServerRequest('https://www.example.com/typo3/ajax/awstools/invalidate', 'POST'))->withBody($body)
        );
    }
}
