<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\Hook;

use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\FileListEditIconHookInterface;

class EditIconsHook implements FileListEditIconHookInterface
{
    /**
     * @param array $cells
     * @param FileList $parentObject
     *
     * @throws RouteNotFoundException
     */
    public function manipulateEditIcons(&$cells, &$parentObject)
    {
        /** @var FolderInterface|FileInterface $item */
        $item = $cells['__fileOrFolderObject'];
        $type = $this->getType($item);

        if ($type !== null && $item->getStorage()->checkUserActionPermission('invalidate', $type)) {
            $identifier = $item->getIdentifier();

            if ($item instanceof FolderInterface) {
                $identifier .= '*';
            }

            $attributes = [
                'href' => (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('ajax_awstools_invalidate', ['identifier' => $identifier]),
                'title' => $GLOBALS['LANG']->sL(sprintf('LLL:EXT:%s/Resources/Private/Language/locallang.xlf:messages.invalid_resource_path.title', Constants::EXTENSION_KEY)),
                'data-type' => $type,
                'data-identifier' => $item->getIdentifier(),
                'data-storage' => $item->getStorage()->getUid()
            ];

            $cells['awstools_invalidate'] = sprintf(
                '<a class="btn btn-default c-awstools__invalidate" %s>%s</a>',
                GeneralUtility::implodeAttributes($attributes, true),
                GeneralUtility::makeInstance(IconFactory::class)->getIcon('actions-bolt', Icon::SIZE_SMALL)->render()
            );
        }
    }

    /**
     * @param FileInterface|FolderInterface $item
     * @return string|null
     */
    protected function getType($item): ?string
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
