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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Stream;

class ContentReplaceMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $siteLanguage = $request->getAttribute('language');
        if ($siteLanguage === null) {
            return $handler->handle($request);
        }
        $language = $siteLanguage->toArray();
        $response = $handler->handle($request);
        $config = $GLOBALS['TSFE']->config['config']['tx_awstools.'] ?? [];

        if (empty($config['enabled']) || filter_var($language['awstools_cdn_enabled'], FILTER_VALIDATE_BOOLEAN) === false || empty($language['awstools_cdn_host']) || $config['replacer.']['middleware'] !== '1') {
            return $response;
        }

        $host = rtrim($language['awstools_cdn_host'], '/');
        $patterns = [];
        $replacements = [];

        foreach ($config['patterns.'] ?? [] as $search) {
            $patterns[] = sprintf('#%s#', $search['search']);
            $replacements[] = sprintf($search['replace'], $host);
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
