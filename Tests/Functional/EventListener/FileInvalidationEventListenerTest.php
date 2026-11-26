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

namespace Leuchtfeuer\AwsTools\Tests\Functional\EventListener;

use Leuchtfeuer\AwsTools\Tests\Functional\AwsToolsFunctionalTestCase;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;

final class FileInvalidationEventListenerTest extends AwsToolsFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUser(1);
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function replacingFileInvalidatesItsCdnPath(string $fileName, string $expectedPath): void
    {
        $replacement = Environment::getVarPath() . '/transient/replacement.txt';
        @mkdir(dirname($replacement), 0777, true);
        file_put_contents($replacement, 'replaced');
        $file = $this->getTestFile($fileName);

        $file->getStorage()->replaceFile($file, $replacement);

        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function settingFileContentsInvalidatesItsCdnPath(string $fileName, string $expectedPath): void
    {
        $this->getTestFile($fileName)->setContents('changed');

        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function deletingFileInvalidatesItsCdnPath(string $fileName, string $expectedPath): void
    {
        $this->getTestFile($fileName)->delete();

        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }
}
