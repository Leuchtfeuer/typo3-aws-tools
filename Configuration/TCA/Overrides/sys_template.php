<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extensionKey,
            'Configuration/TypoScript',
            'AWS Tools'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            $extensionKey,
            'Configuration/TypoScript/ReplaceConfig',
            'AWS Tools (CDN Replace Configuration)'
        );
    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
