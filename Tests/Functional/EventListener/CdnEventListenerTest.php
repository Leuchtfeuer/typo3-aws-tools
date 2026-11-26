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

namespace Leuchtfeuer\AwsTools\Tests\Functional\EventListener;

use Leuchtfeuer\AwsTools\Tests\Functional\AwsToolsFunctionalTestCase;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class CdnEventListenerTest extends AwsToolsFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->get(SiteWriter::class)->write('test', [
            'rootPageId' => 1,
            'base' => 'https://www.example.com/',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'enabled' => true,
                    'base' => '/',
                    'locale' => 'en_US.UTF-8',
                    'navigationTitle' => 'English',
                    'flag' => 'us',
                    'awstools_cdn_enabled' => true,
                    // Without trailing slash, as label and documentation show it
                    'awstools_cdn_host' => FileNameCases::CDN_HOST,
                ],
            ],
        ]);
        $this->setUpFrontendRootPage(1, [
            'EXT:aws_tools/Configuration/TypoScript/ReplaceConfig/setup.typoscript',
            'EXT:aws_tools/Tests/Functional/Fixtures/Frontend/PublicUrls.typoscript',
        ]);
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function publicUrlPointsToCdnHostWithEncodedPath(string $fileName, string $expectedPath): void
    {
        $body = (string)$this->executeFrontendSubRequest(new InternalRequest('https://www.example.com/'))->getBody();

        self::assertStringContainsString('<li>' . FileNameCases::CDN_HOST . $expectedPath . '</li>', $body);
    }
}
