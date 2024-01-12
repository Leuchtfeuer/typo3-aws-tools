<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Controller;

use Aws\Exception\AwsException;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class InvalidationController extends ActionController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected array $distributions;

    protected CloudFrontRepository $cloudFrontRepository;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        CloudFrontRepository $cloudFrontRepository,
        ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->distributions = $extensionConfiguration->getCloudFrontDistributions();
        $this->cloudFrontRepository = $cloudFrontRepository;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function indexAction(): ResponseInterface
    {
        $distributions = [];

        foreach ($this->distributions as $distribution) {
            try {
                $distributions[$distribution] = $this->cloudFrontRepository->findInvalidationsByDistribution($distribution)['InvalidationList'];
            } catch (AwsException $exception) {
                $this->addAwsException($exception, ContextualFeedbackSeverity::WARNING);
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('distributions', $distributions);

        return $moduleTemplate->renderResponse();
    }

    public function invalidateAction(string $resourcePaths): ResponseInterface
    {
        foreach ($this->distributions as $distribution) {
            try {
                $result = $this->cloudFrontRepository->createInvalidation($distribution, $this->clearResourcePaths($resourcePaths));
                $paths = implode(', ', $result['Invalidation']['InvalidationBatch']['Paths']['Items'] ?? []);

                $this->addFlashMessage(
                    LocalizationUtility::translate('messages.cloudfront_invalidation_success.body', Constants::EXTENSION_NAME, [urldecode($paths), $distribution]),
                    LocalizationUtility::translate('messages.cloudfront_invalidation_success.title', Constants::EXTENSION_NAME),
                    ContextualFeedbackSeverity::OK
                );
            } catch (AwsException $exception) {
                $this->addAwsException($exception, ContextualFeedbackSeverity::ERROR);
            }
        }

        return $this->redirect('index');
    }

    protected function addAwsException(
        AwsException $exception,
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR,
        bool $storeInSession = true
    ): void {
        $this->addFlashMessage(
            $exception->getAwsErrorMessage(),
            LocalizationUtility::translate($exception->getAwsErrorCode(), Constants::EXTENSION_NAME) ?? $exception->getAwsErrorCode(),
            $severity,
            $storeInSession
        );
    }

    protected function clearResourcePaths(string $paths): array
    {
        $resourcePaths = [];

        foreach (GeneralUtility::trimExplode(LF, $paths, true) as $path) {
            if (!str_contains($path, ' ')) {
                $resourcePaths[] = $path;
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate('messages.invalid_resource_path.body', Constants::EXTENSION_NAME, [$path]),
                    LocalizationUtility::translate('messages.invalid_resource_path.title', Constants::EXTENSION_NAME),
                    ContextualFeedbackSeverity::WARNING
                );
            }
        }

        return $resourcePaths;
    }
}
