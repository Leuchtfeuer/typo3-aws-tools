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

namespace Leuchtfeuer\AwsTools\Exception;

final class InvalidInvalidationPathException extends \InvalidArgumentException
{
    private string $path = '';

    public static function containsTilde(string $path): self
    {
        $exception = new self(
            sprintf('The path "%s" cannot be invalidated. Amazon CloudFront does not support the character "~" in invalidation paths.', $path),
            1794480001
        );
        $exception->path = $path;

        return $exception;
    }

    public static function isEmpty(): self
    {
        return new self('An empty path cannot be invalidated.', 1794480002);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
