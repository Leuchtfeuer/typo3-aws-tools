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

namespace Leuchtfeuer\AwsTools\Tests\Functional\Fixtures;

/**
 * CloudFront matches invalidation paths byte for byte against the requested URL (measured on
 * 2026-11-17), so the frontend URL and every invalidation entry point must agree on these paths.
 */
final class FileNameCases
{
    public const FOLDER = 'cf-test';

    public const CDN_HOST = 'https://cdn.example.com';

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function filesWithExpectedPaths(): \Generator
    {
        yield 'plain' => ['kontrolle.txt', '/fileadmin/cf-test/kontrolle.txt'];
        yield 'at sign, measured on CloudFront' => ['at@test.txt', '/fileadmin/cf-test/at%40test.txt'];
        yield 'umlaut' => ['Über.pdf', '/fileadmin/cf-test/%C3%9Cber.pdf'];
        yield 'space' => ['a b.pdf', '/fileadmin/cf-test/a%20b.pdf'];
        yield 'parentheses' => ['a(1).pdf', '/fileadmin/cf-test/a%281%29.pdf'];
        yield 'plus' => ['a+b.pdf', '/fileadmin/cf-test/a%2Bb.pdf'];
        yield 'percent' => ['100%.pdf', '/fileadmin/cf-test/100%25.pdf'];
    }

    /**
     * @return list<string>
     */
    public static function fileNames(): array
    {
        return array_values(array_map(
            static fn(array $case): string => $case[0],
            iterator_to_array(self::filesWithExpectedPaths())
        ));
    }
}
