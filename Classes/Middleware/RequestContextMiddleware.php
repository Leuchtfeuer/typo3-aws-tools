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

/**
 * Ensures $GLOBALS['TYPO3_REQUEST'] is populated after site resolution so that
 * event listeners (e.g. CdnEventListener) can access the enriched request in
 * eID contexts where the frontend RequestHandler::handle() is never reached.
 */
readonly class RequestContextMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $handler->handle($request);
    }
}
