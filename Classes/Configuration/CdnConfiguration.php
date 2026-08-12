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

namespace Leuchtfeuer\AwsTools\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Single source of truth for the question "should CDN rewriting happen?".
 *
 * Both the event listener (file URLs) and the middleware (HTML content) ask this class,
 * so that a site language and TypoScript setup can never activate one and not the other.
 */
class CdnConfiguration implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const REPLACER_EVENT_LISTENER = 'eventListener';

    public const REPLACER_MIDDLEWARE = 'middleware';

    private const SUPPORTED_HOST_SCHEMES = ['http', 'https'];

    public function __construct(private readonly ConfigurationManagerInterface $configurationManager) {}

    /**
     * @param array<string, mixed> $language Site language configuration as array
     */
    public function isReplacerEnabled(string $replacer, array $language, ?ServerRequestInterface $request): bool
    {
        if (!$this->isFlagEnabled($language['awstools_cdn_enabled'] ?? null) || $this->resolveHost($language) === '') {
            return false;
        }

        $config = $this->getConfig($request);

        if ($config === null) {
            // eID contexts (e.g. tx_cms_showpic) have no TypoScript, so the site language decides alone
            return true;
        }

        $replacerConfig = $config['replacer.'] ?? [];

        return $this->isFlagEnabled($config['enabled'] ?? null)
            && is_array($replacerConfig)
            && $this->isFlagEnabled($replacerConfig[$replacer] ?? null);
    }

    /**
     * Returns an empty string for anything that is not an absolute http(s) URL, so that a
     * malformed CDN host disables the rewriting instead of producing broken public URLs.
     *
     * @param array<string, mixed> $language Site language configuration as array
     */
    public function resolveHost(array $language): string
    {
        $host = rtrim((string)($language['awstools_cdn_host'] ?? ''), '/');

        if ($host === '') {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_URL) === false
            || !in_array(parse_url($host, PHP_URL_SCHEME), self::SUPPORTED_HOST_SCHEMES, true)
        ) {
            $this->logger?->warning(
                'CDN host of the site language is not an absolute http(s) URL, CDN rewriting is disabled.',
                ['host' => $host]
            );

            return '';
        }

        return $host;
    }

    /**
     * @return list<array{search: string, replace: string}>
     */
    public function getPatterns(?ServerRequestInterface $request): array
    {
        $rawPatterns = ($this->getConfig($request) ?? [])['patterns.'] ?? [];

        if (!is_array($rawPatterns)) {
            return [];
        }

        $patterns = [];

        foreach ($rawPatterns as $rawPattern) {
            if (is_array($rawPattern) && isset($rawPattern['search'], $rawPattern['replace'])) {
                $patterns[] = [
                    'search' => (string)$rawPattern['search'],
                    'replace' => (string)$rawPattern['replace'],
                ];
            }
        }

        return $patterns;
    }

    /**
     * @return array<string, mixed>|null Null means TypoScript is unavailable, an empty array means it holds no configuration
     */
    private function getConfig(?ServerRequestInterface $request): ?array
    {
        $frontendTypoScript = $request?->getAttribute('frontend.typoscript');

        if ($frontendTypoScript instanceof FrontendTypoScript) {
            try {
                return $this->extractConfig($frontendTypoScript->getConfigArray());
            } catch (\RuntimeException $exception) {
                // config array not initialised yet, fall through to the Extbase configuration manager
                $this->logger?->debug(
                    'Frontend TypoScript holds no config array, falling back to the configuration manager.',
                    ['exception' => $exception]
                );
            }
        }

        try {
            $typoScript = $this->configurationManager
                ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

            if ($typoScript !== []) {
                $config = $typoScript['config'] ?? [];

                return $this->extractConfig(is_array($config) ? $config : []);
            }
        } catch (\Exception $exception) {
            // no page context available, e.g. in eID requests
            $this->logger?->debug(
                'TypoScript is unavailable, the site language configuration decides on its own.',
                ['exception' => $exception]
            );
        }

        return null;
    }

    /**
     * @param array<string, mixed> $configArray
     * @return array<string, mixed>
     */
    private function extractConfig(array $configArray): array
    {
        $config = $configArray['tx_awstools.'] ?? [];

        return is_array($config) ? $config : [];
    }

    private function isFlagEnabled(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
