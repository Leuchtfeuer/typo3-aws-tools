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

namespace Leuchtfeuer\AwsTools\Tests\Unit\Service;

use Leuchtfeuer\AwsTools\Exception\InvalidInvalidationPathException;
use Leuchtfeuer\AwsTools\Service\InvalidationPathNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InvalidationPathNormalizerTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function cloudFrontPathDataProvider(): \Generator
    {
        yield 'relative path gets leading slash' => ['fileadmin/a.pdf', '/fileadmin/a.pdf'];
        yield 'full URL is reduced to path' => ['https://cdn.example.com/fileadmin/a.pdf', '/fileadmin/a.pdf'];
        yield 'full URL with port keeps query string' => ['https://cdn.example.com:8443/_assets/x/a.json?1794407943', '/_assets/x/a.json?1794407943'];
        yield 'protocol-relative URL is reduced to path' => ['//cdn.example.com/fileadmin/a.pdf', '/fileadmin/a.pdf'];
        yield 'trailing wildcard is kept' => ['/_assets/x/postcodes-de.json*', '/_assets/x/postcodes-de.json*'];
        yield 'wildcard for whole distribution is kept' => ['/*', '/*'];
        yield 'umlaut is encoded' => ['/fileadmin/Über.pdf', '/fileadmin/%C3%9Cber.pdf'];
        yield 'space is encoded' => ['/fileadmin/a b.pdf', '/fileadmin/a%20b.pdf'];
        yield 'encoded space is not encoded twice' => ['/fileadmin/a%20b.pdf', '/fileadmin/a%20b.pdf'];
        yield 'lone percent sign is encoded' => ['/fileadmin/100%.pdf', '/fileadmin/100%25.pdf'];
        yield 'unsafe characters are encoded' => ['/fileadmin/a{1}.pdf', '/fileadmin/a%7B1%7D.pdf'];
        yield 'fragment is dropped' => ['/index.html#top', '/index.html'];
        yield 'surrounding whitespace is trimmed' => ['  /fileadmin/a.pdf  ', '/fileadmin/a.pdf'];
        yield 'already normalized path stays unchanged' => ['/fileadmin/%C3%9Cber%20a.pdf?1', '/fileadmin/%C3%9Cber%20a.pdf?1'];
    }

    #[Test]
    #[DataProvider('cloudFrontPathDataProvider')]
    public function normalizeReturnsCloudFrontPath(string $path, string $expected): void
    {
        self::assertSame($expected, (new InvalidationPathNormalizer())->normalize($path));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function unsupportedPathDataProvider(): \Generator
    {
        yield 'tilde in path' => ['/fileadmin/a~b.pdf'];
        yield 'tilde in full URL' => ['https://cdn.example.com/~user/a.pdf'];
        yield 'empty path' => [''];
        yield 'whitespace only' => ['   '];
    }

    #[Test]
    #[DataProvider('unsupportedPathDataProvider')]
    public function normalizeRejectsUnsupportedPath(string $path): void
    {
        $this->expectException(InvalidInvalidationPathException::class);

        (new InvalidationPathNormalizer())->normalize($path);
    }
}
