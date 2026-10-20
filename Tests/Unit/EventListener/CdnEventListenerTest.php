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

use Leuchtfeuer\AwsTools\Configuration\CdnConfiguration;
use Leuchtfeuer\AwsTools\EventListener\CdnEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CdnEventListenerTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{string, string, string}>
     */
    public static function publicUrlDataProvider(): \Generator
    {
        yield 'fileadmin file, host without trailing slash' => [
            'https://cdn.example.com',
            'fileadmin/user_upload/a.jpg',
            'https://cdn.example.com/fileadmin/user_upload/a.jpg',
        ];
        yield 'fileadmin file, host with trailing slash' => [
            'https://cdn.example.com/',
            'fileadmin/user_upload/a.jpg',
            'https://cdn.example.com/fileadmin/user_upload/a.jpg',
        ];
        yield 'extension resource from the fallback storage' => [
            'https://cdn.example.com',
            '_assets/abc/Images/logo.svg',
            'https://cdn.example.com/_assets/abc/Images/logo.svg',
        ];
        yield 'driver URL with leading slash, host with trailing slash' => [
            'https://cdn.example.com/',
            '/fileadmin/user_upload/a.jpg',
            'https://cdn.example.com/fileadmin/user_upload/a.jpg',
        ];
    }

    #[Test]
    #[DataProvider('publicUrlDataProvider')]
    public function publicUrlContainsSingleSlashBetweenHostAndPath(string $host, string $driverUrl, string $expected): void
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->createFrontendRequest($host);

        $event = $this->createEvent($driverUrl);
        $this->createSubject()->onResourceStorageEmitPreGeneratePublicUrlSignal($event);

        self::assertSame($expected, $event->getPublicUrl());
    }

    private function createSubject(): CdnEventListener
    {
        $onlineMediaHelperRegistry = $this->createStub(OnlineMediaHelperRegistry::class);
        $onlineMediaHelperRegistry->method('getOnlineMediaHelper')->willReturn(false);

        return new CdnEventListener(
            new CdnConfiguration($this->createStub(ConfigurationManagerInterface::class)),
            $onlineMediaHelperRegistry
        );
    }

    private function createEvent(string $driverUrl): GeneratePublicUrlForResourceEvent
    {
        $file = $this->createStub(File::class);
        $file->method('getIdentifier')->willReturn('/user_upload/a.jpg');

        $driver = $this->createStub(LocalDriver::class);
        $driver->method('getPublicUrl')->willReturn($driverUrl);

        return new GeneratePublicUrlForResourceEvent($file, $this->createStub(ResourceStorage::class), $driver);
    }

    private function createFrontendRequest(string $host): ServerRequest
    {
        $siteLanguage = $this->createStub(SiteLanguage::class);
        $siteLanguage->method('toArray')->willReturn([
            'awstools_cdn_enabled' => '1',
            'awstools_cdn_host' => $host,
        ]);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([
            'tx_awstools.' => [
                'enabled' => '1',
                'replacer.' => ['eventListener' => '1'],
            ],
        ]);

        return (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', $siteLanguage)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
