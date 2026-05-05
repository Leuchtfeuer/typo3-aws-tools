<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey): void {
        if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode()) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
            '@import \'EXT:aws_tools/Configuration/TypoScript/setup.typoscript\''
        );
    }, \Leuchtfeuer\AwsTools\Constants::EXTENSION_KEY
);
