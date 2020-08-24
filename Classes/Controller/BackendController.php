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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendController implements SingletonInterface
{
    protected $cloudFrontRepository;

    public function __construct(CloudFrontRepository $cloudFrontRepository)
    {
        $this->cloudFrontRepository = $cloudFrontRepository;
    }

    public function invalidateAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $item = $this->getItem($data);

        if ($this->isPermitted($item, $data['type']) && $identifier = $request->getQueryParams()['identifier'] ?? false) {
            try {
                $distributions = GeneralUtility::makeInstance(ExtensionConfiguration::class)->getCloudFrontDistributions();

                foreach ($distributions as $distribution) {
                    $this->cloudFrontRepository->createInvalidation($distribution, $identifier);
                }

                return new JsonResponse([
                    'message' => LocalizationUtility::translate('messages.cloudfront_invalidation_success.body', Constants::EXTENSION_NAME, [urldecode($identifier), implode(', ', $distributions)]),
                    'title' => LocalizationUtility::translate('messages.cloudfront_invalidation_success.title', Constants::EXTENSION_NAME)
                ]);
            } catch (AwsException $exception) {
                return new JsonResponse(['message' => $exception->getAwsErrorMessage()], 500);
            }
        }

        return new JsonResponse(['message' => 'An unknown error occurred.'], 500);
    }

    /**
     * @param array $data
     * @return FileInterface|FolderInterface|null
     */
    protected function getItem(array $data)
    {
        switch ($data['type']) {
            case 'Folder':
                return $this->getFolder($data['identifier'], (int)$data['storage']);

            case 'File':
                return $this->getFile($data['identifier'], (int)$data['storage']);
        }

        return null;
    }

    protected function getFolder(string $identifier, int $storage): FolderInterface
    {
        return GeneralUtility::makeInstance(ResourceFactory::class)
            ->getFolderObjectFromCombinedIdentifier(sprintf('%d:%s', $storage, $identifier));
    }

    protected function getFile(string $identifier, int $storage): FileInterface
    {
        return GeneralUtility::makeInstance(ResourceFactory::class)
            ->getFileObjectByStorageAndIdentifier($storage, $identifier);
    }

    /**
     * @param FileInterface|FolderInterface|null $item
     * @param string $type
     * @return bool
     * @throws AspectNotFoundException
     */
    protected function isPermitted($item, string $type): bool
    {
        return
            $item !== null
            && GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn')
            && $item->getStorage()->checkUserActionPermission('invalidate', $type);
    }
}
