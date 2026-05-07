<?php

use Leuchtfeuer\AwsTools\Constants;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey): void {
        if (!Environment::isComposerMode()) {
            require ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        ExtensionManagementUtility::addTypoScriptSetup(
            '@import \'EXT:aws_tools/Configuration/TypoScript/setup.typoscript\''
        );
    }, Constants::EXTENSION_KEY
);
