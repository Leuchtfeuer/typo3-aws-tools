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

namespace Leuchtfeuer\AwsTools\Tests\Functional;

use Aws\CloudFront\CloudFrontClient;
use Aws\MockHandler;
use Aws\Result;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Factory\CloudFrontFactory;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AwsToolsFunctionalTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filelist'];

    protected array $testExtensionsToLoad = ['leuchtfeuer/aws-tools'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'aws_tools' => [
                'accessKeyId' => 'test',
                'secretAccessKey' => 'test',
                'region' => 'eu-central-1',
                'cloudFrontDistributions' => 'DIST',
            ],
        ],
    ];

    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/be_users.csv');

        $folderPath = Environment::getPublicPath() . '/fileadmin/' . FileNameCases::FOLDER . '/';
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }
        foreach (FileNameCases::fileNames() as $fileName) {
            file_put_contents($folderPath . $fileName, 'original');
        }

        $this->mockHandler = new MockHandler();
        for ($i = 0; $i < 5; $i++) {
            $this->mockHandler->append(new Result([]));
        }
        // The client is a private service built by a static factory, so the cached instance is the only seam.
        (new \ReflectionProperty(CloudFrontFactory::class, '_client'))->setValue(null, new CloudFrontClient([
            'region' => 'eu-central-1',
            'version' => Constants::VERSION,
            'credentials' => false,
            'handler' => $this->mockHandler,
        ]));
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(CloudFrontFactory::class, '_client'))->setValue(null, null);

        parent::tearDown();
    }

    /**
     * @return list<string>
     */
    protected function sentInvalidationPaths(): array
    {
        $command = $this->mockHandler->getLastCommand();

        return array_values($command['InvalidationBatch']['Paths']['Items'] ?? []);
    }

    protected function getTestFile(string $fileName): File
    {
        $file = $this->get(StorageRepository::class)
            ->findByUid(1)
            ?->getFile('/' . FileNameCases::FOLDER . '/' . $fileName);
        self::assertInstanceOf(File::class, $file);

        return $file;
    }
}
