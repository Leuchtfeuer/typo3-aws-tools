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

namespace Leuchtfeuer\AwsTools\Tests\Functional\Controller;

use Leuchtfeuer\AwsTools\Controller\InvalidationController;
use Leuchtfeuer\AwsTools\Tests\Functional\AwsToolsFunctionalTestCase;
use Leuchtfeuer\AwsTools\Tests\Functional\Fixtures\FileNameCases;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

final class InvalidationControllerTest extends AwsToolsFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUser(1);
    }

    #[Test]
    #[DataProviderExternal(FileNameCases::class, 'filesWithExpectedPaths')]
    public function invalidatingCopiedCdnUrlSendsItsPath(string $fileName, string $expectedPath): void
    {
        $extbaseParameters = (new ExtbaseRequestParameters(InvalidationController::class))
            ->setControllerExtensionName('AwsTools')
            ->setControllerName('Invalidation')
            ->setControllerActionName('invalidate')
            ->setArgument('resourcePaths', FileNameCases::CDN_HOST . $expectedPath);
        $serverRequest = new ServerRequest('https://www.example.com/typo3/module/page/awstools', 'POST');
        $request = new Request(
            $serverRequest
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($serverRequest))
                ->withAttribute('extbase', $extbaseParameters)
        );

        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->get(InvalidationController::class)->processRequest($request);

        self::assertSame([$expectedPath], $this->sentInvalidationPaths());
    }
}
