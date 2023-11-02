<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

class EditIconsEventListener implements SingletonInterface
{
    /**
     * @param ProcessFileListActionsEvent $event
     *
     * @throws RouteNotFoundException
     */
    public function manipulateEditIcons(ProcessFileListActionsEvent $event): void
    {
        $actionItems = $event->getActionItems();

        $item = $event->getResource();
        $type = $this->getType($item);

        if ($type !== null && $item->getStorage()->checkUserActionPermission('invalidate', $type)) {
            $identifier = $item->getIdentifier();

            if ($item instanceof FolderInterface) {
                $identifier .= '*';
            }

            /**
             * @var UriBuilder $uriBuilder
             */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $attributes = [
                'href' => (string)$uriBuilder->buildUriFromRoute('ajax_awstools_invalidate', ['identifier' => $identifier]),
                'title' => $GLOBALS['LANG']->sL(sprintf('LLL:EXT:%s/Resources/Private/Language/locallang.xlf:messages.invalid_resource_path.title', Constants::EXTENSION_KEY)),
                'data-type' => $type,
                'data-identifier' => $item->getIdentifier(),
                'data-storage' => $item->getStorage()->getUid()
            ];

            /**
             * @var IconFactory $iconFactory
             */
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $actionItems['awstools_invalidate'] = sprintf(
                '<a class="btn btn-default c-awstools__invalidate" %s>%s</a>',
                GeneralUtility::implodeAttributes($attributes, true),
                $iconFactory->getIcon('actions-bolt', Icon::SIZE_SMALL)->render()
            );
        }

        $event->setActionItems($actionItems);
    }

    protected function getType(ResourceInterface $item): ?string
    {
        if ($item instanceof FolderInterface) {
            return 'Folder';
        }
        if ($item instanceof FileInterface) {
            return 'File';
        }

        return null;
    }
}
