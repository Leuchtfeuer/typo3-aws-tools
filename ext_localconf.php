<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }
    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
