<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
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
        $language = $request->getAttribute('language')->toArray();
        $response = $handler->handle($request);

        if ((bool)($language['awstools_cdn_enabled'] ?? false) === false || empty($language['awstools_cdn_host'])) {
            return $response;
        }

        $host = rtrim($language['awstools_cdn_host'], '/');
        $config = $GLOBALS['TSFE']->config['config']['tx_awstools.'] ?? [];
        $patterns = [];
        $replacements = [];

        foreach ($config['patterns.'] ?? [] as $search) {
            $patterns[] = sprintf('#%s#', $search['search']);
            $replacements[] = sprintf($search['replace'], $host);
        }

        if (!empty($patterns)) {
            $body = $response->getBody();
            $body->rewind();
            $contents = $response->getBody()->getContents();
            $content = preg_replace($patterns, $replacements, $contents);
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }

        return $response;
    }
}
