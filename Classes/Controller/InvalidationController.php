<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Controller;

use Aws\Exception\AwsException;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class InvalidationController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;
    protected $distributionIds;
    protected $cloudFrontRepository;
    protected $exception;

    public function __construct(ExtensionConfiguration $extensionConfiguration, CloudFrontRepository $cloudFrontRepository)
    {
        $this->distributionIds = $extensionConfiguration->getCloudFrontDistributions();
        $this->cloudFrontRepository = $cloudFrontRepository;
    }

    public function indexAction(): void
    {
        $distributions = [];

        foreach ($this->distributionIds as $distributionId) {
            try {
                $distributions[$distributionId] = $this->cloudFrontRepository->findInvalidationsByDistribution($distributionId)['InvalidationList'];
            } catch (AwsException $exception) {
                $this->addAwsException($exception, AbstractMessage::WARNING);
            }
        }

        $this->view->assign('distributions', $distributions);
    }

    public function invalidateAction(string $resourcePaths): void
    {
        $processedResourcePaths = [];

        foreach (GeneralUtility::trimExplode(LF, $resourcePaths, true) as $path) {
            if (strpos($path, ' ') === false) {
                $processedResourcePaths[] = $path;
            } else {
                $this->addFlashMessage(
                    LocalizationUtility::translate('messages.invalid_resource_path.body', Constants::EXTENSION_NAME, [$path]),
                    LocalizationUtility::translate('messages.invalid_resource_path.title', Constants::EXTENSION_NAME),
                    AbstractMessage::WARNING
                );
            }
        }

        foreach ($this->distributionIds as $distributionId) {
            try {
                $result = $this->cloudFrontRepository->createInvalidation($distributionId, $processedResourcePaths);
                $paths = implode(', ', $result['Invalidation']['InvalidationBatch']['Paths']['Items'] ?? []);

                $this->addFlashMessage(
                    LocalizationUtility::translate('messages.cloudfront_invalidation_success.body', Constants::EXTENSION_NAME, [urldecode($paths), $distributionId]),
                    LocalizationUtility::translate('messages.cloudfront_invalidation_success.title', Constants::EXTENSION_NAME),
                    AbstractMessage::OK
                );
            } catch (AwsException $exception) {
                $this->addAwsException($exception, AbstractMessage::ERROR);
            }
        }

        $this->redirect('index');
    }

    protected function addAwsException(
        AwsException $exception,
        int $severity = AbstractMessage::ERROR,
        bool $storeInSession = true
    ): void {
        $this->addFlashMessage(
            $exception->getAwsErrorMessage(),
            LocalizationUtility::translate($exception->getAwsErrorCode(), Constants::EXTENSION_NAME) ?? $exception->getAwsErrorCode(),
            $severity,
            $storeInSession
        );
    }
}
