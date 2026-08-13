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

namespace Leuchtfeuer\AwsTools\Tests\Unit\Configuration;

use Leuchtfeuer\AwsTools\Configuration\CdnConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CdnConfigurationTest extends UnitTestCase
{
    private ConfigurationManagerInterface&Stub $configurationManager;

    private CdnConfiguration $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationManager = $this->createStub(ConfigurationManagerInterface::class);
        $this->subject = new CdnConfiguration($this->configurationManager);
    }

    /**
     * @return \Generator<string, array{mixed, bool}>
     */
    public static function booleanFlagValueDataProvider(): \Generator
    {
        yield 'TypoScript string one' => ['1', true];
        yield 'integer one' => [1, true];
        yield 'boolean true' => [true, true];
        yield 'string true' => ['true', true];
        yield 'string on' => ['on', true];
        yield 'string yes' => ['yes', true];
        yield 'TypoScript string zero' => ['0', false];
        yield 'integer zero' => [0, false];
        yield 'boolean false' => [false, false];
        yield 'string false' => ['false', false];
        yield 'string off' => ['off', false];
        yield 'string no' => ['no', false];
        yield 'empty string' => ['', false];
        yield 'null' => [null, false];
        yield 'arbitrary string' => ['lorem', false];
    }

    #[Test]
    #[DataProvider('booleanFlagValueDataProvider')]
    public function siteLanguageFlagIsEvaluatedAsBoolean(mixed $flagValue, bool $expectation): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => '1',
            'replacer.' => ['eventListener' => '1'],
        ]);

        self::assertSame($expectation, $this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage($flagValue),
            $request
        ));
    }

    #[Test]
    #[DataProvider('booleanFlagValueDataProvider')]
    public function typoScriptEnabledFlagIsEvaluatedAsBoolean(mixed $flagValue, bool $expectation): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => $flagValue,
            'replacer.' => ['eventListener' => '1'],
        ]);

        self::assertSame($expectation, $this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $request
        ));
    }

    #[Test]
    #[DataProvider('booleanFlagValueDataProvider')]
    public function replacerFlagIsEvaluatedAsBoolean(mixed $flagValue, bool $expectation): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => '1',
            'replacer.' => ['eventListener' => $flagValue],
        ]);

        self::assertSame($expectation, $this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $request
        ));
    }

    #[Test]
    public function replacersAreEvaluatedIndependently(): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => '1',
            'replacer.' => [
                'eventListener' => '1',
                'middleware' => '0',
            ],
        ]);
        $language = $this->createLanguage('1');

        self::assertTrue($this->subject->isReplacerEnabled(CdnConfiguration::REPLACER_EVENT_LISTENER, $language, $request));
        self::assertFalse($this->subject->isReplacerEnabled(CdnConfiguration::REPLACER_MIDDLEWARE, $language, $request));
    }

    #[Test]
    public function missingReplacerFlagDisablesRewriting(): void
    {
        $request = $this->createRequestWithTypoScript(['enabled' => '1']);

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $request
        ));
    }

    #[Test]
    public function missingHostDisablesRewritingDespiteEnabledFlags(): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => '1',
            'replacer.' => ['eventListener' => '1'],
        ]);

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            ['awstools_cdn_enabled' => '1'],
            $request
        ));
    }

    #[Test]
    public function configurationManagerIsUsedWhenRequestHasNoFrontendTypoScript(): void
    {
        $this->configurationManager->method('getConfiguration')->willReturn([
            'config' => [
                'tx_awstools.' => [
                    'enabled' => '1',
                    'replacer.' => ['eventListener' => '1'],
                ],
            ],
        ]);

        self::assertTrue($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest(null)
        ));
    }

    #[Test]
    public function configurationManagerIsUsedWhenFrontendTypoScriptHasNoConfigArray(): void
    {
        $this->configurationManager->method('getConfiguration')->willReturn([
            'config' => [
                'tx_awstools.' => [
                    'enabled' => '1',
                    'replacer.' => ['eventListener' => '0'],
                ],
            ],
        ]);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest($frontendTypoScript)
        ));
    }

    #[Test]
    public function availableTypoScriptWithoutExtensionConfigurationDisablesRewriting(): void
    {
        $this->configurationManager->method('getConfiguration')->willReturn(['config' => []]);

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest(null)
        ));
    }

    #[Test]
    public function unavailableTypoScriptFallsBackToSiteLanguageConfiguration(): void
    {
        $this->configurationManager->method('getConfiguration')
            ->willThrowException(new \RuntimeException('no page context', 1770000000));

        self::assertTrue($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest(null)
        ));
    }

    #[Test]
    public function unavailableTypoScriptStillHonoursDisabledSiteLanguage(): void
    {
        $this->configurationManager->method('getConfiguration')
            ->willThrowException(new \RuntimeException('no page context', 1770000001));

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('0'),
            $this->createRequest(null)
        ));
    }

    #[Test]
    public function unavailableTypoScriptIsLoggedForDebugging(): void
    {
        $this->configurationManager->method('getConfiguration')
            ->willThrowException(new \RuntimeException('no page context', 1770000003));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug');
        $this->subject->setLogger($logger);

        $this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest(null)
        );
    }

    #[Test]
    public function uninitialisedConfigArrayIsLoggedForDebugging(): void
    {
        $this->configurationManager->method('getConfiguration')->willReturn(['config' => []]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug');
        $this->subject->setLogger($logger);

        $this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            $this->createLanguage('1'),
            $this->createRequest(new FrontendTypoScript(new RootNode(), [], [], []))
        );
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, string}>
     */
    public static function hostDataProvider(): \Generator
    {
        yield 'host without trailing slash' => [['awstools_cdn_host' => 'https://cdn.example.com'], 'https://cdn.example.com'];
        yield 'host with trailing slash' => [['awstools_cdn_host' => 'https://cdn.example.com/'], 'https://cdn.example.com'];
        yield 'host with multiple trailing slashes' => [['awstools_cdn_host' => 'https://cdn.example.com//'], 'https://cdn.example.com'];
        yield 'host with unencrypted scheme' => [['awstools_cdn_host' => 'http://cdn.example.com'], 'http://cdn.example.com'];
        yield 'host with port' => [['awstools_cdn_host' => 'https://cdn.example.com:8080'], 'https://cdn.example.com:8080'];
        yield 'host with path' => [['awstools_cdn_host' => 'https://cdn.example.com/assets/'], 'https://cdn.example.com/assets'];
        yield 'missing host' => [[], ''];
        yield 'empty host' => [['awstools_cdn_host' => ''], ''];
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function malformedHostDataProvider(): \Generator
    {
        yield 'malformed url' => [['awstools_cdn_host' => 'https:///www.example.org|']];
        yield 'missing scheme' => [['awstools_cdn_host' => 'cdn.example.com']];
        yield 'protocol relative host' => [['awstools_cdn_host' => '//cdn.example.com']];
        yield 'unsupported scheme' => [['awstools_cdn_host' => 'javascript:alert(1)']];
        yield 'data scheme' => [['awstools_cdn_host' => 'data:text/html,<h1>x</h1>']];
        yield 'whitespace only' => [['awstools_cdn_host' => '   ']];
        yield 'slashes only' => [['awstools_cdn_host' => '///']];
    }

    #[Test]
    #[DataProvider('hostDataProvider')]
    public function hostIsNormalised(array $language, string $expectation): void
    {
        self::assertSame($expectation, $this->subject->resolveHost($language));
    }

    #[Test]
    #[DataProvider('malformedHostDataProvider')]
    public function malformedHostIsDiscarded(array $language): void
    {
        self::assertSame('', $this->subject->resolveHost($language));
    }

    #[Test]
    #[DataProvider('malformedHostDataProvider')]
    public function malformedHostDisablesRewritingDespiteEnabledFlags(array $language): void
    {
        $request = $this->createRequestWithTypoScript([
            'enabled' => '1',
            'replacer.' => ['eventListener' => '1'],
        ]);

        self::assertFalse($this->subject->isReplacerEnabled(
            CdnConfiguration::REPLACER_EVENT_LISTENER,
            ['awstools_cdn_enabled' => '1'] + $language,
            $request
        ));
    }

    #[Test]
    public function malformedHostIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            self::anything(),
            ['host' => 'cdn.example.com']
        );
        $this->subject->setLogger($logger);

        $this->subject->resolveHost(['awstools_cdn_host' => 'cdn.example.com']);
    }

    #[Test]
    public function validHostIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $this->subject->setLogger($logger);

        $this->subject->resolveHost(['awstools_cdn_host' => 'https://cdn.example.com']);
    }

    #[Test]
    public function patternsAreNormalisedAndMalformedEntriesAreSkipped(): void
    {
        $request = $this->createRequestWithTypoScript([
            'patterns.' => [
                '10.' => ['search' => '"/typo3temp/', 'replace' => '"%s/typo3temp/'],
                '20.' => ['search' => '"/typo3conf/'],
                '30.' => 'not an array',
            ],
        ]);

        self::assertSame(
            [['search' => '"/typo3temp/', 'replace' => '"%s/typo3temp/']],
            $this->subject->getPatterns($request)
        );
    }

    #[Test]
    public function patternsAreEmptyWhenTypoScriptIsUnavailable(): void
    {
        $this->configurationManager->method('getConfiguration')
            ->willThrowException(new \RuntimeException('no page context', 1770000002));

        self::assertSame([], $this->subject->getPatterns($this->createRequest(null)));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createRequestWithTypoScript(array $config): ServerRequestInterface
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray(['tx_awstools.' => $config]);

        return $this->createRequest($frontendTypoScript);
    }

    private function createRequest(?FrontendTypoScript $frontendTypoScript): ServerRequestInterface
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturnCallback(
            static fn (string $name): ?FrontendTypoScript => $name === 'frontend.typoscript' ? $frontendTypoScript : null
        );

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function createLanguage(mixed $enabled): array
    {
        return [
            'awstools_cdn_enabled' => $enabled,
            'awstools_cdn_host' => 'https://cdn.example.com',
        ];
    }
}
