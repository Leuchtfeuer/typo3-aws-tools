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

namespace Leuchtfeuer\AwsTools\Service;

use Leuchtfeuer\AwsTools\Exception\InvalidInvalidationPathException;

/**
 * Prepares a path or URL for a CloudFront invalidation: query string and trailing wildcard are kept,
 * only non-ASCII and unsafe characters are percent-encoded, as CloudFront rejects over-encoded paths.
 */
final readonly class InvalidationPathNormalizer
{
    private const SCHEME_AND_HOST_PATTERN = '#^(?:[a-z][a-z0-9+.-]*:)?//[^/?]*#i';

    private const CHARACTERS_TO_ENCODE_PATTERN = '/[^\x21-\x7E]|[<>"{}|\\\\^\[\]`]|%(?![0-9A-Fa-f]{2})/';

    /**
     * @throws InvalidInvalidationPathException
     */
    public function normalize(string $path): string
    {
        $normalizedPath = trim(explode('#', $path, 2)[0]);

        if ($normalizedPath === '') {
            throw InvalidInvalidationPathException::isEmpty();
        }

        if (str_contains($normalizedPath, '~') || stripos($normalizedPath, '%7e') !== false) {
            throw InvalidInvalidationPathException::containsTilde($path);
        }

        $normalizedPath = '/' . ltrim((string)preg_replace(self::SCHEME_AND_HOST_PATTERN, '', $normalizedPath), '/');

        return (string)preg_replace_callback(
            self::CHARACTERS_TO_ENCODE_PATTERN,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $normalizedPath
        );
    }
}
