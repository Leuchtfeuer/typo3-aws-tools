<?php

/*
 * This file is part of the "AWS Tools" extension for TYPO3 CMS.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\AwsTools\TCA;

use Leuchtfeuer\AwsTools\Constants;

class FilePermissions
{
    public static function extendFilePermissions(string $tableName): void
    {
        $filePermissions = static::getFilePermissions();
        $items = [];

        foreach ($GLOBALS['TCA'][$tableName]['columns']['file_permissions']['config']['items'] as $item) {
            $items[] = $item;

            if ($item[1] === 'recursivedeleteFolder') {
                $items[] = $filePermissions['invalidateFolder'];
            }

            if ($item[1] === 'deleteFile') {
                $items[] = $filePermissions['invalidateFile'];
            }
        }

        $GLOBALS['TCA'][$tableName]['columns']['file_permissions']['config']['items'] = $items;
        $itemCount = count($items) - 1;
        $GLOBALS['TCA'][$tableName]['columns']['file_permissions']['config']['maxitems'] = $itemCount;
        $GLOBALS['TCA'][$tableName]['columns']['file_permissions']['config']['size'] = $itemCount;
    }

    protected static function getFilePermissions(): array
    {
        return [
            'invalidateFile' => [
                sprintf('LLL:EXT:%s/Resources/Private/Language/locallang.xlf:file_permissions.invalidate_file', Constants::EXTENSION_KEY),
                'invalidateFile',
                'mimetypes-other-other',
            ],
            'invalidateFolder' => [
                sprintf('LLL:EXT:%s/Resources/Private/Language/locallang.xlf:file_permissions.invalidate_folder', Constants::EXTENSION_KEY),
                'invalidateFolder',
                'apps-filetree-folder-default',
            ],
        ];
    }
}
