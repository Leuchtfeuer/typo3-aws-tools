<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extensionKey,
            'Configuration/TypoScript',
            sprintf(
                'LLL:EXT:%s/Resources/Private/Language/Database.xlf:template.common',
                $extensionKey
            )
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extensionKey,
            'Configuration/TypoScript/ReplaceConfig',
            sprintf(
                'LLL:EXT:%s/Resources/Private/Language/Database.xlf:template.replace',
                $extensionKey
            )
        );
    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
