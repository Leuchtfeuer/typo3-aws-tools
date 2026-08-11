<?php

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\AwsTools\Middleware;

use Leuchtfeuer\AwsTools\Configuration\CdnConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;

class ContentReplaceMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CdnConfiguration $cdnConfiguration) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $siteLanguage = $request->getAttribute('language');
        if ($siteLanguage === null) {
            return $handler->handle($request);
        }
        $language = $siteLanguage->toArray();
        $response = $handler->handle($request);

        if (!$this->cdnConfiguration->isReplacerEnabled(CdnConfiguration::REPLACER_MIDDLEWARE, $language, $request)) {
            return $response;
        }

        $host = $this->cdnConfiguration->resolveHost($language);
        $patterns = [];
        $replacements = [];

        foreach ($this->cdnConfiguration->getPatterns($request) as $pattern) {
            $patterns[] = sprintf('#%s#', $pattern['search']);
            $replacements[] = sprintf($pattern['replace'], $host);
        }

        if ($patterns !== []) {
            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            $content = preg_replace($patterns, $replacements, $contents) ?? $contents;
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }

        return $response;
    }
}
