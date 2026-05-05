<?php

/*
 * This file is part of the "AWS Tools" extension.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\AwsTools\EventListener;

use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

readonly class EditIconsEventListener implements SingletonInterface
{
    public function __construct(private UriBuilder $uriBuilder, private IconFactory $iconFactory) {}

    /**
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

            $attributes = [
                'href' => (string)$this->uriBuilder->buildUriFromRoute('ajax_awstools_invalidate', ['identifier' => $identifier]),
                'title' => $GLOBALS['LANG']->sL(sprintf('LLL:EXT:%s/Resources/Private/Language/locallang.xlf:messages.invalid_resource_path.title', Constants::EXTENSION_KEY)),
                'data-type' => $type,
                'data-identifier' => $item->getIdentifier(),
                'data-storage' => $item->getStorage()->getUid(),
            ];

            $actionItems['awstools_invalidate'] = sprintf(
                '<a class="btn btn-default c-awstools__invalidate" %s>%s</a>',
                GeneralUtility::implodeAttributes($attributes, true),
                $this->iconFactory->getIcon('actions-bolt', IconSize::SMALL)->render()
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
