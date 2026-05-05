<?php

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\AwsTools\Controller;

use Aws\Exception\AwsException;
use Leuchtfeuer\AwsTools\Constants;
use Leuchtfeuer\AwsTools\Domain\Repository\CloudFrontRepository;
use Leuchtfeuer\AwsTools\Domain\Transfer\ExtensionConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

readonly class BackendController implements SingletonInterface
{
    public function __construct(
        private CloudFrontRepository $cloudFrontRepository,
        private ResourceFactory $resourceFactory,
        private StorageRepository $storageRepository,
        private Context $context
    ) {}

    public function invalidateAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $item = $this->getItem($data);

        if ($item !== null && $this->isPermitted($item, $data['type']) && $identifier = $item->getPublicUrl()) {
            try {
                $identifier = '/' . ltrim($identifier, '/');
                $distributions = GeneralUtility::makeInstance(ExtensionConfiguration::class)->getCloudFrontDistributions();
                $this->cloudFrontRepository->createBatchInvalidation($distributions, $identifier);

                return new JsonResponse([
                    'message' => LocalizationUtility::translate('messages.cloudfront_invalidation_success.body', Constants::EXTENSION_NAME, [urldecode($identifier), implode(', ', $distributions)]),
                    'title' => LocalizationUtility::translate('messages.cloudfront_invalidation_success.title', Constants::EXTENSION_NAME),
                ]);
            } catch (AwsException $exception) {
                return new JsonResponse(['message' => $exception->getAwsErrorMessage()], 500);
            }
        }

        return new JsonResponse(['message' => 'An unknown error occurred.'], 500);
    }

    /** @param array<string, mixed> $data */
    protected function getItem(array $data): FileInterface|Folder|null
    {
        return match ($data['type']) {
            'Folder' => $this->getFolder($data['identifier'], (int)$data['storage']),
            'File' => $this->getFile($data['identifier'], (int)$data['storage']),
            default => null,
        };
    }

    protected function getFolder(string $identifier, int $storage): Folder
    {
        return $this->resourceFactory
            ->getFolderObjectFromCombinedIdentifier(sprintf('%d:%s', $storage, $identifier));
    }

    protected function getFile(string $identifier, int $storage): ?FileInterface
    {
        return $this->storageRepository->findByUid($storage)?->getFile($identifier);
    }

    protected function isPermitted(?ResourceInterface $item, string $type): bool
    {
        try {
            return
                $item instanceof \TYPO3\CMS\Core\Resource\ResourceInterface
                && $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn')
                && $item->getStorage()->checkUserActionPermission('invalidate', $type);
        } catch (AspectNotFoundException) {
            return false;
        }
    }
}
